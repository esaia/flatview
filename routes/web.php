<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IrepController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServicesController;
use App\Support\IrepShortcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use IrepPlugin\FilamentIrep\Models\Reservation;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/work', fn() => Inertia::render('Work'))->name('work');
Route::get('/services', [ServicesController::class, 'index'])->name('services');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/project/{slug}', function ($slug) {
    $data = IrepShortcode::forProject($slug);

    abort_if($data === null, 404);

    return Inertia::render('IreProject360Page', [
        'projectId' => $slug,
        'projectData' => $data,
    ]);
})->name('project.show');

Route::get('/irep/shortcode-data/{slug}', function ($slug) {
    $data = IrepShortcode::forProject($slug);

    if ($data === null) {
        return response()->json(['success' => false, 'message' => 'Project not found'], 404);
    }

    return response()->json(['success' => true, 'data' => $data]);
});

Route::post('/irep/reservation', function (Request $request) {
    $validated = $request->validate([
        'flat_id' => ['required', 'integer', 'exists:flats,id'],
        'name'    => ['required', 'string', 'max:255'],
        'phone'   => ['nullable', 'string', 'max:255'],
        'email'   => ['nullable', 'email', 'max:255'],
        'comment' => ['nullable', 'string'],
    ]);

    $reservation = Reservation::create($validated);

    return response()->json(['success' => true, 'data' => $reservation]);
});

require __DIR__ . '/auth.php';
