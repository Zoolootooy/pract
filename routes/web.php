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

Route::get('/', 'HomeController@index')->name('home');

Route::get('/search/{query}', 'Api\SearchController@index')->name('search');
Route::get('/favorites', 'Api\SearchController@favorites')->name('search.favorites');
Route::post('/search', 'Api\SearchController@store')->name('search.store');
Route::post('/like', 'Api\SearchController@update')->name('search.update');

