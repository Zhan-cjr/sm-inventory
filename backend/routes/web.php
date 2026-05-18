<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/print/transaction/{transaction}', function (\App\Models\Transaction $transaction) {
    return view('print.transaction-receipt', compact('transaction'));
})->name('print.transaction')->middleware('web');

Route::get('/print/report/{type}', [\App\Http\Controllers\ReportPrintController::class, 'print'])->name('print.report')->middleware('web');
