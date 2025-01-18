<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\TransmissionController;




// 📢 PUBLIC ROUTES
Route::post('/alive', [AuthController::class, 'alive']);

// -- AUTH
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// 🔑 PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {
    
    // -- USER
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'getUser']);
        Route::post('/update', [UserController::class, 'updateUser']);
        Route::post('/delete', [UserController::class, 'deleteUser']);
    });
    
    // -- FRIENDS
    Route::prefix('friendships')->group(function () {
        Route::get('/', [FriendshipController::class, 'getFriends']);
        Route::post('/request', [FriendshipController::class, 'sendFriendRequest']);
        Route::post('/cancel', [FriendshipController::class, 'cancelFriendRequest']);
        Route::post('/accept', [FriendshipController::class, 'acceptFriendRequest']);
        Route::post('/reject', [FriendshipController::class, 'rejectFriendRequest']);
    });

    // -- TRANSMISSIONS
    Route::prefix('transmissions')->group(function () {
        Route::get('/', [TransmissionController::class, 'getTransmissions']);
        Route::post('/send', [TransmissionController::class, 'sendTransmission']);
        Route::post('/listen', [TransmissionController::class, 'listenToTransmission']);
    });

});
