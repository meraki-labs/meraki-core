<?php
// src/routes/web.php
use Illuminate\Support\Facades\Route;
use Merakilab\Core\Http\Controllers\CoreController;

Route::get('/meraki', [CoreController::class, 'index']);
