<?php

use Illuminate\Support\Facades\Route;
use Meraki\Core\Http\Controllers\InstallerController;

Route::middleware(['web'])->prefix('meraki/install')->group(function () {
    Route::get('/', [InstallerController::class, 'welcome'])->name('meraki.install.welcome');
    Route::get('/environment',  [InstallerController::class, 'environment'])->name('meraki.install.environment');
    Route::post('/environment', [InstallerController::class, 'postEnvironment']);
    Route::get('/database',  [InstallerController::class, 'database'])->name('meraki.install.database');
    Route::post('/database', [InstallerController::class, 'postDatabase']);
    Route::get('/admin',  [InstallerController::class, 'admin'])->name('meraki.install.admin');
    Route::post('/admin', [InstallerController::class, 'postAdmin']);
    Route::get('/plugins',  [InstallerController::class, 'plugins'])->name('meraki.install.plugins');
    Route::post('/plugins', [InstallerController::class, 'postPlugins']);
    Route::get('/complete', [InstallerController::class, 'complete'])->name('meraki.install.complete');
    Route::post('/complete', [InstallerController::class, 'complete']);
});
