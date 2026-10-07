<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('websites', WebsiteController::class)->only(['index', 'show']);
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
