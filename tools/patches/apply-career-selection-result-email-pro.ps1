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
$backupDir = ".backup-career-selection-system-$stamp"

$backupFiles = @(
    ".\app\Models\JobVacancy.php",
    ".\app\Models\JobApplication.php",
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\app\Services\CareerSelectionService.php",
    ".\app\Console\Commands\FinalizeCareerSelections.php",
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\frontend\hasil-lamaran.blade.php",
    ".\resources\views\emails\career-decision.blade.php",
    ".\routes\web.php",
    ".\routes\console.php",
    ".\resources\views\components\admin\nav-badge.blade.php",
    ".\resources\views\layouts\admin-panel.blade.php",
    ".\resources\views\components\notification-bell.blade.php"
)

Write-Host "======================================================================" -ForegroundColor Yellow
Write-Host " Career Pro - Hasil Pelamar + Masa Seleksi + Email Otomatis" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Yellow

Step "[1/8] Backup file yang akan disentuh ..."
foreach ($item in $backupFiles) {
    if (Test-Path $item) {
        $relative = $item.TrimStart('.', '\')
        $dest = Join-Path $backupDir $relative
        $destDir = Split-Path $dest -Parent
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        Copy-Item $item $dest -Force
    }
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/8] Menulis model, controller, service, command, dan migration ..."

$jobVacancyContent = @'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Lowongan kerja yang dikelola dari Admin > Loker.
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
        'selection_end',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'selection_end' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
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

    public function isOpen(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function selectionStartDate(): ?Carbon
    {
        return $this->deadline?->copy()->addDay();
    }

    public function isSelectionActive(): bool
    {
        $start = $this->selectionStartDate();

        if (! $start || ! $this->selection_end) {
            return false;
        }

        return today()->gte($start) && today()->lte($this->selection_end);
    }

    public function isSelectionFinished(): bool
    {
        return $this->selection_end?->isBefore(today()) ?? false;
    }
}
'@

$jobApplicationContent = @'
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
        'cv_original_name',
        'portfolio_original_name',
        'public_token',
        'status',
        'is_read_admin',
        'decided_at',
        'decision_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'is_read_admin' => 'boolean',
            'decided_at' => 'datetime',
            'decision_notified_at' => 'datetime',
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'accepted' => 'Diterima',
            'rejected' => 'Tidak Lolos',
            default => 'Pending',
        };
    }
}
'@

$migrationContent = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_vacancies') && ! Schema::hasColumn('job_vacancies', 'selection_end')) {
            Schema::table('job_vacancies', function (Blueprint $table) {
                $table->date('selection_end')->nullable()->after('deadline')->index();
            });
        }

        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                if (! Schema::hasColumn('job_applications', 'public_token')) {
                    $table->string('public_token', 80)->nullable()->unique()->after('portfolio_path');
                }
                if (! Schema::hasColumn('job_applications', 'cv_original_name')) {
                    $table->string('cv_original_name', 255)->nullable()->after('cv_path');
                }
                if (! Schema::hasColumn('job_applications', 'portfolio_original_name')) {
                    $table->string('portfolio_original_name', 255)->nullable()->after('portfolio_path');
                }
                if (! Schema::hasColumn('job_applications', 'decided_at')) {
                    $table->timestamp('decided_at')->nullable()->after('is_read_admin');
                }
                if (! Schema::hasColumn('job_applications', 'decision_notified_at')) {
                    $table->timestamp('decision_notified_at')->nullable()->after('decided_at');
                }
            });

            // Drive-only tidak memiliki CV lokal.
            if (Schema::hasColumn('job_applications', 'cv_path')) {
                Schema::table('job_applications', function (Blueprint $table) {
                    $table->string('cv_path', 500)->nullable()->change();
                });
            }

            // Normalisasi status lama.
            DB::table('job_applications')
                ->whereIn('status', ['new', 'review', 'interview'])
                ->update(['status' => 'pending']);

            DB::table('job_applications')
                ->where('status', 'hired')
                ->update(['status' => 'accepted']);

            // Semua lamaran lama diberi token hasil.
            DB::table('job_applications')
                ->whereNull('public_token')
                ->orderBy('id')
                ->pluck('id')
                ->each(function ($id) {
                    DB::table('job_applications')
                        ->where('id', $id)
                        ->update(['public_token' => Str::random(64)]);
                });
        }
    }

    public function down(): void
    {
        // Data hasil seleksi tidak dipaksa dihapus saat rollback.
    }
};
'@

