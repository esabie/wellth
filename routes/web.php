<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/faqs', FaqController::class)->name('faqs');

Route::get('/shop', [ProductController::class, 'index'])->name('shop.index');
Route::get('/shop/{product}', [ProductController::class, 'show'])->name('shop.show');

Route::redirect('/formulas/{formula}', '/shop/{formula}', 301)->name('formulas.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/loyalty-preview', [CheckoutController::class, 'previewLoyalty'])
    ->middleware('throttle:30,1')
    ->name('checkout.loyalty.preview');
Route::post('/checkout/promo', [CheckoutController::class, 'applyPromo'])
    ->middleware('throttle:20,1')
    ->name('checkout.promo.apply');
Route::delete('/checkout/promo', [CheckoutController::class, 'removePromo'])->name('checkout.promo.remove');
Route::post('/checkout/points', [CheckoutController::class, 'applyPoints'])
    ->middleware('throttle:20,1')
    ->name('checkout.points.apply');
Route::delete('/checkout/points', [CheckoutController::class, 'removePoints'])->name('checkout.points.remove');
Route::get('/checkout/{order}/pay', [CheckoutController::class, 'payment'])->name('checkout.payment');
Route::post('/checkout/{order}/pay', [CheckoutController::class, 'storePaymentProof'])
    ->middleware('throttle:10,1')
    ->name('checkout.payment.store');
Route::get('/checkout/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
