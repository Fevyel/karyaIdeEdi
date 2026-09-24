$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$routes = ".\routes\web.php"
$sidebar = ".\resources\views\layouts\admin-panel.blade.php"
$front = ".\resources\views\pages\frontend\karier.blade.php"
$model = ".\app\Models\JobVacancy.php"
$migration = ".\database\migrations\2026_09_24_000001_create_job_vacancies_table.php"
$adminPage = ".\resources\views\pages\admin\loker.blade.php"

foreach ($required in @($routes, $sidebar, $front)) {
    if (-not (Test-Path $required)) {
        throw "File project tidak ditemukan: $required"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-loker-$stamp"

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Fitur Loker Admin + Halaman Careers Dinamis" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/7] Membuat backup file yang akan disentuh ..."
New-Item -ItemType Directory -Path "$backupDir\routes" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\resources\views\layouts" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
Copy-Item $routes "$backupDir\routes\web.php" -Force
Copy-Item $sidebar "$backupDir\resources\views\layouts\admin-panel.blade.php" -Force
Copy-Item $front "$backupDir\resources\views\pages\frontend\karier.blade.php" -Force

foreach ($maybeExisting in @($model, $migration, $adminPage)) {
    if (Test-Path $maybeExisting) {
        $leaf = Split-Path $maybeExisting -Leaf
        Copy-Item $maybeExisting (Join-Path $backupDir $leaf) -Force
    }
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/7] Menyiapkan Model, Migration, Admin Loker, dan halaman Careers ..."

$content_f0 = @'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        'whatsapp_message',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'is_active' => 'boolean',
        ];
    }

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
}

'@

$target = ".\app\\Models\\JobVacancy.php"
if ((Test-Path $target) -and ($target -ne $front)) {
    $existing = Get-Content $target -Raw
    if ($existing -notmatch 'KIE_JOB_VACANCIES_FEATURE' -and $target -notmatch 'loker\.blade\.php$' -and $target -notmatch '2026_09_24_000001_create_job_vacancies_table\.php$') {
        throw "File target sudah ada dan bukan file fitur Loker buatan patch ini: $target. Tidak ditimpa untuk menjaga project."
    }
}
$parent = Split-Path $target -Parent
if ($parent -and -not (Test-Path $parent)) { New-Item -ItemType Directory -Path $parent -Force | Out-Null }
Set-Content -Path $target -Value $content_f0 -Encoding UTF8
Write-Host "  - $target" -ForegroundColor DarkGray
$content_f1 = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('department', 80)->nullable();
            $table->string('employment_type', 40)->default('Full-time');
            $table->string('work_mode', 40)->default('On-site');
            $table->string('location', 120)->default('Jepara, Jawa Tengah');
            $table->string('salary_label', 120)->nullable();
            $table->string('summary', 500);
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->date('deadline')->nullable();
            $table->string('whatsapp_message', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_vacancies');
    }
};

'@

$target = ".\database\\migrations\\2026_09_24_000001_create_job_vacancies_table.php"
if ((Test-Path $target) -and ($target -ne $front)) {
    $existing = Get-Content $target -Raw
    if ($existing -notmatch 'KIE_JOB_VACANCIES_FEATURE' -and $target -notmatch 'loker\.blade\.php$' -and $target -notmatch '2026_09_24_000001_create_job_vacancies_table\.php$') {
        throw "File target sudah ada dan bukan file fitur Loker buatan patch ini: $target. Tidak ditimpa untuk menjaga project."
    }
}
$parent = Split-Path $target -Parent
if ($parent -and -not (Test-Path $parent)) { New-Item -ItemType Directory -Path $parent -Force | Out-Null }
Set-Content -Path $target -Value $content_f1 -Encoding UTF8
Write-Host "  - $target" -ForegroundColor DarkGray
$content_f2 = @'
<?php

