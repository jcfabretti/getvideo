<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GetVideosController;
use App\Http\Controllers\VideoController;

Route::get('/', function () {
    return view('index');
});

//Route::post('/download-video', [VideoController::class, 'downloadVideo'])->name('download.video');

// Rota principal para download
Route::get('/', [VideoController::class, 'index'])->name('videos.index');
Route::post('/formats', [VideoController::class, 'listFormats'])->name('videos.formats');
Route::post('/download', [VideoController::class, 'downloadVideo'])->name('videos.download');

// Novas rotas para conversão
Route::get('/converter', [VideoController::class, 'listarVideos'])->name('videos.listar');
Route::post('/converter/process', [VideoController::class, 'converterVideo'])->name('videos.converter');