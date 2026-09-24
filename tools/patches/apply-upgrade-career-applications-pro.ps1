$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Write-NoBom($Path, $Content) {
    $full = [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $Path))
    $dir = [System.IO.Path]::GetDirectoryName($full)
    if (-not [System.IO.Directory]::Exists($dir)) {
        [System.IO.Directory]::CreateDirectory($dir) | Out-Null
    }
    [System.IO.File]::WriteAllText($full, $Content, $utf8NoBom)
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-career-applications-$stamp"

Write-Host "==================================================================" -ForegroundColor Yellow
Write-Host " Upgrade Career Profesional - Pelamar, CV, Portofolio, Notifikasi" -ForegroundColor Yellow
Write-Host "==================================================================" -ForegroundColor Yellow

$toBackup = @(
    ".\app\Models\JobVacancy.php",
    ".\app\Models\JobApplication.php",
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\database\migrations\2026_09_24_000002_create_job_applications_table.php",
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\frontend\lamar-kerja.blade.php",
    ".\resources\views\layouts\admin-panel.blade.php",
    ".\resources\views\components\admin\nav-badge.blade.php",
    ".\resources\views\components\notification-bell.blade.php",
    ".\routes\web.php"
)

Step "[1/8] Membuat backup ..."
foreach ($item in $toBackup) {
    if (Test-Path $item) {
        $relative = $item.TrimStart('.', '\')
        $dest = Join-Path $backupDir $relative
        $destDir = Split-Path $dest -Parent
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        Copy-Item $item $dest -Force
    }
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/8] Menulis model, migration, controller, dan halaman Career ..."

$content_job_vacancy = @'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * KIE_JOB_VACANCIES_FEATURE
 * Lowongan kerja yang dikelola dari Admin > Loker dan ditampilkan di /karier.
 */
class JobVacancy extends Model
{
    protected $fillable = [
        'title',
        'department',
        'employment_type',
        'work_mode',
        'location',
        'salary_label',
        'summary',
        'description',
        'requirements',
        'benefits',
        'deadline',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** Lowongan yang masih boleh dilihat dan menerima pendaftaran. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('deadline')->orWhereDate('deadline', '>=', today());
            });
    }

    public function isExpired(): bool
    {
        return $this->deadline?->isBefore(today()) ?? false;
    }

    public function isOpen(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }
}
'@

$content_job_application = @'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    protected $fillable = [
        'job_vacancy_id',
        'job_title',
        'full_name',
        'email',
        'whatsapp',
        'domicile',
        'last_education',
        'experience_years',
        'portfolio_url',
        'cover_letter',
        'cv_path',
        'portfolio_path',
        'status',
        'is_read_admin',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'is_read_admin' => 'boolean',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function scopeUnreadAdmin(Builder $query): Builder
    {
        return $query->where('is_read_admin', false);
    }
}
'@

