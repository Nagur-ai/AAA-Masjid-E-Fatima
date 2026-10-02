<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DonationController;
use App\Http\Controllers\Api\V1\IqamahRuleController;
use App\Http\Controllers\Api\V1\MasjidController;
use App\Http\Controllers\Api\V1\PrayerTimeController;
use App\Http\Controllers\Api\V1\RamadanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    Route::middleware('throttle:auth')->prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Public: anyone with the app can read times, calendars and funds, and pledge a donation.
    Route::get('masjids', [MasjidController::class, 'index']);
    Route::prefix('masjids/{masjid}')->group(function () {
        Route::get('/', [MasjidController::class, 'show']);
        Route::get('prayer-times', [PrayerTimeController::class, 'daily']);
        Route::get('prayer-times/monthly', [PrayerTimeController::class, 'monthly']);
        Route::get('ramadan/{hijriYear?}', [RamadanController::class, 'show'])->whereNumber('hijriYear');
        Route::get('funds', [DonationController::class, 'funds']);
        Route::post('donations', [DonationController::class, 'store'])->middleware('throttle:donations');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me/donations', [DonationController::class, 'mine']);

        Route::prefix('masjids/{masjid}')->scopeBindings()->group(function () {
            // Masjid administrators: settings, iqamah and Ramadan announcements.
            Route::middleware('can:manage-masjid,masjid')->group(function () {
                Route::put('/', [MasjidController::class, 'update']);
                Route::get('iqamah-rules', [IqamahRuleController::class, 'index']);
                Route::post('iqamah-rules', [IqamahRuleController::class, 'store']);
                Route::delete('iqamah-rules/{iqamahRule}', [IqamahRuleController::class, 'destroy']);
                Route::put('ramadan/{hijriYear}', [RamadanController::class, 'update'])->whereNumber('hijriYear');
            });

            // Admins and treasurers: money.
            Route::middleware('can:manage-finance,masjid')->prefix('admin')->group(function () {
                Route::get('donations', [Admin\DonationController::class, 'index']);
                Route::post('donations', [Admin\DonationController::class, 'store']);
                Route::get('donations/{donation}', [Admin\DonationController::class, 'show']);
                Route::post('donations/{donation}/complete', [Admin\DonationController::class, 'complete']);
                Route::post('donations/{donation}/cancel', [Admin\DonationController::class, 'cancel']);
                Route::post('donations/{donation}/refund', [Admin\DonationController::class, 'refund']);

                Route::get('transactions', [Admin\TransactionController::class, 'index']);
                Route::post('transactions', [Admin\TransactionController::class, 'store']);
                Route::get('transactions/{transaction}', [Admin\TransactionController::class, 'show']);
                Route::post('transactions/{transaction}/void', [Admin\TransactionController::class, 'void']);
                Route::get('transactions/{transaction}/attachment', [Admin\TransactionController::class, 'attachment']);

                Route::get('funds', [Admin\FinanceController::class, 'funds']);
                Route::post('funds', [Admin\FinanceController::class, 'storeFund']);
                Route::put('funds/{fund}', [Admin\FinanceController::class, 'updateFund']);
                Route::get('categories', [Admin\FinanceController::class, 'categories']);
                Route::post('categories', [Admin\FinanceController::class, 'storeCategory']);
                Route::get('reports/summary', [Admin\FinanceController::class, 'summary']);
            });
        });
    });
});
