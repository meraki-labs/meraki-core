<?php
// src/routes/web.php
use Illuminate\Support\Facades\Route;
use Meraki\Core\Http\Controllers\CoreController;

Route::get('/meraki', [CoreController::class, 'index']);
