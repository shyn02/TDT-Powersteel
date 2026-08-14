<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/products/', [PageController::class, 'products'])->name('products');
Route::get('/about/', [PageController::class, 'about'])->name('about');
Route::get('/track-order/', [PageController::class, 'trackOrder'])->name('track_order');
Route::get('/blog/', [PageController::class, 'blog'])->name('blog');
Route::get('/contact/', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy-policy/', [PageController::class, 'privacyPolicy'])->name('privacy_policy');
Route::get('/terms-and-conditions/', [PageController::class, 'termsAndConditions'])->name('terms_and_conditions');
Route::get('/referral/', [PageController::class, 'referral'])->name('referral');

// ---- PRODUCT CATEGORY PAGES ----
// One dynamic route serves every category by slug (admin-manageable).
// Must stay BELOW the other /products/... routes above so it doesn't swallow them.
Route::get('/products/{slug}/', [PageController::class, 'categoryDetail'])->name('category_detail');

// One dynamic route serves every blog post by slug (admin-manageable).
// Must stay BELOW /blog/ above.
Route::get('/blog/{slug}/', [PageController::class, 'blogDetail'])->name('blog_detail');

// ---- API ENDPOINTS FOR FORM SUBMISSIONS ----
Route::post('/api/submit-quote/', [QuoteController::class, 'submit'])->name('submit_quote');
Route::get('/api/quote-product-data/', [QuoteController::class, 'productData'])->name('quote_product_data');

// ---- LIVE CHAT ----
Route::match(['get', 'post'], '/api/chat/messages', [ChatController::class, 'messages'])->name('chat_messages_api');
Route::get('/api/chats/unassigned/', [ChatController::class, 'unassignedQueue'])->name('unassigned_chat_queue')
    ->middleware('auth');
Route::post('/api/chats/{sessionId}/claim/', [ChatController::class, 'claim'])->name('claim_chat')
    ->middleware('auth');
