<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
Route::get('/', function () {
    return view('welcome');
});
//debug
Route::get('/hi', function (Request $request) {
    return response()->json(['message' => 'This is an hi route']);
});
