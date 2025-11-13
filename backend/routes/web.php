<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return file_get_contents(public_path('dist/index.html'));
})->where('any', '.*');

Route::get('/test', function () {
    return response()->json(['message' => 'Laravel is reachable!']);
});