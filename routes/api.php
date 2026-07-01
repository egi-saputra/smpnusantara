<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Proktor\NilaiController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Admin\HeroSlideController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes untuk Proktor
Route::get('/rekap-nilai',     [NilaiController::class, 'rekapNilai']);
Route::get('/list-soal',       [NilaiController::class, 'listSoal']);
Route::get('/list-mapel',      [NilaiController::class, 'listMapel']);
Route::get('/list-kelas',      [NilaiController::class, 'listKelas']);
Route::post('/rekap-filtered', [NilaiController::class, 'rekapFiltered']);


// ──────────────────────────────────────────────────────────────────
// PUBLIC ROUTES
// ──────────────────────────────────────────────────────────────────

Route::prefix('v1')->group(function () {

    // Hero slides — dikonsumsi Vue HeroSection (tanpa auth)
    Route::get('/hero-slides', [HeroSlideController::class, 'publicIndex']);

    // Pendaftaran siswa baru
    Route::middleware(['throttle:registration'])->group(function () {
        Route::post('/registrations', [RegistrationController::class, 'store'])
            ->name('api.registrations.store');
    });

});


// ──────────────────────────────────────────────────────────────────
// ADMIN ROUTES
// ──────────────────────────────────────────────────────────────────
//
// BUG FIX: Ganti 'auth:sanctum' → 'auth' (web session guard).
//
// Kenapa? Halaman admin di-render via Inertia (web.php), sehingga
// autentikasi sudah berjalan melalui session Laravel biasa.
// Menggunakan auth:sanctum di sini memerlukan konfigurasi tambahan
// (SANCTUM_STATEFUL_DOMAINS + CSRF cookie handshake) yang tidak dilakukan
// oleh Axios default bootstrap.js, sehingga semua request admin
// mengembalikan 401 Unauthenticated.
//
// Dengan 'auth' (web guard), session cookie yang sudah ada dari login
// Inertia langsung dikenali — tanpa konfigurasi tambahan.
// ──────────────────────────────────────────────────────────────────

Route::prefix('v1/admin')
    ->middleware(['web', 'auth', 'role:admin'])
    ->name('api.admin.')
    ->group(function () {

        // Registrations
        Route::get('/registrations', [RegistrationController::class, 'index'])
            ->name('registrations.index');

        Route::patch('/registrations/{registration}/status', [RegistrationController::class, 'updateStatus'])
            ->name('registrations.update-status');

        // Hero Slides
        Route::get('/hero-slides',                [HeroSlideController::class, 'index']);
        Route::post('/hero-slides',               [HeroSlideController::class, 'store']);
        Route::post('/hero-slides/{heroSlide}',   [HeroSlideController::class, 'update']);
        Route::put('/hero-slides/{heroSlide}',    [HeroSlideController::class, 'update']);
        Route::get('/hero-slides/{heroSlide}',    [HeroSlideController::class, 'show']);
        Route::post('/hero-slides/{heroSlide}',   [HeroSlideController::class, 'update']);   // _method=PUT
        Route::delete('/hero-slides/{heroSlide}', [HeroSlideController::class, 'destroy']);
        Route::post('/hero-slides-reorder',       [HeroSlideController::class, 'reorder']);

    });