<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('/app-ciudadano', 'ciudadano')->name('app.ciudadano');
Route::view('/app-conductor', 'conductor')->name('app.conductor');
Route::view('/app-admin', 'admin')->name('app.admin');
