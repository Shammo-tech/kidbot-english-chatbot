<?php
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChatController::class,'index']);
Route::post('/start', [ChatController::class,'start']);
Route::post('/chat', [ChatController::class,'send']);
Route::post('/reset', [ChatController::class,'reset']);
Route::post('/answer', [ChatController::class,'answer']);
Route::get('/health', fn() => response()->json(['ok'=>true]));