$content_migration = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_vacancy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title', 120);
            $table->string('full_name', 120);
            $table->string('email', 160);
            $table->string('whatsapp', 30);
            $table->string('domicile', 120);
            $table->string('last_education', 120)->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('portfolio_url', 500)->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('cv_path', 500);
            $table->string('portfolio_path', 500)->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->boolean('is_read_admin')->default(false)->index();
            $table->timestamps();

            $table->index(['job_vacancy_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
'@

$content_controller = @'
<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobVacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class JobApplicationController extends Controller
{
    public function create(JobVacancy $job): View
    {
        abort_unless($job->isOpen(), 404);

        return view('pages.frontend.lamar-kerja', compact('job'));
    }

    public function store(Request $request, JobVacancy $job): RedirectResponse
    {
        // Deadline/status dicek lagi saat submit supaya form yang terbuka lama
        // tidak bisa mengirim setelah lowongan resmi ditutup.
        abort_unless($job->fresh()?->isOpen(), 410, 'Pendaftaran untuk posisi ini sudah ditutup.');

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{8,30}$/'],
            'domicile' => ['required', 'string', 'max:120'],
            'last_education' => ['nullable', 'string', 'max:120'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'portfolio_url' => ['nullable', 'url', 'max:500'],
            'cover_letter' => ['nullable', 'string', 'max:3000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'portfolio_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,zip', 'max:10240'],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum valid.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Format nomor WhatsApp belum valid.',
            'domicile.required' => 'Domisili wajib diisi.',
            'cv.required' => 'CV wajib diunggah.',
            'cv.mimes' => 'CV harus berupa PDF, DOC, atau DOCX.',
            'cv.max' => 'Ukuran CV maksimal 5 MB.',
            'portfolio_file.mimes' => 'Portofolio harus berupa PDF, JPG, PNG, atau ZIP.',
            'portfolio_file.max' => 'Ukuran portofolio maksimal 10 MB.',
        ]);

        $folder = 'job-applications/'.$job->id.'/'.Str::uuid();
        $cvPath = null;
        $portfolioPath = null;

        try {
            $cvPath = $request->file('cv')->store($folder, 'local');

            if ($request->hasFile('portfolio_file')) {
                $portfolioPath = $request->file('portfolio_file')->store($folder, 'local');
            }

            DB::transaction(function () use ($validated, $job, $cvPath, $portfolioPath) {
                JobApplication::query()->create([
                    'job_vacancy_id' => $job->id,
                    'job_title' => $job->title,
                    'full_name' => trim($validated['full_name']),
                    'email' => strtolower(trim($validated['email'])),
                    'whatsapp' => trim($validated['whatsapp']),
                    'domicile' => trim($validated['domicile']),
                    'last_education' => trim((string) ($validated['last_education'] ?? '')) ?: null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'portfolio_url' => trim((string) ($validated['portfolio_url'] ?? '')) ?: null,
                    'cover_letter' => trim((string) ($validated['cover_letter'] ?? '')) ?: null,
                    'cv_path' => $cvPath,
                    'portfolio_path' => $portfolioPath,
                    'status' => 'new',
                    'is_read_admin' => false,
                ]);
            });
        } catch (Throwable $e) {
            if ($cvPath) {
                Storage::disk('local')->delete($cvPath);
            }
            if ($portfolioPath) {
                Storage::disk('local')->delete($portfolioPath);
            }
            throw $e;
        }

        return redirect()
            ->route('careers.index')
            ->with('career-application-sent', 'Lamaran untuk posisi '.$job->title.' berhasil dikirim. Tim Karya Ide Edi akan menghubungi Anda jika profil sesuai.');
    }
}
'@

$content_admin_loker = @'
<?php

use App\Models\JobApplication;
use App\Models\JobVacancy;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin-panel')] #[Title('Lowongan Pekerjaan')] class extends Component
{
    public bool $showForm = false;
    public bool $showApplicants = false;
    public ?int $editingId = null;
    public ?int $applicationJobFilter = null;

    public string $title = '';
    public string $department = '';
    public string $employment_type = 'Full-time';
    public string $work_mode = 'On-site';
    public string $location = 'Jepara, Jawa Tengah';
    public string $salary_label = '';
    public string $summary = '';
    public string $description = '';
    public string $requirements = '';
    public string $benefits = '';
    public string $deadline = '';
    public bool $is_active = true;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showApplicants = false;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $job = JobVacancy::query()->findOrFail($id);

        $this->editingId = $job->id;
        $this->title = $job->title;
        $this->department = (string) $job->department;
        $this->employment_type = $job->employment_type;
        $this->work_mode = $job->work_mode;
        $this->location = $job->location;
        $this->salary_label = preg_replace('/\D+/', '', (string) $job->salary_label) ?: '';
        $this->summary = $job->summary;
        $this->description = (string) $job->description;
        $this->requirements = (string) $job->requirements;
        $this->benefits = (string) $job->benefits;
        $this->deadline = $job->deadline?->format('Y-m-d') ?? '';
        $this->is_active = $job->is_active;
        $this->showApplicants = false;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:80'],
            'employment_type' => ['required', 'in:Full-time,Part-time,Contract,Internship,Freelance'],
            'work_mode' => ['required', 'in:On-site,Hybrid,Remote'],
            'location' => ['required', 'string', 'max:120'],
            'salary_label' => ['nullable', 'regex:/^\d{1,15}$/'],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:4000'],
            'requirements' => ['nullable', 'string', 'max:6000'],
            'benefits' => ['nullable', 'string', 'max:4000'],
            'deadline' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ], [
            'title.required' => 'Nama posisi wajib diisi.',
            'summary.required' => 'Ringkasan lowongan wajib diisi.',
            'location.required' => 'Lokasi kerja wajib diisi.',
            'salary_label.regex' => 'Gaji hanya boleh berisi angka.',
        ]);

        $data['department'] = trim($data['department'] ?? '') ?: null;
        $data['salary_label'] = preg_replace('/\D+/', '', trim((string) ($data['salary_label'] ?? ''))) ?: null;
        $data['description'] = trim($data['description'] ?? '') ?: null;
        $data['requirements'] = trim($data['requirements'] ?? '') ?: null;
        $data['benefits'] = trim($data['benefits'] ?? '') ?: null;
        $data['deadline'] = trim($data['deadline'] ?? '') ?: null;

        if ($this->editingId) {
            JobVacancy::query()->findOrFail($this->editingId)->update($data);
            session()->flash('status', 'Lowongan berhasil diperbarui.');
        } else {
            JobVacancy::query()->create($data);
            session()->flash('status', 'Lowongan berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleActive(int $id): void
    {
        $job = JobVacancy::query()->findOrFail($id);
        $job->update(['is_active' => ! $job->is_active]);
    }

    public function delete(int $id): void
    {
        $job = JobVacancy::query()->findOrFail($id);
        $title = $job->title;
        $job->delete();
        session()->flash('status', "Lowongan \"{$title}\" berhasil dihapus. Data pelamar tetap tersimpan.");

        if ($this->editingId === $id) {
            $this->cancelForm();
        }
    }

    public function openApplicants(?int $jobId = null): void
    {
        $this->showForm = false;
        $this->showApplicants = true;
        $this->applicationJobFilter = $jobId;

        $query = JobApplication::query()->unreadAdmin();
        if ($jobId) {
            $query->where('job_vacancy_id', $jobId);
        }
        $query->update(['is_read_admin' => true]);

        $this->dispatch('admin-notifications-updated');
    }

    public function showJobs(): void
    {
        $this->showApplicants = false;
        $this->applicationJobFilter = null;
    }

    public function clearApplicantFilter(): void
    {
        $this->applicationJobFilter = null;
        JobApplication::query()->unreadAdmin()->update(['is_read_admin' => true]);
        $this->dispatch('admin-notifications-updated');
    }

    public function updateApplicationStatus(int $id, string $status): void
    {
        abort_unless(in_array($status, ['new', 'review', 'interview', 'rejected', 'hired'], true), 422);

        JobApplication::query()->findOrFail($id)->update([
            'status' => $status,
            'is_read_admin' => true,
        ]);

        $this->dispatch('admin-notifications-updated');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->department = '';
        $this->employment_type = 'Full-time';
        $this->work_mode = 'On-site';
        $this->location = 'Jepara, Jawa Tengah';
        $this->salary_label = '';
        $this->summary = '';
        $this->description = '';
        $this->requirements = '';
        $this->benefits = '';
        $this->deadline = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function with(): array
    {
        $jobs = JobVacancy::query()
            ->withCount([
                'applications',
                'applications as unread_applications_count' => fn ($query) => $query->where('is_read_admin', false),
            ])
            ->orderByDesc('is_active')
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->latest('id')
            ->get();

        $applications = JobApplication::query()
            ->with('job:id,title')
            ->when($this->applicationJobFilter, fn ($query) => $query->where('job_vacancy_id', $this->applicationJobFilter))
            ->latest()
            ->get();

        return [
            'jobs' => $jobs,
            'applications' => $applications,
            'totalJobs' => $jobs->count(),
            'activeJobs' => $jobs->filter(fn ($job) => $job->isOpen())->count(),
            'draftJobs' => $jobs->where('is_active', false)->count(),
            'unreadApplicants' => JobApplication::query()->unreadAdmin()->count(),
            'totalApplicants' => JobApplication::query()->count(),
        ];
    }
};
?>

<div class="space-y-6">
    @if ($showForm)
        <style>#back-to-top-btn{z-index:20!important}</style>
    @endif

    @if (session('status'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
            <i class="fa-solid fa-circle-check"></i>{{ session('status') }}
        </div>
    @endif

    <section class="relative overflow-hidden rounded-3xl border border-admin-border bg-admin-surface p-6 shadow-sm sm:p-7">
        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-admin-gold/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-admin-accent"><span class="h-px w-8 bg-admin-accent"></span>Career Management</div>
                <h2 class="font-display text-2xl font-semibold text-admin-ink sm:text-3xl">Lowongan Pekerjaan</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-admin-ink-soft">Kelola lowongan dan seluruh lamaran kandidat dari satu tempat. Posisi yang melewati deadline otomatis berhenti tampil di halaman Careers.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="showJobs" class="inline-flex items-center gap-2 rounded-full border border-admin-border px-4 py-2.5 text-sm font-semibold {{ ! $showApplicants ? 'bg-admin-panel text-white' : 'bg-admin-surface text-admin-ink' }}">
                    <i class="fa-solid fa-briefcase text-xs"></i>Lowongan
                </button>
                <button type="button" wire:click="openApplicants" class="relative inline-flex items-center gap-2 rounded-full border border-admin-border px-4 py-2.5 text-sm font-semibold {{ $showApplicants ? 'bg-admin-panel text-white' : 'bg-admin-surface text-admin-ink' }}">
                    <i class="fa-solid fa-user-group text-xs"></i>Pelamar
                    @if ($unreadApplicants > 0)
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">{{ \App\Models\User::formatSidebarBadge($unreadApplicants) }}</span>
                    @endif
                </button>
                @if (! $showApplicants)
                    <button type="button" wire:click="openCreate" class="inline-flex items-center gap-2 rounded-full bg-admin-accent px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-admin-accent/20 transition hover:-translate-y-0.5"><i class="fa-solid fa-plus text-xs"></i>Tambah Lowongan</button>
                @endif
            </div>
        </div>
    </section>

    @if (! $showApplicants)
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([['Total',$totalJobs,'fa-briefcase'],['Tayang',$activeJobs,'fa-signal'],['Draft',$draftJobs,'fa-file-pen']] as [$label,$count,$icon])
                <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm">
                    <div class="flex items-center justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-admin-cream text-admin-accent"><i class="fa-solid {{ $icon }}"></i></span><span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-admin-ink-soft">{{ $label }}</span></div>
                    <p class="mt-5 font-display text-3xl font-semibold text-admin-ink">{{ $count }}</p>
                </div>
            @endforeach
        </div>

        @if ($showForm)
            <form wire:submit="save" class="relative z-60 overflow-hidden rounded-3xl border border-admin-border bg-admin-surface shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-admin-border bg-admin-canvas px-5 py-4 sm:px-6">
                    <div><p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-admin-accent">{{ $editingId ? 'Edit' : 'Posisi Baru' }}</p><h3 class="mt-1 font-display text-lg font-semibold text-admin-ink">{{ $editingId ? 'Perbarui Lowongan' : 'Tambah Lowongan Pekerjaan' }}</h3></div>
                    <button type="button" wire:click="cancelForm" class="flex h-9 w-9 items-center justify-center rounded-full text-admin-ink-soft transition hover:bg-admin-cream"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Nama posisi <span class="text-red-500">*</span></label><input wire:model="title" type="text" placeholder="Contoh: Furniture Designer" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">@error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Divisi / departemen</label><input wire:model="department" type="text" placeholder="Contoh: Produksi" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"></div>

                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Tipe pekerjaan</label><select wire:model="employment_type" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">@foreach (['Full-time','Part-time','Contract','Internship','Freelance'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Sistem kerja</label><select wire:model="work_mode" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">@foreach (['On-site','Hybrid','Remote'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>

                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Lokasi kerja <span class="text-red-500">*</span></label><input wire:model="location" type="text" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">@error('location')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Gaji / kompensasi <span class="text-admin-ink-soft">(opsional)</span></label>
                        <div class="flex overflow-hidden rounded-xl border border-admin-border bg-admin-canvas focus-within:border-admin-accent focus-within:ring-2 focus-within:ring-admin-accent/20"><span class="flex shrink-0 items-center border-r border-admin-border bg-admin-cream px-4 text-sm font-semibold text-admin-accent">Rp</span><input wire:model="salary_label" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="15" autocomplete="off" placeholder="3500000" oninput="this.value=this.value.replace(/\D/g,'')" class="min-w-0 flex-1 bg-transparent px-3.5 py-3 text-sm text-admin-ink outline-none"></div>
                        @error('salary_label')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Ringkasan lowongan <span class="text-red-500">*</span></label><textarea wire:model="summary" rows="3" maxlength="500" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink"></textarea>@error('summary')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Tentang pekerjaan</label><textarea wire:model="description" rows="4" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink"></textarea></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Kualifikasi</label><textarea wire:model="requirements" rows="6" placeholder="Satu poin per baris" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink"></textarea></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Benefit</label><textarea wire:model="benefits" rows="6" placeholder="Satu poin per baris" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink"></textarea></div>
                    <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Batas pendaftaran</label><input wire:model="deadline" type="date" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink"><p class="mt-1.5 text-[11px] text-admin-ink-soft">Setelah tanggal ini terlewati, posisi otomatis hilang dari halaman Careers dan form pendaftaran ditutup.</p></div>

                    <label class="flex items-center gap-3 rounded-2xl border border-admin-border bg-admin-canvas p-4 lg:col-span-2"><input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded border-admin-border text-admin-accent"><span><span class="block text-sm font-semibold text-admin-ink">Tampilkan lowongan di halaman Careers</span><span class="mt-0.5 block text-xs text-admin-ink-soft">Status aktif tetap tunduk pada batas pendaftaran.</span></span></label>
                </div>

                <div class="flex justify-end gap-3 border-t border-admin-border bg-admin-canvas px-5 py-4 sm:px-6"><button type="button" wire:click="cancelForm" class="rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink">Batal</button><button type="submit" class="rounded-full bg-admin-panel px-6 py-2.5 text-sm font-semibold text-white">{{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Lowongan' }}</button></div>
            </form>
        @endif

        <section>
            <div class="mb-4"><h3 class="font-display text-lg font-semibold text-admin-ink">Daftar Lowongan</h3><p class="mt-1 text-xs text-admin-ink-soft">Pelamar tetap tersimpan meskipun posisi nantinya ditutup atau dihapus.</p></div>
            @if ($jobs->isEmpty())
                <div class="rounded-3xl border border-dashed border-admin-border bg-admin-surface px-6 py-14 text-center"><h4 class="font-display text-lg font-semibold text-admin-ink">Belum ada lowongan</h4></div>
            @else
                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach ($jobs as $job)
                        <article wire:key="job-{{ $job->id }}" class="rounded-3xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        @if ($job->isOpen())<span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-emerald-600">Tayang</span>@elseif ($job->isExpired())<span class="rounded-full bg-red-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-red-600">Berakhir</span>@else<span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-amber-600">Draft</span>@endif
                                        @if ($job->department)<span class="text-[11px] text-admin-ink-soft">{{ $job->department }}</span>@endif
                                    </div>
                                    <h4 class="font-display text-xl font-semibold text-admin-ink">{{ $job->title }}</h4>
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-admin-ink-soft">{{ $job->summary }}</p>
                                </div>
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-admin-cream text-admin-accent"><i class="fa-solid fa-briefcase"></i></span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2 text-[11px] text-admin-ink-soft"><span class="rounded-full bg-admin-canvas px-3 py-1.5">{{ $job->employment_type }}</span><span class="rounded-full bg-admin-canvas px-3 py-1.5">{{ $job->location }}</span>@if ($job->salary_label)<span class="rounded-full bg-admin-canvas px-3 py-1.5">Rp {{ number_format((int) $job->salary_label, 0, ',', '.') }}</span>@endif</div>

                            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-admin-border pt-4">
                                <button type="button" wire:click="openApplicants({{ $job->id }})" class="relative rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink"><i class="fa-solid fa-user-group mr-1.5"></i>Pelamar {{ $job->applications_count }}@if ($job->unread_applications_count > 0)<span class="ml-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white">{{ $job->unread_applications_count }}</span>@endif</button>
                                <button type="button" wire:click="edit({{ $job->id }})" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink">Edit</button>
                                <button type="button" wire:click="toggleActive({{ $job->id }})" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink">{{ $job->is_active ? 'Jadikan Draft' : 'Tayangkan' }}</button>
                                <button type="button" wire:click="delete({{ $job->id }})" wire:confirm="Hapus lowongan ini? Data pelamar tetap disimpan." class="ml-auto rounded-full px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-500/10">Hapus</button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <section class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-display text-xl font-semibold text-admin-ink">Pelamar Masuk</h3><p class="mt-1 text-xs text-admin-ink-soft">{{ $applications->count() }} data pelamar {{ $applicationJobFilter ? 'pada posisi terpilih' : 'dari seluruh lowongan' }}.</p></div>
                @if ($applicationJobFilter)<button wire:click="clearApplicantFilter" type="button" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink">Tampilkan Semua Pelamar</button>@endif
            </div>

            @forelse ($applications as $application)
                @php
                    $statusOptions = ['new'=>'Baru','review'=>'Ditinjau','interview'=>'Interview','rejected'=>'Tidak Lanjut','hired'=>'Diterima'];
                @endphp
                <article wire:key="application-{{ $application->id }}" class="rounded-3xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_15rem]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-admin-cream px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-admin-accent">{{ $application->job_title }}</span><span class="text-[11px] text-admin-ink-soft">{{ $application->created_at->translatedFormat('d M Y, H:i') }}</span></div>
                            <h4 class="mt-3 font-display text-xl font-semibold text-admin-ink">{{ $application->full_name }}</h4>
                            <div class="mt-3 grid gap-x-6 gap-y-2 text-sm text-admin-ink-soft sm:grid-cols-2"><p><i class="fa-regular fa-envelope mr-2 text-admin-accent"></i>{{ $application->email }}</p><p><i class="fa-brands fa-whatsapp mr-2 text-admin-accent"></i>{{ $application->whatsapp }}</p><p><i class="fa-solid fa-location-dot mr-2 text-admin-accent"></i>{{ $application->domicile }}</p>@if ($application->last_education)<p><i class="fa-solid fa-graduation-cap mr-2 text-admin-accent"></i>{{ $application->last_education }}</p>@endif</div>
                            @if ($application->cover_letter)<div class="mt-4 rounded-2xl bg-admin-canvas p-4 text-sm leading-7 text-admin-ink-soft">{{ $application->cover_letter }}</div>@endif
                            <div class="mt-4 flex flex-wrap gap-2"><a href="{{ route('admin.jobs.applications.file', ['application'=>$application,'file'=>'cv']) }}" class="rounded-full bg-admin-panel px-4 py-2 text-xs font-semibold text-white"><i class="fa-solid fa-file-arrow-down mr-1.5"></i>Unduh CV</a>@if ($application->portfolio_path)<a href="{{ route('admin.jobs.applications.file', ['application'=>$application,'file'=>'portfolio']) }}" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink"><i class="fa-solid fa-folder-open mr-1.5"></i>Portofolio File</a>@endif @if ($application->portfolio_url)<a href="{{ $application->portfolio_url }}" target="_blank" rel="noopener" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink"><i class="fa-solid fa-arrow-up-right-from-square mr-1.5"></i>Portofolio Link</a>@endif</div>
                        </div>
                        <div class="rounded-2xl bg-admin-canvas p-4">
                            <label class="text-[10px] font-semibold uppercase tracking-[0.18em] text-admin-ink-soft">Status Kandidat</label>
                            <select wire:change="updateApplicationStatus({{ $application->id }}, $event.target.value)" class="mt-2 w-full rounded-xl border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">@foreach ($statusOptions as $value=>$label)<option value="{{ $value }}" @selected($application->status === $value)>{{ $label }}</option>@endforeach</select>
                            @if ($application->experience_years !== null)<p class="mt-4 text-xs leading-5 text-admin-ink-soft">Pengalaman: <strong class="text-admin-ink">{{ $application->experience_years }} tahun</strong></p>@endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-admin-border bg-admin-surface px-6 py-14 text-center"><i class="fa-solid fa-user-group text-2xl text-admin-accent"></i><h4 class="mt-4 font-display text-lg font-semibold text-admin-ink">Belum ada pelamar</h4><p class="mt-2 text-sm text-admin-ink-soft">Lamaran baru akan muncul otomatis di sini.</p></div>
            @endforelse
        </section>
    @endif
</div>
'@

$content_career = @'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Careers | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F7F4EF] font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $careerJobs = \App\Models\JobVacancy::query()
            ->open()
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->latest('id')
            ->get();
        $careerJobCount = $careerJobs->count();
        $splitLines = fn (?string $text) => collect(preg_split('/\r\n|\r|\n/', (string) $text))->map(fn ($line) => trim($line))->filter()->values();
    @endphp

    <main>
        <section class="relative overflow-hidden bg-[#2F1D14] text-white">
            <div class="pointer-events-none absolute -left-24 top-12 h-72 w-72 rounded-full bg-[#B47A45]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-[#D9B27B]/10 blur-3xl"></div>
            <div class="relative mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-28">
                <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
                    <div class="max-w-4xl">
                        <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.36em] text-[#D8AC72] sm:text-xs"><span class="h-px w-10 bg-[#D8AC72]"></span>Careers at Karya Ide Edi</div>
                        <h1 class="mt-7 font-display text-5xl font-semibold leading-[1.02] tracking-[-0.035em] sm:text-6xl lg:text-7xl">Bertumbuh bersama orang-orang yang peduli pada detail.</h1>
                        <p class="mt-7 max-w-2xl text-base leading-8 text-white/68 sm:text-lg">Temukan posisi yang sedang dibuka dan kirimkan lamaran langsung melalui website. Setiap lamaran masuk ke dashboard admin Karya Ide Edi.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/6 p-6 backdrop-blur-sm"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#D3A064] text-[#2F1D14]"><i class="fa-solid fa-briefcase"></i></span><p class="mt-6 text-[10px] font-semibold uppercase tracking-[0.28em] text-white/45">Posisi terbuka</p><p class="mt-2 font-display text-4xl font-semibold">{{ $careerJobCount }}</p><p class="mt-2 text-sm leading-6 text-white/55">Lowongan yang masih menerima pendaftaran.</p></div>
                </div>
            </div>
        </section>

        @if (session('career-application-sent'))
            <section class="bg-[#F7F4EF] pt-8">
                <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                    <div class="flex items-start gap-3 rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm leading-6 text-emerald-800"><span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white"><i class="fa-solid fa-check text-xs"></i></span><div><strong>Lamaran berhasil dikirim.</strong><p class="mt-0.5">{{ session('career-application-sent') }}</p></div></div>
                </div>
            </section>
        @endif

        @if ($careerJobs->isNotEmpty())
            <section class="bg-[#F7F4EF] py-16 sm:py-20 lg:py-24">
                <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                    <div class="flex flex-col gap-5 border-b border-[#DED1C1] pb-8 sm:flex-row sm:items-end sm:justify-between">
                        <div><p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-[#A96D37]">Open Positions</p><h2 class="mt-3 font-display text-3xl font-semibold text-[#3D2B1F] sm:text-4xl">Lowongan Pekerjaan</h2><p class="mt-3 max-w-2xl text-sm leading-7 text-[#75695D]">Klik posisi yang sesuai, pelajari detailnya, lalu kirim CV dan portofolio langsung dari formulir pendaftaran.</p></div>
                        <div class="inline-flex w-fit items-center gap-2 rounded-full border border-[#D9C8B4] bg-white px-4 py-2 text-xs font-semibold text-[#6F5132]"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ $careerJobCount }} posisi dibuka</div>
                    </div>

                    <div class="mt-8 space-y-5">
                        @foreach ($careerJobs as $index => $job)
                            @php
                                $requirements = $splitLines($job->requirements);
                                $benefits = $splitLines($job->benefits);
                            @endphp
                            <article class="overflow-hidden rounded-4xl border border-[#E1D5C7] bg-white shadow-[0_18px_55px_-42px_rgba(61,43,31,0.45)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_70px_-40px_rgba(61,43,31,0.56)]">
                                <div class="grid lg:grid-cols-[minmax(0,1fr)_19rem]">
                                    <div class="p-6 sm:p-8">
                                        <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-[#F1E7DB] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#8A5A31]">{{ $job->department ?: 'Karya Ide Edi' }}</span>@if ($index === 0)<span class="rounded-full bg-[#2F1D14] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white">New</span>@endif</div>
                                        <h3 class="mt-4 font-display text-3xl font-semibold leading-tight text-[#352319] sm:text-4xl">{{ $job->title }}</h3>
                                        <p class="mt-4 max-w-3xl text-sm leading-7 text-[#706458] sm:text-[15px]">{{ $job->summary }}</p>

                                        <div class="mt-5 flex flex-wrap gap-2 text-[11px] font-semibold text-[#75695D]"><span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-regular fa-clock mr-1.5 text-[#AD7542]"></i>{{ $job->employment_type }}</span><span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-solid fa-location-dot mr-1.5 text-[#AD7542]"></i>{{ $job->location }}</span><span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-solid fa-building mr-1.5 text-[#AD7542]"></i>{{ $job->work_mode }}</span></div>

                                        <details class="group/detail mt-6 border-t border-[#E9DFD3] pt-5">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-[#4B3323]"><span>Lihat detail posisi</span><span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#F3E9DD] text-[#8D5A30] transition group-open/detail:rotate-45"><i class="fa-solid fa-plus text-xs"></i></span></summary>
                                            <div class="mt-5 grid gap-6 md:grid-cols-2">
                                                @if ($job->description)<div class="md:col-span-2"><h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Tentang Pekerjaan</h4><p class="mt-2 whitespace-pre-line text-sm leading-7 text-[#706458]">{{ $job->description }}</p></div>@endif
                                                @if ($requirements->isNotEmpty())<div><h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Kualifikasi</h4><ul class="mt-3 space-y-2.5">@foreach ($requirements as $requirement)<li class="flex gap-3 text-sm leading-6 text-[#706458]"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#B57B43]"></span><span>{{ $requirement }}</span></li>@endforeach</ul></div>@endif
                                                @if ($benefits->isNotEmpty())<div><h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Yang Anda Dapatkan</h4><ul class="mt-3 space-y-2.5">@foreach ($benefits as $benefit)<li class="flex gap-2.5 text-sm leading-6 text-[#706458]"><i class="fa-solid fa-check mt-1 text-[10px] text-[#A96D37]"></i><span>{{ $benefit }}</span></li>@endforeach</ul></div>@endif
                                            </div>
                                        </details>
                                    </div>

                                    <aside class="flex flex-col justify-between border-t border-[#E9DFD3] bg-[#FCFAF7] p-6 sm:p-7 lg:border-l lg:border-t-0">
                                        <div class="space-y-4">
                                            @if ($job->salary_label)<div><p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Kompensasi</p><p class="mt-1 text-lg font-semibold text-[#493224]">Rp {{ number_format((int) $job->salary_label, 0, ',', '.') }}</p></div>@endif
                                            @if ($job->deadline)<div><p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Batas Pendaftaran</p><p class="mt-1 text-sm font-semibold text-[#493224]">{{ $job->deadline->translatedFormat('d F Y') }}</p><p class="mt-1 text-xs leading-5 text-[#8A7B6D]">Posisi otomatis ditutup setelah tanggal ini.</p></div>@endif
                                        </div>
                                        <a href="{{ route('careers.apply', $job) }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-[#3B2518] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#3B2518]/15 transition hover:-translate-y-0.5 hover:bg-[#6F4527]"><i class="fa-solid fa-paper-plane text-xs"></i>Daftar Sekarang</a>
                                    </aside>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            <section class="bg-[#F7F4EF] py-20 sm:py-24"><div class="mx-auto max-w-4xl px-6 text-center sm:px-8"><div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-[#3B2518] text-[#DDB17B]"><i class="fa-solid fa-briefcase text-xl"></i></div><p class="mt-7 text-[11px] font-semibold uppercase tracking-[0.3em] text-[#A96D37]">Belum Ada Posisi Terbuka</p><h2 class="mt-4 font-display text-4xl font-semibold text-[#3D2B1F] sm:text-5xl">Belum ada lowongan saat ini</h2><p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-[#75695D]">Lowongan yang sudah melewati batas pendaftaran otomatis tidak ditampilkan. Silakan cek kembali di lain waktu.</p></div></section>
        @endif
    </main>

    @include('partials.frontend.footer')
</body>
</html>
'@

$content_apply = @'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lamar {{ $job->title }} | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F7F4EF] font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    <main class="py-14 sm:py-18 lg:py-20">
        <div class="mx-auto max-w-6xl px-6 sm:px-8 lg:px-10">
            <a href="{{ route('careers.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#795333] transition hover:text-[#3B2518]"><i class="fa-solid fa-arrow-left text-xs"></i>Kembali ke Careers</a>

            <div class="mt-7 grid overflow-hidden rounded-4xl border border-[#E0D4C6] bg-white shadow-[0_28px_80px_-50px_rgba(61,43,31,0.5)] lg:grid-cols-[20rem_minmax(0,1fr)]">
                <aside class="bg-[#2F1D14] p-7 text-white sm:p-8">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.32em] text-[#D7AA71]">Posisi yang Dilamar</p>
                    <h1 class="mt-4 font-display text-3xl font-semibold leading-tight">{{ $job->title }}</h1>
                    <p class="mt-4 text-sm leading-7 text-white/65">{{ $job->summary }}</p>

                    <div class="mt-7 space-y-3 border-t border-white/10 pt-6 text-sm text-white/70"><p><i class="fa-solid fa-building mr-2 text-[#D7AA71]"></i>{{ $job->department ?: 'Karya Ide Edi' }}</p><p><i class="fa-solid fa-location-dot mr-2 text-[#D7AA71]"></i>{{ $job->location }}</p><p><i class="fa-regular fa-clock mr-2 text-[#D7AA71]"></i>{{ $job->employment_type }} · {{ $job->work_mode }}</p>@if ($job->deadline)<p><i class="fa-regular fa-calendar mr-2 text-[#D7AA71]"></i>Ditutup {{ $job->deadline->translatedFormat('d F Y') }}</p>@endif</div>

                    <div class="mt-8 rounded-3xl bg-white/7 p-5 text-xs leading-6 text-white/60"><i class="fa-solid fa-shield-halved mr-1.5 text-[#D7AA71]"></i>CV dan portofolio dikirim langsung ke sistem internal Karya Ide Edi dan tidak ditampilkan secara publik.</div>
                </aside>

                <section class="p-6 sm:p-8 lg:p-10">
                    <div><p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#A96D37]">Form Pendaftaran</p><h2 class="mt-2 font-display text-3xl font-semibold text-[#3D2B1F]">Kirim Lamaran Anda</h2><p class="mt-2 text-sm leading-7 text-[#75695D]">Nama posisi sudah terisi otomatis. Lengkapi data kandidat dan dokumen pendukung di bawah.</p></div>

                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><strong>Periksa kembali data berikut:</strong><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif

                    <form action="{{ route('careers.apply.store', $job) }}" method="POST" enctype="multipart/form-data" class="mt-7 grid gap-5 sm:grid-cols-2">
                        @csrf
                        <div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">Nama lengkap *</label><input name="full_name" value="{{ old('full_name') }}" required class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">Email *</label><input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">WhatsApp *</label><input name="whatsapp" value="{{ old('whatsapp') }}" placeholder="08xxxxxxxxxx" required class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">Domisili *</label><input name="domicile" value="{{ old('domicile') }}" required class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">Pendidikan terakhir</label><input name="last_education" value="{{ old('last_education') }}" placeholder="Contoh: SMK / S1 Desain Interior" class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37]"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">Pengalaman kerja (tahun)</label><input name="experience_years" type="number" min="0" max="60" value="{{ old('experience_years') }}" class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37]"></div>
                        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-[#493224]">Link portofolio <span class="font-normal text-[#8A7B6D]">(opsional)</span></label><input name="portfolio_url" type="url" value="{{ old('portfolio_url') }}" placeholder="https://..." class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37]"></div>
                        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-[#493224]">Perkenalan singkat</label><textarea name="cover_letter" rows="5" maxlength="3000" placeholder="Ceritakan pengalaman yang paling relevan, alasan tertarik, atau hal lain yang perlu kami ketahui." class="w-full resize-none rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm leading-7 outline-none focus:border-[#A96D37]">{{ old('cover_letter') }}</textarea></div>

                        <div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">CV *</label><div class="rounded-2xl border border-dashed border-[#D7C6B2] bg-[#FCFAF7] p-4"><input name="cv" type="file" accept=".pdf,.doc,.docx" required class="block w-full text-xs text-[#75695D] file:mr-3 file:rounded-full file:border-0 file:bg-[#3B2518] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"><p class="mt-2 text-[11px] text-[#8A7B6D]">PDF/DOC/DOCX, maksimal 5 MB.</p></div></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">Portofolio file</label><div class="rounded-2xl border border-dashed border-[#D7C6B2] bg-[#FCFAF7] p-4"><input name="portfolio_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.zip" class="block w-full text-xs text-[#75695D] file:mr-3 file:rounded-full file:border-0 file:bg-[#8A5A31] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"><p class="mt-2 text-[11px] text-[#8A7B6D]">PDF/JPG/PNG/ZIP, maksimal 10 MB.</p></div></div>

                        <div class="sm:col-span-2 flex flex-col gap-3 border-t border-[#E8DED2] pt-5 sm:flex-row sm:items-center sm:justify-between"><p class="max-w-xl text-xs leading-6 text-[#8A7B6D]">Dengan mengirim formulir ini, Anda menyatakan data yang diberikan benar dan dapat digunakan untuk proses rekrutmen posisi <strong>{{ $job->title }}</strong>.</p><button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-[#3B2518] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#3B2518]/15 transition hover:-translate-y-0.5 hover:bg-[#6F4527]"><i class="fa-solid fa-paper-plane text-xs"></i>Kirim Lamaran</button></div>
                    </form>
                </section>
            </div>
        </div>
    </main>

    @include('partials.frontend.footer')
</body>
</html>
'@

Write-NoBom ".\app\Models\JobVacancy.php" $content_job_vacancy
Write-Host "  - .\app\Models\JobVacancy.php" -ForegroundColor DarkGray

Write-NoBom ".\app\Models\JobApplication.php" $content_job_application
Write-Host "  - .\app\Models\JobApplication.php" -ForegroundColor DarkGray

Write-NoBom ".\database\migrations\2026_09_24_000002_create_job_applications_table.php" $content_migration
Write-Host "  - .\database\migrations\2026_09_24_000002_create_job_applications_table.php" -ForegroundColor DarkGray

Write-NoBom ".\app\Http\Controllers\JobApplicationController.php" $content_controller
Write-Host "  - .\app\Http\Controllers\JobApplicationController.php" -ForegroundColor DarkGray

Write-NoBom ".\resources\views\pages\admin\loker.blade.php" $content_admin_loker
Write-Host "  - .\resources\views\pages\admin\loker.blade.php" -ForegroundColor DarkGray

Write-NoBom ".\resources\views\pages\frontend\karier.blade.php" $content_career
Write-Host "  - .\resources\views\pages\frontend\karier.blade.php" -ForegroundColor DarkGray

Write-NoBom ".\resources\views\pages\frontend\lamar-kerja.blade.php" $content_apply
Write-Host "  - .\resources\views\pages\frontend\lamar-kerja.blade.php" -ForegroundColor DarkGray

Step "[3/8] Menghubungkan route, sidebar, badge Pelamar, dan notifikasi frontend ..."

$patch_existing = @'
<?php
$root = $argv[1] ?? getcwd();

function file_text(string $root, string $rel): string {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $text = file_get_contents($path);
    if ($text === false) throw new RuntimeException("Gagal membaca {$rel}");
    return $text;
}
function save_text(string $root, string $rel, string $text): void {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (file_put_contents($path, $text) === false) throw new RuntimeException("Gagal menulis {$rel}");
}
function replace_once(string $text, string $search, string $replace, string $label): string {
    if (str_contains($text, $replace)) return $text;
    if (!str_contains($text, $search)) throw new RuntimeException("Anchor tidak ditemukan: {$label}");
    return preg_replace('/'.preg_quote($search, '/').'/', addcslashes($replace, '\\$'), $text, 1) ?? throw new RuntimeException("Replace gagal: {$label}");
}

// routes/web.php
$rel = 'routes/web.php';
$text = file_text($root, $rel);
$publicAnchor = "Route::view('/karier', 'pages.frontend.karier')->name('careers.index');";
$publicAddition = $publicAnchor."\nRoute::get('/karier/lamar/{job}', [\\App\\Http\\Controllers\\JobApplicationController::class, 'create'])->name('careers.apply');\nRoute::post('/karier/lamar/{job}', [\\App\\Http\\Controllers\\JobApplicationController::class, 'store'])->name('careers.apply.store');";
if (!str_contains($text, "name('careers.apply.store')")) {
    if (!str_contains($text, $publicAnchor)) throw new RuntimeException('Route careers.index tidak ditemukan.');
    $text = str_replace($publicAnchor, $publicAddition, $text);
}

if (!str_contains($text, "->name('jobs');")) {
    $anchor = "    Route::livewire('/pelanggan', 'pages::admin.pelanggan')->name('customers');";
    if (!str_contains($text, $anchor)) throw new RuntimeException('Anchor route pelanggan tidak ditemukan.');
    $text = str_replace($anchor, $anchor."\n    Route::livewire('/loker', 'pages::admin.loker')->name('jobs');", $text);
}

if (!str_contains($text, "name('jobs.applications.file')")) {
    $anchor = "    Route::livewire('/loker', 'pages::admin.loker')->name('jobs');";
    if (!str_contains($text, $anchor)) throw new RuntimeException('Route admin.jobs tidak ditemukan.');
    $download = <<<'ROUTE'

    Route::get('/loker/pelamar/{application}/file/{file}', function (\App\Models\JobApplication $application, string $file) {
        $path = match ($file) {
            'cv' => $application->cv_path,
            'portfolio' => $application->portfolio_path,
            default => null,
        };

        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $downloadName = \Illuminate\Support\Str::slug($application->full_name).'-'.$file.($extension ? '.'.$extension : '');

        return \Illuminate\Support\Facades\Storage::disk('local')->download($path, $downloadName);
    })->whereIn('file', ['cv', 'portfolio'])->name('jobs.applications.file');
ROUTE;
    $text = str_replace($anchor, $anchor.$download, $text);
}
save_text($root, $rel, $text);

// Sidebar: ensure Loker item and applicant badge.
$rel = 'resources/views/layouts/admin-panel.blade.php';
$text = file_text($root, $rel);
if (!str_contains($text, "\$navItem('admin.jobs'")) {
    $anchor = "                                \$navItem('admin.customers', 'fa-users', 'Pelanggan'),";
    if (!str_contains($text, $anchor)) throw new RuntimeException('Anchor sidebar Pelanggan tidak ditemukan.');
    $text = str_replace($anchor, $anchor."\n                                \$navItem('admin.jobs', 'fa-briefcase', 'Loker'),", $text);
}
if (!str_contains($text, 'type="pelamar"')) {
    $anchor = <<<'BLADE'
                                    @elseif ($item['route'] === 'admin.transactions')
                                        <livewire:admin.nav-badge type="pesanan" :active="$item['active']" :key="'nav-badge-pesanan'" />
                                    @endif
BLADE;
    $replacement = <<<'BLADE'
                                    @elseif ($item['route'] === 'admin.transactions')
                                        <livewire:admin.nav-badge type="pesanan" :active="$item['active']" :key="'nav-badge-pesanan'" />
                                    @elseif ($item['route'] === 'admin.jobs')
                                        <livewire:admin.nav-badge type="pelamar" :active="$item['active']" :key="'nav-badge-pelamar'" />
                                    @endif
BLADE;
    if (!str_contains($text, $anchor)) throw new RuntimeException('Anchor badge sidebar Pesanan tidak ditemukan.');
    $text = str_replace($anchor, $replacement, $text);
}
save_text($root, $rel, $text);

// nav-badge: add applicant source.
$rel = 'resources/views/components/admin/nav-badge.blade.php';
$text = file_text($root, $rel);
if (!str_contains($text, "'pelamar' =>")) {
    $anchor = "            'dashboard' => \$user->unreadDashboardCount(),";
    if (!str_contains($text, $anchor)) throw new RuntimeException('Anchor nav badge dashboard tidak ditemukan.');
    $text = str_replace($anchor, $anchor."\n            'pelamar' => \\App\\Models\\JobApplication::query()->unreadAdmin()->count(),", $text);
}
save_text($root, $rel, $text);

// Notification bell frontend: add job applications without touching User model.
$rel = 'resources/views/components/notification-bell.blade.php';
$text = file_text($root, $rel);
if (!str_contains($text, 'unreadJobApplications')) {
    $old = "        return \$user ? \$user->unreadNotificationsCount() : 0;";
    $new = "        \$unreadJobApplications = \\App\\Models\\JobApplication::query()->unreadAdmin()->count();\n\n        return \$user ? \$user->unreadNotificationsCount() + \$unreadJobApplications : 0;";
    if (!str_contains($text, $old)) throw new RuntimeException('Anchor total notification bell tidak ditemukan.');
    $text = str_replace($old, $new, $text);
}
if (!str_contains($text, "'label' => 'Pelamar Baru'")) {
    $anchor = "            ['icon' => 'fa-triangle-exclamation', 'label' => 'Stok Menipis', 'count' => \$user->unreadDashboardCount()],";
    if (!str_contains($text, $anchor)) throw new RuntimeException('Anchor breakdown notifikasi tidak ditemukan.');
    $text = str_replace($anchor, $anchor."\n            ['icon' => 'fa-user-plus', 'label' => 'Pelamar Baru', 'count' => \\App\\Models\\JobApplication::query()->unreadAdmin()->count()],", $text);
}
save_text($root, $rel, $text);

echo "PATCH_EXISTING_OK\n";
'@

$tmpPatch = Join-Path $env:TEMP "patch-career-existing-$stamp.php"
[System.IO.File]::WriteAllText($tmpPatch, $patch_existing, $utf8NoBom)
php $tmpPatch (Get-Location).Path
if ($LASTEXITCODE -ne 0) {
    Remove-Item $tmpPatch -Force -ErrorAction SilentlyContinue
    throw "Patch route/sidebar/notifikasi gagal. Restore tersedia di: $backupDir"
}
Remove-Item $tmpPatch -Force

Step "[4/8] Validasi syntax PHP ..."
$phpFiles = @(
    ".\app\Models\JobVacancy.php",
    ".\app\Models\JobApplication.php",
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\database\migrations\2026_09_24_000002_create_job_applications_table.php",
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\frontend\lamar-kerja.blade.php",
    ".\resources\views\layouts\admin-panel.blade.php",
    ".\resources\views\components\admin\nav-badge.blade.php",
    ".\resources\views\components\notification-bell.blade.php",
    ".\routes\web.php"
)

foreach ($file in $phpFiles) {
    php -l $file
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Restore tersedia di: $backupDir"
    }
}

Step "[5/8] Menjalankan migration Job Applications ..."
php artisan migrate
if ($LASTEXITCODE -ne 0) {
    throw "Migration gagal. Source backup tersedia di: $backupDir"
}

Step "[6/8] Membersihkan cache Laravel ..."
php artisan optimize:clear | Out-Host

Step "[7/8] Mengecek route Career dan Loker ..."
php artisan route:list --name=careers.apply | Out-Host
php artisan route:list --name=admin.jobs | Out-Host

Step "[8/8] Selesai ..."
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Yang sekarang aktif:" -ForegroundColor Yellow
Write-Host "  - Pesan WhatsApp otomatis di form admin DIHAPUS." -ForegroundColor White
Write-Host "  - Tombol Daftar membuka form lamaran internal sesuai posisi." -ForegroundColor White
Write-Host "  - Nama pekerjaan otomatis berasal dari lowongan yang dipilih." -ForegroundColor White
Write-Host "  - Kandidat mengirim data, CV wajib, portofolio/link opsional." -ForegroundColor White
Write-Host "  - CV/portofolio file disimpan private, hanya admin yang bisa unduh." -ForegroundColor White
Write-Host "  - Deadline lewat = lowongan hilang dari Careers + pendaftaran ditutup." -ForegroundColor White
Write-Host "  - Admin Loker punya tombol Pelamar + badge lingkaran unread." -ForegroundColor White
Write-Host "  - Sidebar Loker ikut punya badge pelamar baru." -ForegroundColor White
Write-Host "  - Ikon admin/notifikasi di frontend ikut menghitung Pelamar Baru." -ForegroundColor White
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