$serviceContent = @'
<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CareerSelectionService
{
    public function accept(JobApplication $application): bool
    {
        if ($application->status === 'accepted') {
            return $this->sendDecisionEmail($application);
        }

        $application->forceFill([
            'status' => 'accepted',
            'is_read_admin' => true,
            'decided_at' => now(),
            'decision_notified_at' => null,
        ])->save();

        return $this->sendDecisionEmail($application->fresh(['job']));
    }

    public function finalizeExpiredSelections(): int
    {
        $count = 0;

        JobApplication::query()
            ->where('status', 'pending')
            ->whereHas('job', function ($query) {
                $query
                    ->whereNotNull('selection_end')
                    ->whereDate('selection_end', '<', today());
            })
            ->with('job')
            ->chunkById(100, function ($applications) use (&$count) {
                foreach ($applications as $application) {
                    $application->forceFill([
                        'status' => 'rejected',
                        'decided_at' => now(),
                        'decision_notified_at' => null,
                    ])->save();

                    $this->sendDecisionEmail($application->fresh(['job']));
                    $count++;
                }
            });

        return $count;
    }

    public function retryUnsentDecisionEmails(): int
    {
        $count = 0;

        JobApplication::query()
            ->whereIn('status', ['accepted', 'rejected'])
            ->whereNull('decision_notified_at')
            ->with('job')
            ->chunkById(100, function ($applications) use (&$count) {
                foreach ($applications as $application) {
                    if ($this->sendDecisionEmail($application)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function sendDecisionEmail(JobApplication $application): bool
    {
        if ($application->decision_notified_at) {
            return true;
        }

        try {
            $setting = Setting::current();
            $statusAccepted = $application->status === 'accepted';
            $subject = $statusAccepted
                ? 'Selamat! Lamaran '.$application->job_title.' Anda diterima'
                : 'Hasil Seleksi Lamaran '.$application->job_title;

            $html = view('emails.career-decision', [
                'application' => $application,
                'accepted' => $statusAccepted,
                'siteName' => $setting->site_name,
            ])->render();

            Mail::html($html, function ($message) use ($application, $setting, $subject) {
                $message
                    ->to($application->email, $application->full_name)
                    ->subject($subject);

                if ($setting->email) {
                    $message->replyTo($setting->email, $setting->site_name);
                }
            });

            $application->forceFill([
                'decision_notified_at' => now(),
            ])->save();

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
'@

$commandContent = @'
<?php

namespace App\Console\Commands;

use App\Services\CareerSelectionService;
use Illuminate\Console\Command;

class FinalizeCareerSelections extends Command
{
    protected $signature = 'career:finalize-selections';

    protected $description = 'Menutup masa seleksi career, menolak kandidat yang belum diterima, dan mengirim email hasil seleksi.';

    public function handle(CareerSelectionService $service): int
    {
        $rejected = $service->finalizeExpiredSelections();
        $retried = $service->retryUnsentDecisionEmails();

        $this->info("Seleksi selesai: {$rejected} kandidat otomatis tidak lolos.");
        $this->info("Email keputusan berhasil dikirim/diulang: {$retried}.");

        return self::SUCCESS;
    }
}
'@

$controllerContent = @'
<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobVacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        abort_unless($job->fresh()?->isOpen(), 410, 'Pendaftaran untuk posisi ini sudah ditutup.');

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'whatsapp' => ['required', 'regex:/^\d{8,15}$/'],
            'domicile' => ['required', 'string', 'max:120'],
            'last_education' => ['nullable', 'string', 'max:120'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'portfolio_url' => [
                'nullable',
                'required_without_all:cv_file,portfolio_file',
                'url',
                'max:500',
                'regex:/^https?:\/\/(?:drive|docs)\.google\.com\//i',
            ],
            'cover_letter' => ['nullable', 'string', 'max:3000'],
            'cv_file' => [
                'nullable',
                'required_without:portfolio_url',
                'file',
                'mimes:pdf,doc,docx',
                'max:5120',
            ],
            'portfolio_file' => [
                'nullable',
                'required_without:portfolio_url',
                'file',
                'mimes:pdf,jpg,jpeg,png,zip',
                'max:10240',
            ],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum valid.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Nomor WhatsApp harus berisi 8-15 digit angka.',
            'domicile.required' => 'Domisili wajib diisi.',
            'cv_file.required_without' => 'Upload CV wajib jika tidak menggunakan Google Drive.',
            'cv_file.mimes' => 'CV harus berupa PDF, DOC, atau DOCX.',
            'cv_file.max' => 'Ukuran CV maksimal 5 MB.',
            'portfolio_file.required_without' => 'Upload portofolio wajib jika tidak menggunakan Google Drive.',
            'portfolio_file.mimes' => 'Portofolio harus berupa PDF, JPG, PNG, atau ZIP.',
            'portfolio_file.max' => 'Ukuran portofolio maksimal 10 MB.',
            'portfolio_url.required_without_all' => 'Upload CV + portofolio, atau isi link Google Drive yang berisi keduanya.',
            'portfolio_url.url' => 'Link Google Drive harus berupa URL yang valid.',
            'portfolio_url.regex' => 'Link harus berasal dari Google Drive.',
        ]);

        $email = strtolower(trim($validated['email']));

        // Hindari lamaran ganda untuk posisi yang sama dari email yang sama.
        $existing = JobApplication::query()
            ->where('job_vacancy_id', $job->id)
            ->where('email', $email)
            ->latest('id')
            ->first();

        if ($existing) {
            if (! $existing->public_token) {
                $existing->forceFill(['public_token' => Str::random(64)])->save();
            }

            $this->rememberApplication($existing);

            return redirect()
                ->route('careers.application.result', [$existing, $existing->public_token])
                ->with('career-info', 'Lamaran Anda untuk posisi ini sudah tersimpan. Berikut hasil dan data yang pernah Anda kirim.');
        }

        $driveUrl = trim((string) ($validated['portfolio_url'] ?? '')) ?: null;
        $hasCv = $request->hasFile('cv_file');
        $hasPortfolio = $request->hasFile('portfolio_file');

        if (! $driveUrl && (! $hasCv || ! $hasPortfolio)) {
            return back()
                ->withInput()
                ->withErrors([
                    'documents' => 'Pilih salah satu metode: upload CV + portofolio, atau isi link Google Drive yang berisi keduanya.',
                ]);
        }

        $folder = 'job-applications/'.$job->id.'/'.Str::uuid();
        $cvPath = null;
        $portfolioPath = null;
        $application = null;

        try {
            if ($hasCv) {
                $cvPath = $request->file('cv_file')->store($folder, 'local');
            }

            if ($hasPortfolio) {
                $portfolioPath = $request->file('portfolio_file')->store($folder, 'local');
            }

            DB::transaction(function () use (
                &$application,
                $validated,
                $email,
                $driveUrl,
                $job,
                $cvPath,
                $portfolioPath,
                $request,
                $hasCv,
                $hasPortfolio
            ) {
                $application = JobApplication::query()->create([
                    'job_vacancy_id' => $job->id,
                    'job_title' => $job->title,
                    'full_name' => trim($validated['full_name']),
                    'email' => $email,
                    'whatsapp' => preg_replace('/\D+/', '', trim($validated['whatsapp'])),
                    'domicile' => trim($validated['domicile']),
                    'last_education' => trim((string) ($validated['last_education'] ?? '')) ?: null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'portfolio_url' => $driveUrl,
                    'cover_letter' => trim((string) ($validated['cover_letter'] ?? '')) ?: null,
                    'cv_path' => $cvPath,
                    'portfolio_path' => $portfolioPath,
                    'cv_original_name' => $hasCv ? $request->file('cv_file')->getClientOriginalName() : null,
                    'portfolio_original_name' => $hasPortfolio ? $request->file('portfolio_file')->getClientOriginalName() : null,
                    'public_token' => Str::random(64),
                    'status' => 'pending',
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

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'submission' => 'Lamaran belum berhasil tersimpan. Silakan coba kembali.',
                ]);
        }

        $this->rememberApplication($application);

        return redirect()
            ->route('careers.application.result', [$application, $application->public_token])
            ->with('career-application-sent', 'Lamaran berhasil dikirim dan sudah masuk ke sistem rekrutmen Karya Ide Edi.');
    }

    public function result(JobApplication $application, string $token): View
    {
        $this->guardPublicToken($application, $token);

        return view('pages.frontend.hasil-lamaran', [
            'application' => $application->load('job'),
        ]);
    }

    public function file(JobApplication $application, string $token, string $type): BinaryFileResponse|StreamedResponse
    {
        $this->guardPublicToken($application, $token);

        $path = match ($type) {
            'cv' => $application->cv_path,
            'portfolio' => $application->portfolio_path,
            default => null,
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $originalName = match ($type) {
            'cv' => $application->cv_original_name ?: 'CV',
            'portfolio' => $application->portfolio_original_name ?: 'Portofolio',
        };

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return response()->file(Storage::disk('local')->path($path), [
                'Content-Disposition' => 'inline; filename="'.addslashes($originalName).'"',
            ]);
        }

        return Storage::disk('local')->download($path, $originalName);
    }

    private function rememberApplication(JobApplication $application): void
    {
        Cookie::queue(cookie(
            'career_application_'.$application->job_vacancy_id,
            $application->public_token,
            60 * 24 * 365,
            '/',
            null,
            request()->isSecure(),
            true,
            false,
            'Lax'
        ));
    }

    private function guardPublicToken(JobApplication $application, string $token): void
    {
        abort_unless(
            $application->public_token
            && hash_equals($application->public_token, $token),
            404
        );
    }
}
'@

$emailContent = @'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Seleksi {{ $application->job_title }}</title>
</head>
<body style="margin:0;background:#f6f2ec;font-family:Arial,sans-serif;color:#3d2b1f;">
    <div style="max-width:620px;margin:0 auto;padding:36px 20px;">
        <div style="background:#ffffff;border:1px solid #eadfd2;border-radius:24px;overflow:hidden;">
            <div style="padding:28px 30px;background:#2f1d14;color:#ffffff;">
                <div style="font-size:12px;letter-spacing:3px;text-transform:uppercase;color:#d9ad76;">{{ $siteName }}</div>
                <h1 style="margin:12px 0 0;font-size:28px;line-height:1.25;">
                    {{ $accepted ? 'Selamat, Anda diterima.' : 'Hasil seleksi telah tersedia.' }}
                </h1>
            </div>
            <div style="padding:30px;">
                <p style="margin:0 0 16px;line-height:1.8;">Halo <strong>{{ $application->full_name }}</strong>,</p>

                @if ($accepted)
                    <p style="margin:0 0 18px;line-height:1.8;">
                        Setelah proses seleksi, kami memilih Anda untuk melanjutkan bersama Karya Ide Edi pada posisi
                        <strong>{{ $application->job_title }}</strong>.
                    </p>
                    <p style="margin:0 0 18px;line-height:1.8;">
                        Tim kami akan menghubungi Anda untuk informasi tahap berikutnya.
                    </p>
                @else
                    <p style="margin:0 0 18px;line-height:1.8;">
                        Masa seleksi untuk posisi <strong>{{ $application->job_title }}</strong> telah selesai.
                        Pada kesempatan ini Anda belum terpilih untuk melanjutkan ke tahap berikutnya.
                    </p>
                    <p style="margin:0 0 18px;line-height:1.8;">
                        Terima kasih atas waktu dan ketertarikan Anda kepada Karya Ide Edi.
                    </p>
                @endif

                <a
                    href="{{ route('careers.application.result', [$application, $application->public_token]) }}"
                    style="display:inline-block;margin-top:8px;background:#3b2518;color:#ffffff;text-decoration:none;padding:13px 20px;border-radius:999px;font-weight:700;font-size:14px;"
                >
                    Lihat Hasil Lamaran
                </a>
            </div>
        </div>
    </div>
</body>
</html>
'@

$resultContent = @'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Lamaran | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F7F4EF] font-sans text-[#2A211B] antialiased">
@include('partials.frontend.navbar')

@php
    $status = $application->status === 'accepted'
        ? ['label'=>'Diterima','class'=>'bg-emerald-100 text-emerald-700','icon'=>'fa-circle-check']
        : ($application->status === 'rejected'
            ? ['label'=>'Tidak Lolos','class'=>'bg-red-100 text-red-700','icon'=>'fa-circle-xmark']
            : ['label'=>'Pending','class'=>'bg-amber-100 text-amber-700','icon'=>'fa-clock']);

    $job = $application->job;
@endphp

<main class="bg-[#F7F4EF] py-14 sm:py-18">
    <div class="mx-auto max-w-6xl px-6 sm:px-8">
        @if (session('career-application-sent') || session('career-info'))
            <div class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm leading-6 text-emerald-800">
                <i class="fa-solid fa-circle-check mr-2"></i>
                {{ session('career-application-sent') ?: session('career-info') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-4xl border border-[#E2D6C8] bg-white shadow-[0_24px_70px_-45px_rgba(61,43,31,.45)]">
            <div class="grid lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section class="p-6 sm:p-8 lg:p-10">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-xs font-semibold {{ $status['class'] }}">
                            <i class="fa-solid {{ $status['icon'] }}"></i>{{ $status['label'] }}
                        </span>
                        <span class="text-xs text-[#8A7B6D]">Dikirim {{ $application->created_at->translatedFormat('d F Y, H:i') }}</span>
                    </div>

                    <p class="mt-7 text-[10px] font-semibold uppercase tracking-[0.26em] text-[#A96D37]">Status Lamaran</p>
                    <h1 class="mt-3 font-display text-4xl font-semibold leading-tight text-[#352319] sm:text-5xl">{{ $application->job_title }}</h1>

                    @if ($application->status === 'pending')
                        <p class="mt-5 max-w-3xl text-sm leading-7 text-[#706458]">
                            Lamaran sudah diterima sistem dan saat ini berstatus <strong>Pending</strong>.
                            Admin akan melakukan seleksi setelah pendaftaran ditutup.
                        </p>
                    @elseif ($application->status === 'accepted')
                        <p class="mt-5 max-w-3xl text-sm leading-7 text-emerald-700">
                            Selamat. Lamaran Anda telah diterima oleh admin Karya Ide Edi. Periksa email Anda untuk informasi selanjutnya.
                        </p>
                    @else
                        <p class="mt-5 max-w-3xl text-sm leading-7 text-red-700">
                            Masa seleksi telah selesai dan lamaran Anda belum terpilih untuk tahap berikutnya.
                        </p>
                    @endif

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['Nama lengkap',$application->full_name],
                            ['Email',$application->email],
                            ['WhatsApp',$application->whatsapp],
                            ['Domisili',$application->domicile],
                            ['Pendidikan terakhir',$application->last_education ?: '-'],
                            ['Pengalaman kerja',$application->experience_years !== null ? $application->experience_years.' tahun' : '-'],
                        ] as [$label,$value])
                            <div class="rounded-2xl bg-[#FBF8F4] p-4">
                                <p class="text-[9px] font-semibold uppercase tracking-[0.18em] text-[#A08A72]">{{ $label }}</p>
                                <p class="mt-1.5 text-sm font-semibold text-[#493224]">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if ($application->cover_letter)
                        <div class="mt-5 rounded-3xl border border-[#E8DED2] p-5">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#A96D37]">Perkenalan Singkat</p>
                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-[#706458]">{{ $application->cover_letter }}</p>
                        </div>
                    @endif

                    <div class="mt-6">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#A96D37]">Dokumen yang Anda Kirim</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($application->cv_path)
                                <a target="_blank" rel="noopener" href="{{ route('careers.application.file', [$application, $application->public_token, 'cv']) }}" class="rounded-full bg-[#3B2518] px-4 py-2.5 text-xs font-semibold text-white">
                                    <i class="fa-solid fa-file-lines mr-1.5"></i>{{ $application->cv_original_name ?: 'Lihat CV' }}
                                </a>
                            @endif
                            @if ($application->portfolio_path)
                                <a target="_blank" rel="noopener" href="{{ route('careers.application.file', [$application, $application->public_token, 'portfolio']) }}" class="rounded-full border border-[#D8C7B5] px-4 py-2.5 text-xs font-semibold text-[#5B402D]">
                                    <i class="fa-solid fa-images mr-1.5"></i>{{ $application->portfolio_original_name ?: 'Lihat Portofolio' }}
                                </a>
                            @endif
                            @if ($application->portfolio_url)
                                <a target="_blank" rel="noopener" href="{{ $application->portfolio_url }}" class="rounded-full border border-[#D8C7B5] px-4 py-2.5 text-xs font-semibold text-[#5B402D]">
                                    <i class="fa-brands fa-google-drive mr-1.5"></i>Google Drive (CV + Portofolio)
                                </a>
                            @endif
                        </div>
                    </div>
                </section>

                <aside class="border-t border-[#E9DFD3] bg-[#FCFAF7] p-6 sm:p-8 lg:border-l lg:border-t-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#A96D37]">Timeline Rekrutmen</p>

                    <div class="mt-6 space-y-5">
                        <div>
                            <p class="text-[9px] font-semibold uppercase tracking-[0.18em] text-[#A08A72]">Pendaftaran</p>
                            <p class="mt-1 text-sm font-semibold text-[#493224]">
                                {{ $job?->deadline?->translatedFormat('d F Y') ?? 'Tidak ditentukan' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[9px] font-semibold uppercase tracking-[0.18em] text-[#A08A72]">Masa Seleksi</p>
                            <p class="mt-1 text-sm font-semibold leading-6 text-[#493224]">
                                @if ($job?->selectionStartDate() && $job?->selection_end)
                                    {{ $job->selectionStartDate()->translatedFormat('d F Y') }} – {{ $job->selection_end->translatedFormat('d F Y') }}
                                @else
                                    Menunggu jadwal admin
                                @endif
                            </p>
                        </div>

                        <div class="rounded-2xl border border-[#E8DED2] bg-white p-4 text-xs leading-6 text-[#75695D]">
                            Status pada halaman ini akan berubah otomatis setelah admin memberikan keputusan atau masa seleksi berakhir.
                        </div>
                    </div>

                    <a href="{{ route('careers.index') }}" class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-full border border-[#D8C7B5] px-4 py-3 text-sm font-semibold text-[#5B402D]">
                        <i class="fa-solid fa-arrow-left text-xs"></i>Kembali ke Careers
                    </a>
                </aside>
            </div>
        </div>
    </div>
</main>

@include('partials.frontend.footer')
</body>
</html>
'@

$careerContent = @'
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
        ->orderBy('deadline')
        ->latest('id')
        ->get();

    $careerJobCount = $careerJobs->count();
    $splitLines = fn (?string $text) => collect(preg_split('/\r\n|\r|\n/', (string) $text))
        ->map(fn ($line) => trim($line))
        ->filter()
        ->values();

    $applicationTokens = collect(request()->cookies->all())
        ->filter(fn ($value, $key) => str_starts_with($key, 'career_application_'))
        ->values()
        ->filter();

    $myApplications = $applicationTokens->isEmpty()
        ? collect()
        : \App\Models\JobApplication::query()
            ->with('job')
            ->whereIn('public_token', $applicationTokens->all())
            ->latest()
            ->get();

    $myApplicationsByJob = $myApplications->keyBy('job_vacancy_id');
@endphp

<main>
    <section class="relative overflow-hidden bg-[#2F1D14] text-white">
        <div class="pointer-events-none absolute -left-24 top-12 h-72 w-72 rounded-full bg-[#B47A45]/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-[#D9B27B]/10 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
                <div class="max-w-4xl">
                    <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.36em] text-[#D8AC72] sm:text-xs">
                        <span class="h-px w-10 bg-[#D8AC72]"></span>Careers at Karya Ide Edi
                    </div>
                    <h1 class="mt-7 font-display text-5xl font-semibold leading-[1.02] tracking-[-0.035em] sm:text-6xl lg:text-7xl">Bertumbuh bersama orang-orang yang peduli pada detail.</h1>
                    <p class="mt-7 max-w-2xl text-base leading-8 text-white/68 sm:text-lg">Temukan posisi terbuka, kirim lamaran, lalu pantau hasil seleksi langsung dari website.</p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/6 p-6 backdrop-blur-sm">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#D3A064] text-[#2F1D14]"><i class="fa-solid fa-briefcase"></i></span>
                    <p class="mt-6 text-[10px] font-semibold uppercase tracking-[0.28em] text-white/45">Posisi terbuka</p>
                    <p class="mt-2 font-display text-4xl font-semibold">{{ $careerJobCount }}</p>
                    <p class="mt-2 text-sm leading-6 text-white/55">Lowongan yang masih menerima pendaftaran.</p>
                </div>
            </div>
        </div>
    </section>

    @if ($myApplications->isNotEmpty())
        <section class="bg-[#F7F4EF] pt-10">
            <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <div class="rounded-4xl border border-[#E2D6C8] bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-[#A96D37]">Lamaran Saya</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-[#3D2B1F]">Pantau status tanpa mengisi ulang.</h2>
                        </div>
                        <span class="text-xs text-[#8A7B6D]">{{ $myApplications->count() }} lamaran tersimpan di browser ini</span>
                    </div>

                    <div class="mt-5 grid gap-3 lg:grid-cols-2">
                        @foreach ($myApplications as $application)
                            @php
                                $pending = ! in_array($application->status, ['accepted','rejected'], true);
                            @endphp
                            <a href="{{ route('careers.application.result', [$application, $application->public_token]) }}" class="group flex items-center justify-between gap-4 rounded-3xl border border-[#E7DCCF] bg-[#FCFAF7] p-4 transition hover:border-[#C9A57D] hover:bg-white">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[#493224]">{{ $application->job_title }}</p>
                                    <p class="mt-1 text-xs text-[#8A7B6D]">Dikirim {{ $application->created_at->translatedFormat('d M Y') }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="rounded-full px-3 py-1.5 text-[10px] font-semibold {{ $application->status === 'accepted' ? 'bg-emerald-100 text-emerald-700' : ($application->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $application->statusLabel() }}
                                    </span>
                                    <i class="fa-solid fa-arrow-right text-xs text-[#A96D37] transition group-hover:translate-x-1"></i>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($careerJobs->isNotEmpty())
        <section class="bg-[#F7F4EF] py-16 sm:py-20 lg:py-24">
            <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <div class="flex flex-col gap-5 border-b border-[#DED1C1] pb-8 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-[#A96D37]">Open Positions</p>
                        <h2 class="mt-3 font-display text-3xl font-semibold text-[#3D2B1F] sm:text-4xl">Lowongan Pekerjaan</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[#75695D]">Pendaftaran dan masa seleksi mempunyai jadwal berbeda. Setelah mengirim lamaran, tombol pendaftaran berubah menjadi Lihat Hasil.</p>
                    </div>
                    <div class="inline-flex w-fit items-center gap-2 rounded-full border border-[#D9C8B4] bg-white px-4 py-2 text-xs font-semibold text-[#6F5132]"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ $careerJobCount }} posisi dibuka</div>
                </div>

                <div class="mt-8 space-y-5">
                    @foreach ($careerJobs as $index => $job)
                        @php
                            $requirements = $splitLines($job->requirements);
                            $benefits = $splitLines($job->benefits);
                            $myApplication = $myApplicationsByJob->get($job->id);
                        @endphp

                        <article class="overflow-hidden rounded-4xl border border-[#E1D5C7] bg-white shadow-[0_18px_55px_-42px_rgba(61,43,31,0.45)] transition duration-300 hover:-translate-y-1">
                            <div class="grid lg:grid-cols-[minmax(0,1fr)_19rem]">
                                <div class="p-6 sm:p-8">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-[#F1E7DB] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#8A5A31]">{{ $job->department ?: 'Karya Ide Edi' }}</span>
                                        @if ($index === 0)<span class="rounded-full bg-[#2F1D14] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white">New</span>@endif
                                        @if ($myApplication)
                                            <span class="rounded-full px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] {{ $myApplication->status === 'accepted' ? 'bg-emerald-100 text-emerald-700' : ($myApplication->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $myApplication->statusLabel() }}</span>
                                        @endif
                                    </div>

                                    <h3 class="mt-4 font-display text-3xl font-semibold leading-tight text-[#352319] sm:text-4xl">{{ $job->title }}</h3>
                                    <p class="mt-4 max-w-3xl text-sm leading-7 text-[#706458] sm:text-[15px]">{{ $job->summary }}</p>

                                    <div class="mt-5 flex flex-wrap gap-2 text-[11px] font-semibold text-[#75695D]">
                                        <span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-regular fa-clock mr-1.5 text-[#AD7542]"></i>{{ $job->employment_type }}</span>
                                        <span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-solid fa-location-dot mr-1.5 text-[#AD7542]"></i>{{ $job->location }}</span>
                                        <span class="rounded-full border border-[#E5D9CB] bg-[#FCFAF7] px-3 py-2"><i class="fa-solid fa-building mr-1.5 text-[#AD7542]"></i>{{ $job->work_mode }}</span>
                                    </div>

                                    <details class="group/detail mt-6 border-t border-[#E9DFD3] pt-5">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-[#4B3323]">
                                            <span>Lihat detail posisi</span>
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#F3E9DD] text-[#8D5A30] transition group-open/detail:rotate-45"><i class="fa-solid fa-plus text-xs"></i></span>
                                        </summary>
                                        <div class="mt-5 grid gap-6 md:grid-cols-2">
                                            @if ($job->description)<div class="md:col-span-2"><h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Tentang Pekerjaan</h4><p class="mt-2 whitespace-pre-line text-sm leading-7 text-[#706458]">{{ $job->description }}</p></div>@endif
                                            @if ($requirements->isNotEmpty())<div><h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Kualifikasi</h4><ul class="mt-3 space-y-2.5">@foreach ($requirements as $requirement)<li class="flex gap-3 text-sm leading-6 text-[#706458]"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#B57B43]"></span><span>{{ $requirement }}</span></li>@endforeach</ul></div>@endif
                                            @if ($benefits->isNotEmpty())<div><h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">Yang Anda Dapatkan</h4><ul class="mt-3 space-y-2.5">@foreach ($benefits as $benefit)<li class="flex gap-2.5 text-sm leading-6 text-[#706458]"><i class="fa-solid fa-check mt-1 text-[10px] text-[#A96D37]"></i><span>{{ $benefit }}</span></li>@endforeach</ul></div>@endif
                                        </div>
                                    </details>
                                </div>

                                <aside class="flex flex-col justify-between border-t border-[#E9DFD3] bg-[#FCFAF7] p-6 sm:p-7 lg:border-l lg:border-t-0">
                                    <div class="space-y-5">
                                        @if ($job->salary_label)
                                            <div>
                                                <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Kompensasi</p>
                                                <p class="mt-1 text-lg font-semibold text-[#493224]">Rp {{ number_format((int) $job->salary_label, 0, ',', '.') }}</p>
                                            </div>
                                        @endif

                                        <div>
                                            <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Batas Pendaftaran</p>
                                            <p class="mt-1 text-sm font-semibold text-[#493224]">{{ $job->deadline?->translatedFormat('d F Y') ?? '-' }}</p>
                                        </div>

                                        @if ($job->selectionStartDate() && $job->selection_end)
                                            <div>
                                                <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Masa Seleksi</p>
                                                <p class="mt-1 text-sm font-semibold leading-6 text-[#493224]">{{ $job->selectionStartDate()->translatedFormat('d M') }} – {{ $job->selection_end->translatedFormat('d M Y') }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($myApplication)
                                        <a href="{{ route('careers.application.result', [$myApplication, $myApplication->public_token]) }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-[#A96D37] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#A96D37]/15 transition hover:-translate-y-0.5 hover:bg-[#8D582D]">
                                            <i class="fa-solid fa-eye text-xs"></i>Lihat Hasil
                                        </a>
                                    @else
                                        <a href="{{ route('careers.apply', $job) }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-[#3B2518] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#3B2518]/15 transition hover:-translate-y-0.5 hover:bg-[#6F4527]">
                                            <i class="fa-solid fa-paper-plane text-xs"></i>Daftar Sekarang
                                        </a>
                                    @endif
                                </aside>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @else
        <section class="bg-[#F7F4EF] py-20 sm:py-24">
            <div class="mx-auto max-w-4xl px-6 text-center sm:px-8">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-[#3B2518] text-[#DDB17B]"><i class="fa-solid fa-briefcase text-xl"></i></div>
                <p class="mt-7 text-[11px] font-semibold uppercase tracking-[0.3em] text-[#A96D37]">Belum Ada Posisi Terbuka</p>
                <h2 class="mt-4 font-display text-4xl font-semibold text-[#3D2B1F] sm:text-5xl">Belum ada lowongan saat ini</h2>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-[#75695D]">Lowongan yang sudah melewati batas pendaftaran otomatis tidak ditampilkan. Lamaran yang pernah dikirim tetap dapat dipantau pada bagian Lamaran Saya.</p>
            </div>
        </section>
    @endif
</main>

@include('partials.frontend.footer')
</body>
</html>
'@

$adminContent = @'
<?php

use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Services\CareerSelectionService;
use Illuminate\Support\Carbon;
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
    public string $selection_end = '';
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
        $this->selection_end = $job->selection_end?->format('Y-m-d') ?? '';
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
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'selection_end' => ['required', 'date', 'after:deadline'],
            'is_active' => ['boolean'],
        ], [
            'title.required' => 'Nama posisi wajib diisi.',
            'summary.required' => 'Ringkasan lowongan wajib diisi.',
            'location.required' => 'Lokasi kerja wajib diisi.',
            'salary_label.regex' => 'Gaji hanya boleh berisi angka.',
            'deadline.required' => 'Batas pendaftaran wajib diisi.',
            'deadline.after_or_equal' => 'Batas pendaftaran tidak boleh memakai tanggal yang sudah lewat.',
            'selection_end.required' => 'Batas akhir seleksi wajib diisi.',
            'selection_end.after' => 'Batas akhir seleksi harus setelah batas pendaftaran.',
        ]);

        $data['department'] = trim($data['department'] ?? '') ?: null;
        $data['salary_label'] = preg_replace('/\D+/', '', trim((string) ($data['salary_label'] ?? ''))) ?: null;
        $data['description'] = trim($data['description'] ?? '') ?: null;
        $data['requirements'] = trim($data['requirements'] ?? '') ?: null;
        $data['benefits'] = trim($data['benefits'] ?? '') ?: null;

        if ($this->editingId) {
            JobVacancy::query()->findOrFail($this->editingId)->update($data);
            session()->flash('status', 'Lowongan berhasil diperbarui.');
        } else {
            JobVacancy::query()->create($data);
            session()->flash('status', 'Lowongan berhasil diterbitkan.');
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

    public function acceptApplication(int $id): void
    {
        $application = JobApplication::query()->with('job')->findOrFail($id);
        $job = $application->job;

        if (! $job) {
            session()->flash('error', 'Lowongan asal pelamar sudah tidak tersedia.');
            return;
        }

        if ($application->status !== 'pending') {
            session()->flash('error', 'Keputusan untuk pelamar ini sudah dibuat.');
            return;
        }

        if (! $job->isSelectionActive()) {
            session()->flash('error', 'Pelamar hanya dapat diterima saat masa seleksi sedang berlangsung.');
            return;
        }

        $mailSent = app(CareerSelectionService::class)->accept($application);

        session()->flash(
            $mailSent ? 'status' : 'warning',
            $mailSent
                ? 'Pelamar diterima dan email hasil seleksi berhasil dikirim.'
                : 'Pelamar sudah diterima. Email belum terkirim dan akan dicoba ulang otomatis oleh scheduler.'
        );

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
        $this->selection_end = '';
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
            ->orderBy('deadline')
            ->latest('id')
            ->get();

        $applications = JobApplication::query()
            ->with('job:id,title,deadline,selection_end')
            ->when($this->applicationJobFilter, fn ($query) => $query->where('job_vacancy_id', $this->applicationJobFilter))
            ->latest()
            ->get();

        return [
            'jobs' => $jobs,
            'applications' => $applications,
            'totalJobs' => $jobs->count(),
            'activeJobs' => $jobs->filter(fn ($job) => $job->isOpen())->count(),
            'selectionJobs' => $jobs->filter(fn ($job) => $job->isSelectionActive())->count(),
            'unreadApplicants' => JobApplication::query()->unreadAdmin()->count(),
            'totalApplicants' => JobApplication::query()->count(),
        ];
    }
};
?>

<div class="space-y-6" wire:poll.15s>
    @if ($showForm)
        <style>#back-to-top-btn{z-index:20!important}</style>
    @endif

    @if (session('status'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
            <i class="fa-solid fa-circle-check"></i>{{ session('status') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="flex items-center gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>{{ session('warning') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 shadow-sm">
            <i class="fa-solid fa-circle-xmark"></i>{{ session('error') }}
        </div>
    @endif

    @if (config('mail.default') === 'log')
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-6 text-amber-800">
            <strong>Email masih dalam mode LOG.</strong> Keputusan seleksi sudah otomatis, tetapi email sungguhan baru terkirim setelah MAIL_MAILER/SMTP di file .env dikonfigurasi.
        </div>
    @endif

    <section class="relative overflow-hidden rounded-3xl border border-admin-border bg-admin-surface p-6 shadow-sm sm:p-7">
        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-admin-gold/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-admin-accent"><span class="h-px w-8 bg-admin-accent"></span>Career Management</div>
                <h2 class="font-display text-2xl font-semibold text-admin-ink sm:text-3xl">Lowongan Pekerjaan</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-admin-ink-soft">Pendaftaran dan seleksi memiliki jadwal terpisah. Admin cukup memilih kandidat yang diterima; sisanya otomatis Tidak Lolos ketika masa seleksi berakhir.</p>
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
                    <button type="button" wire:click="openCreate" class="inline-flex items-center gap-2 rounded-full bg-admin-accent px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-admin-accent/20 transition hover:-translate-y-0.5">
                        <i class="fa-solid fa-plus text-xs"></i>Tambah Lowongan
                    </button>
                @endif
            </div>
        </div>
    </section>

    @if (! $showApplicants)
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([['Total',$totalJobs,'fa-briefcase'],['Pendaftaran Aktif',$activeJobs,'fa-signal'],['Masa Seleksi',$selectionJobs,'fa-filter']] as [$label,$count,$icon])
                <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm">
                    <div class="flex items-center justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-admin-cream text-admin-accent"><i class="fa-solid {{ $icon }}"></i></span><span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-admin-ink-soft">{{ $label }}</span></div>
                    <p class="mt-5 font-display text-3xl font-semibold text-admin-ink">{{ $count }}</p>
                </div>
            @endforeach
        </div>

        @if ($showForm)
            @php
                $todayMin = now()->format('Y-m-d');
                $selectionMin = $deadline
                    ? Carbon::parse($deadline)->addDay()->format('Y-m-d')
                    : now()->addDay()->format('Y-m-d');
                $selectionStartLabel = $deadline
                    ? Carbon::parse($deadline)->addDay()->translatedFormat('d F Y')
                    : 'otomatis sehari setelah pendaftaran ditutup';
            @endphp

            <form wire:submit="save" class="relative z-60 overflow-hidden rounded-3xl border border-admin-border bg-admin-surface shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-admin-border bg-admin-canvas px-5 py-4 sm:px-6">
                    <div><p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-admin-accent">{{ $editingId ? 'Edit' : 'Posisi Baru' }}</p><h3 class="mt-1 font-display text-lg font-semibold text-admin-ink">{{ $editingId ? 'Perbarui Lowongan' : 'Tambah Lowongan Pekerjaan' }}</h3></div>
                    <button type="button" wire:click="cancelForm" class="flex h-9 w-9 items-center justify-center rounded-full text-admin-ink-soft transition hover:bg-admin-cream"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Nama posisi <span class="text-red-500">*</span></label><input wire:model="title" type="text" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">@error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Divisi / departemen</label><input wire:model="department" type="text" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink"></div>

                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Tipe pekerjaan</label><select wire:model="employment_type" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">@foreach (['Full-time','Part-time','Contract','Internship','Freelance'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Sistem kerja</label><select wire:model="work_mode" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">@foreach (['On-site','Hybrid','Remote'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>

                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Lokasi kerja <span class="text-red-500">*</span></label><input wire:model="location" type="text" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">@error('location')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Gaji / kompensasi <span class="text-admin-ink-soft">(opsional)</span></label>
                        <div class="flex overflow-hidden rounded-xl border border-admin-border bg-admin-canvas"><span class="flex shrink-0 items-center border-r border-admin-border bg-admin-cream px-4 text-sm font-semibold text-admin-accent">Rp</span><input wire:model="salary_label" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')" class="min-w-0 flex-1 bg-transparent px-3.5 py-3 text-sm text-admin-ink outline-none"></div>
                        @error('salary_label')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Ringkasan lowongan <span class="text-red-500">*</span></label><textarea wire:model="summary" rows="3" maxlength="500" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink"></textarea>@error('summary')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-medium text-admin-ink">Tentang pekerjaan</label><textarea wire:model="description" rows="4" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink"></textarea></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Kualifikasi</label><textarea wire:model="requirements" rows="6" placeholder="Satu poin per baris" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink"></textarea></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Benefit</label><textarea wire:model="benefits" rows="6" placeholder="Satu poin per baris" class="w-full resize-none rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm leading-relaxed text-admin-ink"></textarea></div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Batas pendaftaran <span class="text-red-500">*</span></label>
                        <input wire:model.live="deadline" min="{{ $todayMin }}" type="date" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">
                        <p class="mt-1.5 text-[11px] leading-5 text-admin-ink-soft">Tanggal sebelumnya tidak dapat dipilih. Setelah tanggal ini, lowongan otomatis hilang dari halaman Careers.</p>
                        @error('deadline')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Batas akhir seleksi <span class="text-red-500">*</span></label>
                        <input wire:model="selection_end" min="{{ $selectionMin }}" type="date" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink">
                        <p class="mt-1.5 text-[11px] leading-5 text-admin-ink-soft">Masa seleksi dimulai otomatis <strong>{{ $selectionStartLabel }}</strong>. Kandidat Pending yang belum diterima sampai batas ini akan otomatis Tidak Lolos.</p>
                        @error('selection_end')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-admin-border bg-admin-canvas p-4 lg:col-span-2"><input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded border-admin-border text-admin-accent"><span><span class="block text-sm font-semibold text-admin-ink">Tampilkan lowongan di halaman Careers</span><span class="mt-0.5 block text-xs text-admin-ink-soft">Lowongan hanya tampil sampai batas pendaftaran.</span></span></label>
                </div>

                <div class="flex justify-end gap-3 border-t border-admin-border bg-admin-canvas px-5 py-4 sm:px-6"><button type="button" wire:click="cancelForm" class="rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink">Batal</button><button type="submit" class="rounded-full bg-admin-panel px-6 py-2.5 text-sm font-semibold text-white">{{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Lowongan' }}</button></div>
            </form>
        @endif

        <section>
            <div class="mb-4"><h3 class="font-display text-lg font-semibold text-admin-ink">Daftar Lowongan</h3><p class="mt-1 text-xs text-admin-ink-soft">Jadwal seleksi selalu dimulai sehari setelah pendaftaran berakhir.</p></div>

            @if ($jobs->isEmpty())
                <div class="rounded-3xl border border-dashed border-admin-border bg-admin-surface px-6 py-14 text-center"><h4 class="font-display text-lg font-semibold text-admin-ink">Belum ada lowongan</h4></div>
            @else
                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach ($jobs as $job)
                        <article wire:key="job-{{ $job->id }}" class="rounded-3xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        @if ($job->isOpen())
                                            <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-emerald-600">Pendaftaran</span>
                                        @elseif ($job->isSelectionActive())
                                            <span class="rounded-full bg-blue-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-blue-600">Seleksi</span>
                                        @elseif ($job->isSelectionFinished())
                                            <span class="rounded-full bg-slate-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-slate-600">Selesai</span>
                                        @else
                                            <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-amber-600">Draft</span>
                                        @endif
                                        @if ($job->department)<span class="text-[11px] text-admin-ink-soft">{{ $job->department }}</span>@endif
                                    </div>
                                    <h4 class="font-display text-xl font-semibold text-admin-ink">{{ $job->title }}</h4>
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-admin-ink-soft">{{ $job->summary }}</p>
                                </div>
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-admin-cream text-admin-accent"><i class="fa-solid fa-briefcase"></i></span>
                            </div>

                            <div class="mt-4 grid gap-3 rounded-2xl bg-admin-canvas p-4 text-xs sm:grid-cols-2">
                                <div><span class="text-admin-ink-soft">Pendaftaran</span><p class="mt-1 font-semibold text-admin-ink">{{ $job->deadline?->translatedFormat('d M Y') ?? '-' }}</p></div>
                                <div><span class="text-admin-ink-soft">Seleksi</span><p class="mt-1 font-semibold text-admin-ink">@if($job->selectionStartDate() && $job->selection_end){{ $job->selectionStartDate()->translatedFormat('d M') }} – {{ $job->selection_end->translatedFormat('d M Y') }}@else-@endif</p></div>
                            </div>

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
                    $candidateStatus = $application->status === 'accepted'
                        ? ['Diterima','bg-emerald-100 text-emerald-700']
                        : ($application->status === 'rejected'
                            ? ['Tidak Lolos','bg-red-100 text-red-700']
                            : ['Pending','bg-amber-100 text-amber-700']);
                    $selectionActive = $application->job?->isSelectionActive() ?? false;
                @endphp

                <article wire:key="application-{{ $application->id }}" class="rounded-3xl border border-admin-border bg-admin-surface p-5 shadow-sm sm:p-6">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_16rem]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-admin-cream px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-admin-accent">{{ $application->job_title }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $candidateStatus[1] }}">{{ $candidateStatus[0] }}</span>
                                <span class="text-[11px] text-admin-ink-soft">{{ $application->created_at->translatedFormat('d M Y, H:i') }}</span>
                            </div>

                            <h4 class="mt-3 font-display text-xl font-semibold text-admin-ink">{{ $application->full_name }}</h4>
                            <div class="mt-3 grid gap-x-6 gap-y-2 text-sm text-admin-ink-soft sm:grid-cols-2">
                                <p><i class="fa-regular fa-envelope mr-2 text-admin-accent"></i>{{ $application->email }}</p>
                                <p><i class="fa-brands fa-whatsapp mr-2 text-admin-accent"></i>{{ $application->whatsapp }}</p>
                                <p><i class="fa-solid fa-location-dot mr-2 text-admin-accent"></i>{{ $application->domicile }}</p>
                                @if ($application->last_education)<p><i class="fa-solid fa-graduation-cap mr-2 text-admin-accent"></i>{{ $application->last_education }}</p>@endif
                            </div>

                            @if ($application->cover_letter)<div class="mt-4 rounded-2xl bg-admin-canvas p-4 text-sm leading-7 text-admin-ink-soft">{{ $application->cover_letter }}</div>@endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($application->cv_path)<a href="{{ route('admin.jobs.applications.file', ['application'=>$application,'file'=>'cv']) }}" class="rounded-full bg-admin-panel px-4 py-2 text-xs font-semibold text-white"><i class="fa-solid fa-file-arrow-down mr-1.5"></i>Unduh CV</a>@endif
                                @if ($application->portfolio_path)<a href="{{ route('admin.jobs.applications.file', ['application'=>$application,'file'=>'portfolio']) }}" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink"><i class="fa-solid fa-folder-open mr-1.5"></i>Unduh Portofolio</a>@endif
                                @if ($application->portfolio_url)<a href="{{ $application->portfolio_url }}" target="_blank" rel="noopener" class="rounded-full border border-admin-accent bg-admin-accent/5 px-4 py-2 text-xs font-semibold text-admin-accent"><i class="fa-brands fa-google-drive mr-1.5"></i>Google Drive (CV + Portofolio)</a>@endif
                            </div>
                        </div>

                        <aside class="rounded-2xl bg-admin-canvas p-4">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-admin-ink-soft">Keputusan Seleksi</p>

                            @if ($application->status === 'pending')
                                @if ($selectionActive)
                                    <button type="button" wire:click="acceptApplication({{ $application->id }})" wire:confirm="Terima {{ $application->full_name }} untuk posisi {{ $application->job_title }}? Email penerimaan akan dikirim otomatis." class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white">
                                        <i class="fa-solid fa-check"></i>Terima Pelamar
                                    </button>
                                    <p class="mt-2 text-[11px] leading-5 text-admin-ink-soft">Tidak perlu menolak manual. Kandidat yang tetap Pending sampai masa seleksi selesai akan otomatis Tidak Lolos.</p>
                                @else
                                    <div class="mt-3 rounded-xl border border-admin-border bg-admin-surface p-3 text-xs leading-6 text-admin-ink-soft">
                                        @if ($application->job?->selectionStartDate() && today()->lt($application->job->selectionStartDate()))
                                            Seleksi belum dimulai. Mulai {{ $application->job->selectionStartDate()->translatedFormat('d F Y') }}.
                                        @elseif ($application->job?->isSelectionFinished())
                                            Masa seleksi sudah berakhir. Scheduler akan memproses status otomatis.
                                        @else
                                            Jadwal seleksi belum lengkap.
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="mt-3 rounded-xl bg-admin-surface p-3">
                                    <p class="text-sm font-semibold {{ $application->status === 'accepted' ? 'text-emerald-700' : 'text-red-700' }}">{{ $candidateStatus[0] }}</p>
                                    @if ($application->decided_at)<p class="mt-1 text-[11px] text-admin-ink-soft">{{ $application->decided_at->translatedFormat('d M Y, H:i') }}</p>@endif
                                    <p class="mt-2 text-[11px] text-admin-ink-soft">{{ $application->decision_notified_at ? 'Email hasil sudah dikirim.' : 'Email belum terkirim; scheduler akan mencoba ulang.' }}</p>
                                </div>
                            @endif

                            @if ($application->experience_years !== null)<p class="mt-4 text-xs leading-5 text-admin-ink-soft">Pengalaman: <strong class="text-admin-ink">{{ $application->experience_years }} tahun</strong></p>@endif
                        </aside>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-admin-border bg-admin-surface px-6 py-14 text-center">
                    <i class="fa-solid fa-user-group text-2xl text-admin-accent"></i>
                    <h4 class="mt-4 font-display text-lg font-semibold text-admin-ink">Belum ada pelamar</h4>
                    <p class="mt-2 text-sm text-admin-ink-soft">Lamaran baru akan muncul otomatis di sini.</p>
                </div>
            @endforelse
        </section>
    @endif
</div>
'@

Write-NoBom ".\app\Models\JobVacancy.php" $jobVacancyContent
Write-NoBom ".\app\Models\JobApplication.php" $jobApplicationContent
Write-NoBom ".\database\migrations\2026_09_24_000004_add_career_selection_tracking.php" $migrationContent
Write-NoBom ".\app\Services\CareerSelectionService.php" $serviceContent
Write-NoBom ".\app\Console\Commands\FinalizeCareerSelections.php" $commandContent
Write-NoBom ".\app\Http\Controllers\JobApplicationController.php" $controllerContent
Write-NoBom ".\resources\views\emails\career-decision.blade.php" $emailContent
Write-NoBom ".\resources\views\pages\frontend\hasil-lamaran.blade.php" $resultContent
Write-NoBom ".\resources\views\pages\frontend\karier.blade.php" $careerContent
Write-NoBom ".\resources\views\pages\admin\loker.blade.php" $adminContent

Step "[3/8] Menambahkan route hasil lamaran + file kandidat ..."

$routes = ".\routes\web.php"
if (-not (Test-Path $routes)) { throw "routes\web.php tidak ditemukan." }
$routeText = [System.IO.File]::ReadAllText((Resolve-Path $routes))

if ($routeText -notmatch "name\('careers\.application\.result'\)") {
    $anchor = "Route::post('/karier/lamar/{job}', [\App\Http\Controllers\JobApplicationController::class, 'store'])->name('careers.apply.store');"

    if (-not $routeText.Contains($anchor)) {
        throw "Route careers.apply.store tidak ditemukan. Berhenti agar routes tidak ditulis di lokasi yang salah."
    }

    $addition = @'
Route::post('/karier/lamar/{job}', [\App\Http\Controllers\JobApplicationController::class, 'store'])->name('careers.apply.store');
Route::get('/karier/hasil/{application}/{token}', [\App\Http\Controllers\JobApplicationController::class, 'result'])->name('careers.application.result');
Route::get('/karier/hasil/{application}/{token}/file/{type}', [\App\Http\Controllers\JobApplicationController::class, 'file'])
    ->whereIn('type', ['cv', 'portfolio'])
    ->name('careers.application.file');
'@

    $routeText = $routeText.Replace($anchor, $addition.TrimEnd())
}

Write-NoBom $routes $routeText

Step "[4/8] Mengaktifkan scheduler seleksi otomatis ..."

$console = ".\routes\console.php"
if (-not (Test-Path $console)) { throw "routes\console.php tidak ditemukan." }

$consoleText = [System.IO.File]::ReadAllText((Resolve-Path $console))

if ($consoleText -notmatch 'Illuminate\\Support\\Facades\\Schedule') {
    $consoleText = $consoleText.Replace(
        "use Illuminate\Support\Facades\Artisan;",
        "use Illuminate\Support\Facades\Artisan;`r`nuse Illuminate\Support\Facades\Schedule;"
    )
}

if ($consoleText -notmatch "career:finalize-selections") {
    $consoleText = $consoleText.TrimEnd() + "`r`n`r`nSchedule::command('career:finalize-selections')->everyFiveMinutes()->withoutOverlapping();`r`n"
}

Write-NoBom $console $consoleText

Step "[5/8] Memastikan badge Pelamar + notifikasi frontend tetap aktif ..."

$navBadge = ".\resources\views\components\admin\nav-badge.blade.php"
if (Test-Path $navBadge) {
    $text = [System.IO.File]::ReadAllText((Resolve-Path $navBadge))
    if ($text -notmatch "'pelamar'\s*=>") {
        $anchor = "            'dashboard' => `$user->unreadDashboardCount(),"
        if (-not $text.Contains($anchor)) {
            throw "Anchor nav badge dashboard tidak ditemukan."
        }
        $text = $text.Replace(
            $anchor,
            $anchor + "`r`n            'pelamar' => \App\Models\JobApplication::query()->unreadAdmin()->count(),"
        )
    }
    Write-NoBom $navBadge $text
}

$sidebar = ".\resources\views\layouts\admin-panel.blade.php"
if (Test-Path $sidebar) {
    $text = [System.IO.File]::ReadAllText((Resolve-Path $sidebar))
    if ($text -notmatch 'type="pelamar"') {
        $anchor = @'
                                    @elseif ($item['route'] === 'admin.transactions')
                                        <livewire:admin.nav-badge type="pesanan" :active="$item['active']" :key="'nav-badge-pesanan'" />
                                    @endif
'@
        $replacement = @'
                                    @elseif ($item['route'] === 'admin.transactions')
                                        <livewire:admin.nav-badge type="pesanan" :active="$item['active']" :key="'nav-badge-pesanan'" />
                                    @elseif ($item['route'] === 'admin.jobs')
                                        <livewire:admin.nav-badge type="pelamar" :active="$item['active']" :key="'nav-badge-pelamar'" />
                                    @endif
'@
        if (-not $text.Contains($anchor.Trim())) {
            throw "Anchor sidebar badge Pesanan tidak ditemukan."
        }
        $text = $text.Replace($anchor.Trim(), $replacement.Trim())
    }
    Write-NoBom $sidebar $text
}

$bell = ".\resources\views\components\notification-bell.blade.php"
if (Test-Path $bell) {
    $text = [System.IO.File]::ReadAllText((Resolve-Path $bell))

    if ($text -notmatch 'unreadJobApplications') {
        $old = '        return $user ? $user->unreadNotificationsCount() : 0;'
        $new = @'
        $unreadJobApplications = \App\Models\JobApplication::query()->unreadAdmin()->count();

        return $user ? $user->unreadNotificationsCount() + $unreadJobApplications : 0;
'@
        if (-not $text.Contains($old)) {
            throw "Perhitungan notification bell tidak ditemukan."
        }
        $text = $text.Replace($old, $new.TrimEnd())
    }

    if ($text -notmatch "'label'\s*=>\s*'Pelamar Baru'") {
        $anchor = "            ['icon' => 'fa-triangle-exclamation', 'label' => 'Stok Menipis', 'count' => `$user->unreadDashboardCount()],"
        if (-not $text.Contains($anchor)) {
            throw "Breakdown notification bell tidak ditemukan."
        }
        $text = $text.Replace(
            $anchor,
            $anchor + "`r`n            ['icon' => 'fa-user-plus', 'label' => 'Pelamar Baru', 'count' => \App\Models\JobApplication::query()->unreadAdmin()->count()],"
        )
    }

    Write-NoBom $bell $text
}

Step "[6/8] Validasi syntax ..."
$checkFiles = @(
    ".\app\Models\JobVacancy.php",
    ".\app\Models\JobApplication.php",
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\app\Services\CareerSelectionService.php",
    ".\app\Console\Commands\FinalizeCareerSelections.php",
    ".\database\migrations\2026_09_24_000004_add_career_selection_tracking.php",
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\frontend\hasil-lamaran.blade.php",
    ".\resources\views\emails\career-decision.blade.php",
    ".\routes\web.php",
    ".\routes\console.php"
)

foreach ($file in $checkFiles) {
    php -l $file | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Backup tersedia di: $backupDir"
    }
}

Step "[7/8] Migration + clear cache ..."
php artisan migrate
if ($LASTEXITCODE -ne 0) {
    throw "Migration gagal. Backup tersedia di: $backupDir"
}

php artisan optimize:clear | Out-Host

Step "[8/8] Verifikasi route + command ..."
php artisan route:list --name=careers.application | Out-Host
php artisan route:list --name=admin.jobs | Out-Host
php artisan list | Select-String "career:finalize-selections" | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Fitur aktif:" -ForegroundColor Yellow
Write-Host "  - Setelah melamar, tombol menjadi Lihat Hasil pada browser yang sama." -ForegroundColor White
Write-Host "  - Status awal kandidat = Pending." -ForegroundColor White
Write-Host "  - Halaman Hasil menampilkan semua data + dokumen yang dikirim." -ForegroundColor White
Write-Host "  - Pendaftaran dan masa seleksi memiliki tanggal terpisah." -ForegroundColor White
Write-Host "  - Masa seleksi mulai otomatis sehari setelah pendaftaran ditutup." -ForegroundColor White
Write-Host "  - Admin hanya memilih kandidat yang diterima." -ForegroundColor White
Write-Host "  - Sisa kandidat Pending otomatis Tidak Lolos setelah batas seleksi." -ForegroundColor White
Write-Host "  - Email diterima/tidak lolos dikirim otomatis." -ForegroundColor White
Write-Host ""
Write-Host "PENTING: agar seleksi otomatis berjalan saat memakai php artisan serve," -ForegroundColor Yellow
Write-Host "buka terminal VS Code KEDUA dan jalankan: php artisan schedule:work" -ForegroundColor Yellow
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
