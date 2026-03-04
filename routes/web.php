<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CvAnalysisController;
use App\Http\Controllers\JobMatchingController;

Route::get('/', function () {
    $stats = [
        'total_analyzed' => \App\Models\CvAnalysis::count(),
        'avg_score' => \App\Models\CvAnalysis::avg('overall_score') ?? 0,
        'top_industry' => \App\Models\CvAnalysis::select('industry')->groupBy('industry')->orderByRaw('COUNT(*) DESC')->first()?->industry ?? 'N/A'
    ];
    return view('welcome', $stats);
});

Route::post('/analyze', [CvAnalysisController::class , 'analyze']);
Route::post('/generate-cv', [CvAnalysisController::class , 'generate']);

Route::get('/job-matching', [JobMatchingController::class, 'index'])->name('job-matching');
Route::post('/job-matching/extract', [JobMatchingController::class, 'extract']);
