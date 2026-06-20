<?php

use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\PdfCallbackController;
use App\Http\Controllers\Api\V1\PdfController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\VideoController;
use App\Http\Middleware\VerifyPdfCallbackSecret;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('news', [NewsController::class, 'index']);
    Route::get('news/{article}', [NewsController::class, 'show']);

    Route::get('videos', [VideoController::class, 'index']);
    Route::get('videos/{video}', [VideoController::class, 'show']);

    Route::get('pdfs', [PdfController::class, 'index']);
    Route::get('pdfs/{edition}', [PdfController::class, 'show']);

    Route::post('internal/pdf-job-complete', PdfCallbackController::class)
        ->middleware(VerifyPdfCallbackSecret::class)
        ->name('api.pdf.callback');

    Route::middleware('auth:web')->group(function () {
        Route::get('pdfs/{edition}/access', [PdfController::class, 'access']);

        Route::post('submissions', [SubmissionController::class, 'store'])
            ->middleware('throttle:submissions');
        Route::get('submissions/mine', [SubmissionController::class, 'mine']);

        Route::post('submissions/{submission}/approve', [SubmissionController::class, 'approve']);
        Route::post('submissions/{submission}/reject', [SubmissionController::class, 'reject']);
    });
});
