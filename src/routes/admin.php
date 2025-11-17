<?php

use Illuminate\Support\Facades\Route;
use khdija\SupportChat\Http\Controllers\Admin\SupportInboxController;

Route::get('/conversations', [SupportInboxController::class, 'index'])
    ->name('conversations');

Route::get('/conversations/{business}', [SupportInboxController::class, 'users'])
    ->name('conversations.show');

Route::post('/conversations/{business}/user/{user}/reply', [SupportInboxController::class, 'replyToUser'])
    ->name('conversations.reply_user');

Route::post('/conversations/{business}/user/{user}/ack', [SupportInboxController::class, 'ackUser'])
    ->name('conversations.ack_user');

Route::get('/conversations/counters', [SupportInboxController::class, 'counters'])
    ->name('conversations.counters');

Route::get('/conversations/counters-map', [SupportInboxController::class, 'countersMap'])
    ->name('conversations.counters_map');

Route::get('/conversations/{business}/user/{user}/stream', [SupportInboxController::class, 'stream'])
    ->name('conversations.stream');
