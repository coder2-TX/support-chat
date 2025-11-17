<?php

use Illuminate\Support\Facades\Route;
use khdija\SupportChat\Http\Controllers\Business\SupportChatController;

Route::get('/business/support', [SupportChatController::class, 'index'])
    ->name('support');

Route::post('/business/support', [SupportChatController::class, 'store'])
    ->name('support.store');
