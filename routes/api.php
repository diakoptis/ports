<?php

use App\Http\Controllers\Api\PortController;
use Illuminate\Support\Facades\Route;

Route::get('/ports', [PortController::class, 'index'])->name('api.ports.index');
