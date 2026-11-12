<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');
