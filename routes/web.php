<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/products/', [PageController::class, 'products'])->name('products');
Route::get('/about/', [PageController::class, 'about'])->name('about');
Route::get('/blog/', [PageController::class, 'blog'])->name('blog');
Route::get('/contact/', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy-policy/', [PageController::class, 'privacyPolicy'])->name('privacy_policy');
Route::get('/terms-and-conditions/', [PageController::class, 'termsAndConditions'])->name('terms_and_conditions');
Route::get('/referral/', [PageController::class, 'referral'])->name('referral');

// ---- SEO ----
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

// ---- PRODUCT CATEGORY PAGES ----
// One dynamic route serves every category by slug (admin-manageable).
// Must stay BELOW the other /products/... routes above so it doesn't swallow them.
Route::get('/products/{slug}/', [PageController::class, 'categoryDetail'])->name('category_detail');

// One dynamic route serves every blog post by slug (admin-manageable).
// Must stay BELOW /blog/ above.
Route::get('/blog/{slug}/', [PageController::class, 'blogDetail'])->name('blog_detail');

// ---- API ENDPOINTS FOR FORM SUBMISSIONS ----
// Public, anonymous-visitor endpoints. Rate-limited (throttle) since these
// write directly to the DB with no login and are a spam/flood target.
Route::post('/api/submit-quote/', [QuoteController::class, 'submit'])
    ->name('submit_quote')
    ->middleware('throttle:10,1');
Route::get('/api/quote-product-data/', [QuoteController::class, 'productData'])->name('quote_product_data')
    ->middleware('throttle:60,1');

// ---- LIVE CHAT ----
Route::match(['get', 'post'], '/api/chat/messages', [ChatController::class, 'messages'])
    ->name('chat_messages_api')
    ->middleware('throttle:30,1');

// Staff-only chat pool. Requires login -> NOT exempted from CSRF (see
// bootstrap/app.php), unlike the two public routes above.
Route::get('/api/chats/unassigned/', [ChatController::class, 'unassignedQueue'])->name('unassigned_chat_queue')
    ->middleware('auth');
Route::post('/api/chats/{sessionId}/claim/', [ChatController::class, 'claim'])->name('claim_chat')
    ->middleware('auth');
