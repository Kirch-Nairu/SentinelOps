<?php

use App\Http\Controllers\Api\AdminMembershipController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\EvidenceStagingController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\ReauthenticationController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');

    Route::prefix('/api')->group(function () {
        Route::post('/sync/operations', [SyncController::class, 'store'])->middleware('throttle:120,1');
        Route::post('/evidence/stage', [EvidenceStagingController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/evidence/{evidence}', [EvidenceController::class, 'show'])->middleware('throttle:60,1');
        Route::post('/incidents/{incident}/close', [IncidentController::class, 'close']);
        Route::post('/assets/{asset}/maintenance', [MaintenanceController::class, 'store']);
        Route::post('/reauthenticate', [ReauthenticationController::class, 'store'])->middleware('throttle:10,1');
        Route::patch('/admin/memberships/{membership}', [AdminMembershipController::class, 'update'])->middleware('privileged.reauth');
    });
});
