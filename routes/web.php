<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*Route::get('/', function () {
    return view('welcome');
});*/

Route::get('/', [\App\Http\Controllers\DynamoDbController::class, 'index']);
Route::get('create_table', [\App\Http\Controllers\DynamoDbController::class, 'createTable']);

Route::get('create', [\App\Http\Controllers\DynamoDbController::class, 'create']);
Route::get('read', [\App\Http\Controllers\DynamoDbController::class, 'read']);
Route::get('update', [\App\Http\Controllers\DynamoDbController::class, 'update']);
Route::get('delete', [\App\Http\Controllers\DynamoDbController::class, 'delete']);

Route::get('delete_table', [\App\Http\Controllers\DynamoDbController::class, 'deleteTable']);
