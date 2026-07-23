<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\EpaperClipShortController;
use App\Http\Controllers\Web\EpaperClipSignController;
use App\Http\Controllers\Web\OgImageController;
use App\Http\Controllers\Web\SubmissionController;
use App\Http\Controllers\Web\ArticleLikeController;
use App\Http\Controllers\Web\ArticleReadController;
use App\Http\Controllers\Web\ArticleSingleController;
use App\Http\Controllers\Web\ArticleSlugRedirectController;
use App\Http\Controllers\Web\AssetLinksController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\EpaperArchiveController;
use App\Http\Controllers\Web\EpaperLikeController;
use App\Http\Controllers\Web\EpaperReadController;
use App\Http\Controllers\Web\EpaperSingleController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ManifestController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PwaIconController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\TagController;
use App\Http\Controllers\Web\VideoArchiveController;
use App\Http\Controllers\Web\VideoSingleController;
use Illuminate\Support\Facades\Route;

Route::get('/storage/{path?}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('storage.serve');

Route::redirect('/admin/login', '/login');
Route::permanentRedirect('/admin/login/', '/login');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/.well-known/assetlinks.json', AssetLinksController::class)->name('assetlinks');

Route::get('/manifest.json', ManifestController::class)->name('manifest');
Route::get('/pwa/icon/{size}', PwaIconController::class)->whereNumber('size')->name('pwa.icon');

Route::get('/tnf_news/{slug}', ArticleSlugRedirectController::class)->where('slug', '.*');

Route::middleware(['cache.public', 'cache.headers'])->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/epaper', EpaperArchiveController::class)->name('epaper.index');
    Route::get('/epaper/{edition:slug}', EpaperSingleController::class)->name('epaper.show');
    Route::get('/c/{token}', EpaperClipShortController::class)
        ->where('token', '[A-Za-z0-9_-]+')
        ->name('epaper.clip.short');
    Route::get('/videos', VideoArchiveController::class)->name('videos.index');
    Route::get('/videos/{video:slug}', VideoSingleController::class)->name('videos.show');
    Route::get('/search', SearchController::class)->name('search');

    Route::get('/category/{category:slug}', CategoryController::class)->name('category.show');
    Route::get('/tag/{tag:slug}', TagController::class)->name('tag.show');

    Route::get('/about-us', fn () => app()->call(PageController::class, ['slug' => 'about-us']))->name('page.about');
    Route::get('/contact-us', [ContactController::class, 'show'])->name('page.contact');
    Route::get('/privacy-policy', fn () => app()->call(PageController::class, ['slug' => 'privacy-policy']))->name('page.privacy');
    Route::get('/terms-of-use', fn () => app()->call(PageController::class, ['slug' => 'terms-of-use']))->name('page.terms');
});

Route::get('/design-preview', function () {
    return view('pages.design-preview');
})->name('design.preview');

Route::post('/contact-us', [ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('page.contact.submit');

Route::post('/n/{article}/read', ArticleReadController::class)
    ->whereNumber('article')
    ->middleware('throttle:120,1')
    ->name('article.read');

Route::post('/n/{article}/like', ArticleLikeController::class)
    ->whereNumber('article')
    ->middleware('throttle:60,1')
    ->name('article.like');

Route::post('/epaper/{edition:slug}/read', EpaperReadController::class)
    ->middleware('throttle:120,1')
    ->name('epaper.read');

Route::post('/epaper/{edition:slug}/like', EpaperLikeController::class)
    ->middleware('throttle:60,1')
    ->name('epaper.like');

Route::post('/epaper/{edition:slug}/sign-clip', EpaperClipSignController::class)
    ->middleware('throttle:30,1')
    ->name('epaper.sign-clip');

Route::middleware('throttle:og')->group(function () {
    Route::get('/og/default.jpg', [OgImageController::class, 'default'])->name('og.default');
    Route::get('/og/article/{article}', [OgImageController::class, 'article'])->name('og.article');
    Route::get('/og/video/{video}', [OgImageController::class, 'video'])->name('og.video');
    Route::get('/pdf-report/{edition}/page-og', [OgImageController::class, 'epaperPage'])->name('og.epaper.page');
    Route::get('/pdf-report/{edition}/clip-og', [OgImageController::class, 'epaperClip'])->name('og.epaper.clip');
});

Route::get('/dashboard', function () {
    return redirect(auth()->user()->homeUrl());
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/my-account', AccountController::class)
    ->middleware(['auth'])
    ->name('account');

Route::middleware(['auth'])->prefix('my-account')->name('account.')->group(function () {
    Route::post('/submissions', [SubmissionController::class, 'store'])
        ->middleware('throttle:submissions')
        ->name('submissions.store');
    Route::post('/submissions/upload-image', [SubmissionController::class, 'uploadImage'])
        ->middleware('throttle:submissions')
        ->name('submissions.upload-image');
    Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['cache.public', 'cache.headers'])->group(function () {
    Route::get('/n/{article}', ArticleSingleController::class)
        ->whereNumber('article')
        ->name('article.show');
});

Route::middleware(['cache.public', 'cache.headers'])->group(function () {
    Route::get('/{slug}', ArticleSlugRedirectController::class)
        ->where('slug', '[a-z0-9\-]+');
});
