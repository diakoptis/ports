<?php

use App\Http\Controllers\PortController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortController::class, 'index'])->name('home');