use App\Models\JobVacancy;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin-panel')] #[Title('Lowongan Pekerjaan')] class extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

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
    public string $whatsapp_message = '';
    public bool $is_active = true;

    public function openCreate(): void
    {
        $this->resetForm();
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
        $this->salary_label = (string) $job->salary_label;
        $this->summary = $job->summary;
        $this->description = (string) $job->description;
        $this->requirements = (string) $job->requirements;
        $this->benefits = (string) $job->benefits;
        $this->deadline = $job->deadline?->format('Y-m-d') ?? '';
        $this->whatsapp_message = (string) $job->whatsapp_message;
        $this->is_active = $job->is_active;
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
            'salary_label' => ['nullable', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:4000'],
            'requirements' => ['nullable', 'string', 'max:6000'],
            'benefits' => ['nullable', 'string', 'max:4000'],
            'deadline' => ['nullable', 'date'],
            'whatsapp_message' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ], [
            'title.required' => 'Nama posisi wajib diisi.',
            'summary.required' => 'Ringkasan lowongan wajib diisi.',
            'location.required' => 'Lokasi kerja wajib diisi.',
        ]);

        $data['department'] = trim($data['department'] ?? '') ?: null;
        $data['salary_label'] = trim($data['salary_label'] ?? '') ?: null;
        $data['description'] = trim($data['description'] ?? '') ?: null;
        $data['requirements'] = trim($data['requirements'] ?? '') ?: null;
        $data['benefits'] = trim($data['benefits'] ?? '') ?: null;
        $data['deadline'] = trim($data['deadline'] ?? '') ?: null;
        $data['whatsapp_message'] = trim($data['whatsapp_message'] ?? '') ?: null;

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
        session()->flash('status', "Lowongan \"{$title}\" berhasil dihapus.");

        if ($this->editingId === $id) {
            $this->cancelForm();
        }
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
        $this->whatsapp_message = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function with(): array
    {
        $jobs = JobVacancy::query()
            ->orderByDesc('is_active')
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->latest('id')
            ->get();

        return [
            'jobs' => $jobs,
            'totalJobs' => $jobs->count(),
            'activeJobs' => $jobs->where('is_active', true)->filter(fn ($job) => ! $job->isExpired())->count(),
            'draftJobs' => $jobs->where('is_active', false)->count(),
        ];
    }
};
?>

