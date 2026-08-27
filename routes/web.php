<?php

use App\Http\Controllers\PayFastController;
use Illuminate\Support\Facades\Route;

// Public, unauthenticated by necessity: PayFast's own servers call the ITN
// endpoint, and PayFast redirects the buyer's browser to return/cancel.
// Neither route may mutate payment/subscription state — see
// PayFastController for why. Authenticated checkout initiation lives under
// /api/v1/payments/payfast/* instead (routes/api.php).
Route::prefix('payments/payfast')->group(function () {
    Route::post('/itn', [PayFastController::class, 'itn'])->withoutMiddleware(['web']);
    Route::get('/return', [PayFastController::class, 'handleReturn']);
    Route::get('/cancel', [PayFastController::class, 'handleCancel']);
});

Route::view('/{any?}', 'application')->where('any', '.*');
