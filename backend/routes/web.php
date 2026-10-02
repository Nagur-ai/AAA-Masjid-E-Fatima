<?php

use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\Admin;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\PublicController;
use Illuminate\Support\Facades\Route;

// Public site
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::prefix('m/{masjid}')->name('masjid.')->group(function () {
    Route::get('/', [PublicController::class, 'show'])->name('show');
    Route::get('timetable', [PublicController::class, 'timetable'])->name('timetable');
    Route::get('ramadan/{hijriYear?}', [PublicController::class, 'ramadan'])->whereNumber('hijriYear')->name('ramadan');
    Route::get('donate', [PublicController::class, 'donateForm'])->name('donate');
    Route::post('donate', [PublicController::class, 'donate'])->middleware('throttle:donations');
});

// Sign-in for staff and members
Route::middleware('guest')->group(function () {
    Route::get('admin/login', [LoginController::class, 'show'])->name('login');
    Route::post('admin/login', [LoginController::class, 'login'])->middleware('throttle:auth');
});
Route::post('admin/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Any signed-in user: their own donations and receipts
Route::middleware('auth')->group(function () {
    Route::get('account', [AccountController::class, 'index'])->name('account');
    Route::get('account/donations/{donation}/receipt', [AccountController::class, 'receipt'])->name('account.receipt');
});

// Admin panel, always scoped to the signed-in user's masjid
Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('donations', [Admin\DonationController::class, 'index'])->name('donations.index');
    Route::get('donations/create', [Admin\DonationController::class, 'create'])->name('donations.create');
    Route::post('donations', [Admin\DonationController::class, 'store'])->name('donations.store');
    Route::get('donations/{donation}/receipt', [Admin\DonationController::class, 'receipt'])->name('donations.receipt');
    Route::post('donations/{donation}/complete', [Admin\DonationController::class, 'complete'])->name('donations.complete');
    Route::post('donations/{donation}/cancel', [Admin\DonationController::class, 'cancel'])->name('donations.cancel');
    Route::post('donations/{donation}/refund', [Admin\DonationController::class, 'refund'])->name('donations.refund');

    Route::get('transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/create', [Admin\TransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [Admin\TransactionController::class, 'store'])->name('transactions.store');
    Route::post('transactions/{transaction}/void', [Admin\TransactionController::class, 'void'])->name('transactions.void');
    Route::get('transactions/{transaction}/attachment', [Admin\TransactionController::class, 'attachment'])->name('transactions.attachment');

    Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');

    Route::get('funds', [Admin\FundController::class, 'index'])->name('funds.index');
    Route::post('funds', [Admin\FundController::class, 'store'])->name('funds.store');
    Route::put('funds/{fund}', [Admin\FundController::class, 'update'])->name('funds.update');
    Route::post('categories', [Admin\FundController::class, 'storeCategory'])->name('categories.store');

    Route::middleware('staff:admin')->group(function () {
        Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/iqamah', [Admin\SettingsController::class, 'storeIqamah'])->name('settings.iqamah.store');
        Route::delete('settings/iqamah/{rule}', [Admin\SettingsController::class, 'destroyIqamah'])->name('settings.iqamah.destroy');
        Route::post('settings/jumuah', [Admin\SettingsController::class, 'storeJumuah'])->name('settings.jumuah.store');
        Route::delete('settings/jumuah/{session}', [Admin\SettingsController::class, 'destroyJumuah'])->name('settings.jumuah.destroy');

        Route::get('ramadan/{hijriYear?}', [Admin\RamadanController::class, 'edit'])->whereNumber('hijriYear')->name('ramadan.edit');
        Route::put('ramadan/{hijriYear}', [Admin\RamadanController::class, 'update'])->whereNumber('hijriYear')->name('ramadan.update');

        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('users', [Admin\UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    });
});