<div class="space-y-6">
    @if (session('status'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('status') }}
        </div>
    @endif

    <section class="relative overflow-hidden rounded-3xl border border-admin-border bg-admin-surface p-6 shadow-sm sm:p-7">
        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-admin-gold/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-admin-accent">
                    <span class="h-px w-8 bg-admin-accent"></span>
                    Career Management
                </div>
                <h2 class="font-display text-2xl font-semibold text-admin-ink sm:text-3xl">Lowongan Pekerjaan</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-admin-ink-soft">
                    Kelola posisi yang sedang dibuka. Lowongan aktif otomatis tampil di halaman Careers dan kandidat diarahkan ke WhatsApp toko untuk melamar.
                </p>
            </div>

            <button
                type="button"
                wire:click="openCreate"
                class="inline-flex items-center justify-center gap-2 rounded-full bg-admin-panel px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition hover:-translate-y-0.5 hover:bg-admin-accent-strong"
            >
                <i class="fa-solid fa-plus text-xs"></i>
                Tambah Lowongan
            </button>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-admin-cream text-admin-accent"><i class="fa-solid fa-briefcase"></i></span>
                <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-admin-ink-soft">Total</span>
            </div>
            <p class="mt-5 font-display text-3xl font-semibold text-admin-ink">{{ $totalJobs }}</p>
            <p class="mt-1 text-xs text-admin-ink-soft">Lowongan tersimpan</p>
        </div>
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-signal"></i></span>
                <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-admin-ink-soft">Tayang</span>
            </div>
            <p class="mt-5 font-display text-3xl font-semibold text-admin-ink">{{ $activeJobs }}</p>
            <p class="mt-1 text-xs text-admin-ink-soft">Sedang tampil di Careers</p>
        </div>
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><i class="fa-solid fa-file-pen"></i></span>
                <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-admin-ink-soft">Draft</span>
            </div>
            <p class="mt-5 font-display text-3xl font-semibold text-admin-ink">{{ $draftJobs }}</p>
            <p class="mt-1 text-xs text-admin-ink-soft">Belum ditampilkan</p>
        </div>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="overflow-hidden rounded-3xl border border-admin-border bg-admin-surface shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-admin-border bg-admin-canvas px-5 py-4 sm:px-6">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-admin-accent">{{ $editingId ? 'Edit' : 'Posisi Baru' }}</p>
                    <h3 class="mt-1 font-display text-lg font-semibold text-admin-ink">{{ $editingId ? 'Perbarui Lowongan' : 'Tambah Lowongan Pekerjaan' }}</h3>
                </div>
                <button type="button" wire:click="cancelForm" class="flex h-9 w-9 items-center justify-center rounded-full text-admin-ink-soft transition hover:bg-admin-cream hover:text-admin-ink">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Nama posisi <span class="text-red-500">*</span></label>
                    <input wire:model="title" type="text" placeholder="Contoh: Tukang Kayu / Furniture Maker" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                    @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Divisi / departemen</label>
                    <input wire:model="department" type="text" placeholder="Contoh: Produksi" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Tipe pekerjaan</label>
                        <select wire:model="employment_type" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                            @foreach (['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Sistem kerja</label>
                        <select wire:model="work_mode" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                            @foreach (['On-site', 'Hybrid', 'Remote'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Lokasi kerja <span class="text-red-500">*</span></label>
                    <input wire:model="location" type="text" placeholder="Jepara, Jawa Tengah" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                    @error('location')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Gaji / kompensasi <span class="text-admin-ink-soft">(opsional)</span></label>
                    <input wire:model="salary_label" type="text" placeholder="Contoh: Rp3–5 juta / bulan atau Kompetitif" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Ringkasan lowongan <span class="text-red-500">*</span></label>
                    <textarea wire:model="summary" rows="3" maxlength="500" placeholder="Jelaskan posisi ini secara singkat dan menarik." class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"></textarea>
                    @error('summary')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Tentang pekerjaan</label>
                    <textarea wire:model="description" rows="4" placeholder="Ceritakan tanggung jawab utama, suasana kerja, atau tujuan posisi ini." class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"></textarea>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Kualifikasi</label>
                    <textarea wire:model="requirements" rows="6" placeholder="Tulis satu poin per baris, contoh:&#10;Berpengalaman minimal 1 tahun&#10;Teliti dan bertanggung jawab&#10;Mampu membaca gambar kerja" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"></textarea>
                    <p class="mt-1.5 text-[11px] text-admin-ink-soft">Setiap baris akan tampil sebagai satu poin di halaman Careers.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Benefit / yang didapat</label>
                    <textarea wire:model="benefits" rows="6" placeholder="Tulis satu poin per baris, contoh:&#10;Lingkungan kerja kekeluargaan&#10;Bonus berdasarkan performa&#10;Kesempatan belajar langsung" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"></textarea>
                    <p class="mt-1.5 text-[11px] text-admin-ink-soft">Opsional. Bisa dikosongkan jika belum ada.</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Batas lamaran</label>
                    <input wire:model="deadline" type="date" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                    <p class="mt-1.5 text-[11px] text-admin-ink-soft">Kosongkan jika lowongan berlaku sampai ditutup manual.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Pesan WhatsApp otomatis</label>
                    <input wire:model="whatsapp_message" type="text" placeholder="Contoh: Halo, saya tertarik melamar posisi {posisi}." class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                    <p class="mt-1.5 text-[11px] text-admin-ink-soft">Gunakan <span class="font-semibold">{posisi}</span> jika ingin nama posisi terisi otomatis.</p>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-admin-border bg-admin-canvas p-4 lg:col-span-2">
                    <input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                    <span>
                        <span class="block text-sm font-semibold text-admin-ink">Tampilkan lowongan di halaman Careers</span>
                        <span class="mt-0.5 block text-xs text-admin-ink-soft">Matikan jika masih draft atau ingin menutup lowongan tanpa menghapus datanya.</span>
                    </span>
                </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-admin-border bg-admin-canvas px-5 py-4 sm:px-6">
                <button type="button" wire:click="cancelForm" class="rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink transition hover:bg-admin-cream">Batal</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center gap-2 rounded-full bg-admin-panel px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-admin-panel/20 transition hover:bg-admin-accent-strong disabled:opacity-60">
                    <span wire:loading.remove wire:target="save"><i class="fa-solid fa-floppy-disk mr-1.5 text-xs"></i>{{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Lowongan' }}</span>
                    <span wire:loading wire:target="save"><i class="fa-solid fa-circle-notch mr-1.5 animate-spin"></i>Menyimpan...</span>
                </button>
            </div>
        </form>
    @endif

    <section>
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h3 class="font-display text-lg font-semibold text-admin-ink">Daftar Lowongan</h3>
                <p class="mt-1 text-xs text-admin-ink-soft">Kelola status, edit isi, atau hapus posisi yang sudah tidak diperlukan.</p>
            </div>
        </div>

        @if ($jobs->isEmpty())
            <div class="rounded-3xl border border-dashed border-admin-border bg-admin-surface px-6 py-14 text-center shadow-sm">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-admin-cream text-admin-accent"><i class="fa-solid fa-briefcase text-lg"></i></span>
                <h4 class="mt-5 font-display text-lg font-semibold text-admin-ink">Belum ada lowongan</h4>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-admin-ink-soft">Tambahkan posisi pertama saat Karya Ide Edi mulai membuka kesempatan bergabung.</p>
                <button wire:click="openCreate" type="button" class="mt-5 inline-flex items-center gap-2 rounded-full bg-admin-panel px-5 py-2.5 text-sm font-semibold text-white"><i class="fa-solid fa-plus text-xs"></i>Tambah Lowongan</button>
            </div>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($jobs as $job)
                    <article wire:key="job-{{ $job->id }}" class="overflow-hidden rounded-3xl border border-admin-border bg-admin-surface shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        @if ($job->is_active && ! $job->isExpired())
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-600"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Tayang</span>
                                        @elseif ($job->isExpired())
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-red-600"><i class="fa-solid fa-clock text-[9px]"></i>Berakhir</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-amber-600"><i class="fa-solid fa-file-pen text-[9px]"></i>Draft</span>
                                        @endif
                                        @if ($job->department)<span class="text-[11px] font-medium text-admin-ink-soft">{{ $job->department }}</span>@endif
                                    </div>
                                    <h4 class="font-display text-xl font-semibold text-admin-ink">{{ $job->title }}</h4>
                                </div>
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-admin-cream text-admin-accent"><i class="fa-solid fa-briefcase"></i></span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-medium text-admin-ink-soft">
                                <span class="rounded-full bg-admin-canvas px-3 py-1.5"><i class="fa-solid fa-clock mr-1.5 text-admin-accent"></i>{{ $job->employment_type }}</span>
                                <span class="rounded-full bg-admin-canvas px-3 py-1.5"><i class="fa-solid fa-location-dot mr-1.5 text-admin-accent"></i>{{ $job->location }}</span>
                                <span class="rounded-full bg-admin-canvas px-3 py-1.5"><i class="fa-solid fa-building mr-1.5 text-admin-accent"></i>{{ $job->work_mode }}</span>
                            </div>

                            <p class="mt-4 line-clamp-3 text-sm leading-relaxed text-admin-ink-soft">{{ $job->summary }}</p>

                            <div class="mt-5 flex items-center justify-between gap-3 border-t border-admin-border pt-4">
                                <div class="text-xs text-admin-ink-soft">
                                    @if ($job->deadline)
                                        <i class="fa-regular fa-calendar mr-1.5"></i>Batas {{ $job->deadline->translatedFormat('d M Y') }}
                                    @else
                                        <i class="fa-solid fa-infinity mr-1.5"></i>Sampai ditutup
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" wire:click="toggleActive({{ $job->id }})" class="flex h-9 w-9 items-center justify-center rounded-full text-admin-ink-soft transition hover:bg-admin-cream hover:text-admin-accent" title="{{ $job->is_active ? 'Jadikan draft' : 'Tayangkan' }}">
                                        <i class="fa-solid {{ $job->is_active ? 'fa-eye' : 'fa-eye-slash' }} text-xs"></i>
                                    </button>
                                    <button type="button" wire:click="edit({{ $job->id }})" class="flex h-9 w-9 items-center justify-center rounded-full text-admin-ink-soft transition hover:bg-admin-cream hover:text-admin-accent" title="Edit">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <button type="button" wire:click="delete({{ $job->id }})" wire:confirm="Hapus lowongan '{{ $job->title }}'?" class="flex h-9 w-9 items-center justify-center rounded-full text-admin-ink-soft transition hover:bg-red-500/10 hover:text-red-600" title="Hapus">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>

'@

$target = ".\resources\\views\\pages\\admin\\loker.blade.php"
if ((Test-Path $target) -and ($target -ne $front)) {
    $existing = Get-Content $target -Raw
    if ($existing -notmatch 'KIE_JOB_VACANCIES_FEATURE' -and $target -notmatch 'loker\.blade\.php$' -and $target -notmatch '2026_09_24_000001_create_job_vacancies_table\.php$') {
        throw "File target sudah ada dan bukan file fitur Loker buatan patch ini: $target. Tidak ditimpa untuk menjaga project."
    }
}
$parent = Split-Path $target -Parent
if ($parent -and -not (Test-Path $parent)) { New-Item -ItemType Directory -Path $parent -Force | Out-Null }
Set-Content -Path $target -Value $content_f2 -Encoding UTF8
Write-Host "  - $target" -ForegroundColor DarkGray
$content_f3 = @'
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
        $careersSetting = \App\Models\Setting::current();
        $careersWaNumber = $careersSetting->whatsappDigits();
        $careerJobs = \App\Models\JobVacancy::query()->open()->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')->orderBy('deadline')->latest('id')->get();
        $careerJobCount = $careerJobs->count();
        $splitLines = fn (?string $text) => collect(preg_split('/\r\n|\r|\n/', (string) $text))->map(fn ($line) => trim($line))->filter()->values();
    @endphp

    <main>
        <section class="relative overflow-hidden bg-[#2F1D14] text-white">
            <div class="pointer-events-none absolute -left-24 top-12 h-72 w-72 rounded-full bg-[#B47A45]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-[#D9B27B]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute right-10 top-12 h-72 w-72 rounded-full border border-white/6"></div>
            <div class="pointer-events-none absolute right-24 top-24 h-56 w-56 rounded-full border border-white/5"></div>

            <div class="relative mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-28">
                <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
                    <div class="max-w-4xl">
                        <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.36em] text-[#D8AC72] sm:text-xs">
                            <span class="h-px w-10 bg-[#D8AC72]"></span>
                            Careers at Karya Ide Edi
                        </div>
                        <h1 class="mt-7 font-display text-5xl font-semibold leading-[1.02] tracking-[-0.035em] sm:text-6xl lg:text-7xl">
                            Karya yang baik dimulai dari orang-orang yang peduli pada detail.
                        </h1>
                        <p class="mt-7 max-w-2xl text-base leading-8 text-white/68 sm:text-lg">
                            Temukan kesempatan untuk tumbuh bersama tim Karya Ide Edi. Kami mencari orang yang mau bekerja dengan teliti, bertanggung jawab, dan bangga pada hasil kerjanya.
                        </p>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/6 p-6 backdrop-blur-sm">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#D3A064] text-[#2F1D14] shadow-lg"><i class="fa-solid fa-briefcase"></i></span>
                        <p class="mt-6 text-[10px] font-semibold uppercase tracking-[0.28em] text-white/45">Posisi terbuka</p>
                        <p class="mt-2 font-display text-4xl font-semibold">{{ $careerJobCount }}</p>
                        <p class="mt-2 text-sm leading-6 text-white/55">Lowongan yang sedang menerima lamaran saat ini.</p>
                    </div>
                </div>
            </div>
        </section>

        @if ($careerJobs->isNotEmpty())
            <section class="bg-[#F7F4EF] py-16 sm:py-20 lg:py-24">
                <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                    <div class="flex flex-col gap-5 border-b border-[#DED1C1] pb-8 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-[#A96D37]">Open Positions</p>
                            <h2 class="mt-3 font-display text-3xl font-semibold text-[#3D2B1F] sm:text-4xl">Lowongan Pekerjaan</h2>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#75695D]">Pilih posisi yang paling sesuai dengan pengalaman dan minat Anda. Detail kualifikasi tersedia di setiap kartu.</p>
                        </div>
                        <div class="inline-flex w-fit items-center gap-2 rounded-full border border-[#D9C8B4] bg-white px-4 py-2 text-xs font-semibold text-[#6F5132] shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            {{ $careerJobCount }} posisi sedang dibuka
                        </div>
                    </div>

                    <div class="mt-8 grid gap-6 lg:grid-cols-2">
                        @foreach ($careerJobs as $index => $job)
                            @php
                                $requirements = $splitLines($job->requirements);
                                $benefits = $splitLines($job->benefits);
                                $waTemplate = $job->whatsapp_message ?: 'Halo, saya tertarik melamar posisi {posisi} di Karya Ide Edi.';
                                $waMessage = str_replace('{posisi}', $job->title, $waTemplate);
                                $applyUrl = $careersWaNumber ? 'https://wa.me/'.$careersWaNumber.'?text='.rawurlencode($waMessage) : route('booking.index');
                            @endphp

                            <article class="group overflow-hidden rounded-4xl border border-[#E1D5C7] bg-white shadow-[0_18px_55px_-42px_rgba(61,43,31,0.45)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_70px_-40px_rgba(61,43,31,0.56)]">
                                <div class="p-6 sm:p-7">
                                    <div class="flex items-start justify-between gap-5">
                                        <div class="min-w-0">
                                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-[#F1E7DB] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#8A5A31]">{{ $job->department ?: 'Karya Ide Edi' }}</span>
                                                @if ($index === 0)<span class="rounded-full bg-[#2F1D14] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white">New</span>@endif
                                            </div>
                                            <h3 class="font-display text-2xl font-semibold leading-tight text-[#352319] sm:text-[1.8rem]">{{ $job->title }}</h3>
                                        </div>
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#3B2518] text-[#DAB078] shadow-lg shadow-[#3B2518]/15"><i class="fa-solid fa-arrow-up-right-dots"></i></span>
                                    </div>

                                    <div class="mt-5 flex flex-wrap gap-2 text-[11px] font-semibold text-[#75695D]">
                                        <span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-regular fa-clock mr-1.5 text-[#AD7542]"></i>{{ $job->employment_type }}</span>
                                        <span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-solid fa-location-dot mr-1.5 text-[#AD7542]"></i>{{ $job->location }}</span>
                                        <span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-solid fa-building mr-1.5 text-[#AD7542]"></i>{{ $job->work_mode }}</span>
                                    </div>

                                    <p class="mt-5 text-sm leading-7 text-[#706458]">{{ $job->summary }}</p>

                                    @if ($job->salary_label || $job->deadline)
                                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                            @if ($job->salary_label)
                                                <div class="rounded-2xl bg-[#F7F1E9] px-4 py-3">
                                                    <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Kompensasi</p>
                                                    <p class="mt-1 text-sm font-semibold text-[#493224]">{{ $job->salary_label }}</p>
                                                </div>
                                            @endif
                                            @if ($job->deadline)
                                                <div class="rounded-2xl bg-[#F7F1E9] px-4 py-3">
                                                    <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Batas Lamaran</p>
                                                    <p class="mt-1 text-sm font-semibold text-[#493224]">{{ $job->deadline->translatedFormat('d F Y') }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <details class="group/detail mt-6 border-t border-[#E9DFD3] pt-5">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-[#4B3323]">
                                            <span>Lihat detail posisi</span>
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#F3E9DD] text-[#8D5A30] transition group-open/detail:rotate-45"><i class="fa-solid fa-plus text-xs"></i></span>
                                        </summary>

                                        <div class="mt-5 space-y-5">
                                            @if ($job->description)
                                                <div>
                                                    <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Tentang Pekerjaan</h4>
                                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-[#706458]">{{ $job->description }}</p>
                                                </div>
                                            @endif

                                            @if ($requirements->isNotEmpty())
                                                <div>
                                                    <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Kualifikasi</h4>
                                                    <ul class="mt-3 space-y-2.5">
                                                        @foreach ($requirements as $requirement)
                                                            <li class="flex gap-3 text-sm leading-6 text-[#706458]"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#B57B43]"></span><span>{{ $requirement }}</span></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            @if ($benefits->isNotEmpty())
                                                <div>
                                                    <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Yang Anda Dapatkan</h4>
                                                    <ul class="mt-3 grid gap-2.5 sm:grid-cols-2">
                                                        @foreach ($benefits as $benefit)
                                                            <li class="flex gap-2.5 rounded-xl bg-[#FAF7F2] px-3.5 py-3 text-sm leading-6 text-[#706458]"><i class="fa-solid fa-check mt-1 text-[10px] text-[#A96D37]"></i><span>{{ $benefit }}</span></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                </div>

                                <div class="flex flex-col gap-3 border-t border-[#E9DFD3] bg-[#FCFAF7] px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                                    <p class="text-xs leading-5 text-[#8A7B6D]">Siapkan CV atau perkenalan singkat sebelum menghubungi kami.</p>
                                    <a href="{{ $applyUrl }}" @if ($careersWaNumber) target="_blank" rel="noopener" @endif class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-[#3B2518] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#3B2518]/15 transition hover:-translate-y-0.5 hover:bg-[#6F4527]">
                                        <i class="fa-brands fa-whatsapp"></i>
                                        Lamar Posisi
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            <section class="bg-[#F7F4EF] py-20 sm:py-24">
                <div class="mx-auto max-w-4xl px-6 text-center sm:px-8">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-[#3B2518] text-[#DDB17B] shadow-xl shadow-[#3B2518]/15"><i class="fa-solid fa-briefcase text-xl"></i></div>
                    <p class="mt-7 text-[11px] font-semibold uppercase tracking-[0.3em] text-[#A96D37]">Belum Ada Posisi Terbuka</p>
                    <h2 class="mt-4 font-display text-4xl font-semibold text-[#3D2B1F] sm:text-5xl">Belum menemukan posisi yang tepat?</h2>
                    <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-[#75695D]">Saat ini belum ada lowongan aktif. Anda tetap boleh memperkenalkan diri dan mengirimkan CV untuk dipertimbangkan ketika kesempatan baru tersedia.</p>
                    <a href="{{ $careersWaNumber ? 'https://wa.me/'.$careersWaNumber.'?text='.rawurlencode('Halo Karya Ide Edi, saya ingin memperkenalkan diri dan mengirimkan CV untuk kesempatan kerja di masa mendatang.') : route('booking.index') }}" @if ($careersWaNumber) target="_blank" rel="noopener" @endif class="mt-8 inline-flex items-center gap-2 rounded-full bg-[#3B2518] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#3B2518]/15 transition hover:-translate-y-0.5 hover:bg-[#6F4527]">
                        <i class="fa-brands fa-whatsapp"></i>
                        Kirim CV Spontan
                    </a>
                </div>
            </section>
        @endif
    </main>

    @include('partials.frontend.footer')
</body>
</html>

'@

$target = ".\resources\\views\\pages\\frontend\\karier.blade.php"
if ((Test-Path $target) -and ($target -ne $front)) {
    $existing = Get-Content $target -Raw
    if ($existing -notmatch 'KIE_JOB_VACANCIES_FEATURE' -and $target -notmatch 'loker\.blade\.php$' -and $target -notmatch '2026_09_24_000001_create_job_vacancies_table\.php$') {
        throw "File target sudah ada dan bukan file fitur Loker buatan patch ini: $target. Tidak ditimpa untuk menjaga project."
    }
}
$parent = Split-Path $target -Parent
if ($parent -and -not (Test-Path $parent)) { New-Item -ItemType Directory -Path $parent -Force | Out-Null }
Set-Content -Path $target -Value $content_f3 -Encoding UTF8
Write-Host "  - $target" -ForegroundColor DarkGray

Step "[3/7] Menambahkan route Admin > Loker ..."
$routeText = Get-Content $routes -Raw

if ($routeText -notmatch "name\('jobs'\)") {
    $needleRoute = "    Route::livewire('/edit-web', 'pages::admin.edit-web')->name('website-editor');"
    if (-not $routeText.Contains($needleRoute)) {
        throw "Anchor route Edit Web tidak ditemukan. Berhenti agar routes\web.php tidak dipatch secara ngawur."
    }

    $routeText = $routeText.Replace(
        $needleRoute,
        $needleRoute + "`r`n    Route::livewire('/loker', 'pages::admin.loker')->name('jobs');"
    )
    Set-Content -Path $routes -Value $routeText -Encoding UTF8
    Write-Host "  - Route /admin/loker ditambahkan." -ForegroundColor Green
} else {
    Write-Host "  - Route admin.jobs sudah ada, dilewati." -ForegroundColor DarkGray
}

Step "[4/7] Menambahkan tombol Loker ke sidebar ..."
$sidebarText = Get-Content $sidebar -Raw

if ($sidebarText -notmatch "admin\.jobs") {
    $needleSidebar = "                                `$navItem('admin.customers', 'fa-users', 'Pelanggan'),"
    if (-not $sidebarText.Contains($needleSidebar)) {
        throw "Anchor menu Pelanggan tidak ditemukan. Berhenti agar sidebar tidak diubah di lokasi yang salah."
    }

    $sidebarText = $sidebarText.Replace(
        $needleSidebar,
        $needleSidebar + "`r`n                                `$navItem('admin.jobs', 'fa-briefcase', 'Loker'),"
    )
    Set-Content -Path $sidebar -Value $sidebarText -Encoding UTF8
    Write-Host "  - Tombol Loker ditambahkan setelah Pelanggan." -ForegroundColor Green
} else {
    Write-Host "  - Tombol Loker sudah ada, dilewati." -ForegroundColor DarkGray
}

Step "[5/7] Validasi syntax ..."
php -l $model | Out-Host
php -l $migration | Out-Host
php -l $adminPage | Out-Host
php -l $front | Out-Host
php -l $routes | Out-Host
php -l $sidebar | Out-Host

Step "[6/7] Menjalankan migration tabel job_vacancies ..."
php artisan migrate
if ($LASTEXITCODE -ne 0) {
    throw "Migration gagal. Backup source tersedia di $backupDir"
}

Step "[7/7] Membersihkan cache dan cek route ..."
php artisan view:clear | Out-Host
php artisan route:clear | Out-Host
php artisan route:list --name=admin.jobs | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Fitur baru:" -ForegroundColor Yellow
Write-Host "  Sidebar Admin > Loker" -ForegroundColor White
Write-Host "  Judul halaman: Lowongan Pekerjaan" -ForegroundColor White
Write-Host "  CRUD lowongan + Draft/Tayang + Deadline + WhatsApp" -ForegroundColor White
Write-Host "  Frontend /karier otomatis membaca lowongan aktif dari database" -ForegroundColor White
Write-Host ""
Write-Host "Edit Web tidak disentuh." -ForegroundColor DarkGray
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
