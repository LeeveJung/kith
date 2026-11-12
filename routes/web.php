<?php

use App\Http\Controllers\BreederPageController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::get('/breeders/{breeder:slug}', BreederPageController::class)->name('breeders.show');

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');
