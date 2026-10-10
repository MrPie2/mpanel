<?php

use App\\Http\\Controllers\\AuthController;
use App\\Http\\Controllers\\DashboardController;
use App\\Http\\Controllers\\DatabaseController;
use App\\Http\\Controllers\\DnsController;
use App\\Http\\Controllers\\GitController;
use App\\Http\\Controllers\\ServerController;
use App\\Http\\Controllers\\TerminalController;
use App\\Http\\Controllers\\WebsiteController;
use Illuminate\\Support\\Facades\\Route;

Route::post('/webhooks/github/{website}', [GitController::class, 'webhook'])->name('webhooks.github');

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('websites', WebsiteController::class)->only(['index', 'show']);
    Route::get('/terminal', [TerminalController::class, 'index'])->name('terminal.index');
    Route::get('/websites/{website}/terminal', [TerminalController::class, 'show'])->name('websites.terminal');
    Route::get('/databases', [DatabaseController::class, 'index'])->name('databases.index');
    Route::get('/databases/create', [DatabaseController::class, 'create'])->name('databases.create');
    Route::post('/databases', [DatabaseController::class, 'store'])->name('databases.store');
    Route::get('/databases/{database}', [DatabaseController::class, 'show'])->name('databases.show');
    Route::delete('/databases/{database}', [DatabaseController::class, 'destroy'])->name('databases.destroy');
    Route::get('/databases/{database}/jobs/{job}', [DatabaseController::class, 'jobStatus'])->name('databases.jobs.status');
    Route::get('/websites/{website}/dns', [DnsController::class, 'index'])->name('websites.dns');
    Route::post('/websites/{website}/dns', [DnsController::class, 'store'])->name('websites.dns.store');
    Route::delete('/websites/{website}/dns/{record}', [DnsController::class, 'destroy'])->name('websites.dns.destroy');
    Route::get('/websites/{website}/git', [GitController::class, 'index'])->name('websites.git');
    Route::post('/websites/{website}/git/connect', [GitController::class, 'connect'])->name('websites.git.connect');
    Route::post('/websites/{website}/git/deploy', [GitController::class, 'deploy'])->name('websites.git.deploy');
    Route::get('/websites/{website}/git/jobs/{job}', [GitController::class, 'status'])->name('websites.git.status');
    Route::delete('/websites/{website}/git', [GitController::class, 'disconnect'])->name('websites.git.disconnect');
    Route::get('/websites/{website}/ssl', [WebsiteController::class, 'ssl'])->name('websites.ssl');
    Route::post('/websites/{website}/ssl/issue', [WebsiteController::class, 'sslIssue'])->name('websites.ssl.issue');
    Route::get('/websites/{website}/ssl/issue/{job}', [WebsiteController::class, 'sslIssueStatus'])->name('websites.ssl.issue.status');
    Route::post('/websites/{website}/ssl/disable', [WebsiteController::class, 'sslDisable'])->name('websites.ssl.disable');
    Route::get('/websites/{website}/ssl/disable/{job}', [WebsiteController::class, 'sslDisableStatus'])->name('websites.ssl.disable.status');
    Route::get('/websites/{website}/files', [WebsiteController::class, 'files'])->name('websites.files');
    Route::post('/websites/{website}/files/jobs', [WebsiteController::class, 'filesJob'])->name('websites.files.jobs');
    Route::get('/websites/{website}/files/jobs/{job}', [WebsiteController::class, 'filesJobStatus'])->name('websites.files.jobs.status');
    Route::post('/websites/{website}/files/operations', [WebsiteController::class, 'fileOperation'])->name('websites.files.operations');
    Route::get('/websites/{website}/files/operations/{job}', [WebsiteController::class, 'fileOperationStatus'])->name('websites.files.operations.status');
    Route::post('/websites/{website}/files/upload', [WebsiteController::class, 'upload'])->name('websites.files.upload');
    Route::get('/websites/{website}/files/upload/{job}', [WebsiteController::class, 'uploadStatus'])->name('websites.files.upload.status');
    Route::post('/websites/{website}/files/download', [WebsiteController::class, 'download'])->name('websites.files.download');
    Route::get('/websites/{website}/files/download/{job}', [WebsiteController::class, 'downloadStatus'])->name('websites.files.download.status');
    Route::resource('servers', ServerController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/servers/{server}/websites', [ServerController::class, 'websiteCreate'])->name('servers.websites.store');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
