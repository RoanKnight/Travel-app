<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

Route::get('/', function () {
  return view('welcome');
});

Route::get('/dashboard', function () {
  return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
  Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
  Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
  Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/flightInfo', function () {
  $amadeusService = app()->make(\App\Services\AmadeusService::class);

  // Use dates in the future
  $departureDate = now()->addWeek()->toDateString();
  $returnDate = now()->addMonth()->toDateString();

  $cacheKey = "flightOffers:$departureDate:$returnDate";
  $processedResponse = Cache::remember($cacheKey, 60, function () use ($amadeusService, $departureDate, $returnDate) {
    $response = $amadeusService->getFlightOffers('NYC', 'LON', $departureDate, $returnDate, 1);

    return collect($response['data'])->map(function ($offer) {
      return [
        'price' => $offer['price']['total'],
        'departure' => [
          'terminal' => $offer['itineraries'][0]['segments'][0]['departure']['terminal'] ?? 'Terminal information not available',
          'at' => substr($offer['itineraries'][0]['segments'][0]['departure']['at'], 11, 5),
        ],
        'arrival' => [
          'terminal' => $offer['itineraries'][0]['segments'][0]['arrival']['terminal'] ?? 'Terminal information not available',
          'at' => substr($offer['itineraries'][0]['segments'][0]['arrival']['at'], 11, 5),
        ],
      ];
    })->take(10);
  });

  return response()->json($processedResponse);
});

Route::get('/clear-cache', function () {
  // Use dates in the future
  $departureDate = now()->addMonth()->toDateString();
  $returnDate = now()->addMonth()->addWeek()->toDateString();

  $cacheKey = "flightOffers:$departureDate:$returnDate";

  // Clear the cache for the specific key
  Cache::forget($cacheKey);

  return response()->json(['message' => 'Cache cleared']);
});

require __DIR__ . '/auth.php';
