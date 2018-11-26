<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'category', /*'middleware' => 'assign.city'*/], function () {
    Route::get('index', 'CategoryController@index');
});

Route::group(['prefix' => 'news', /*'middleware' => 'assign.city'*/], function () {
    Route::get('channel', 'NewsController@getChannel');
    Route::get('list', 'NewsController@getNews');
    Route::get('detail', 'NewsController@getDetail');
});

Route::group(['prefix' => 'location', /*'middleware' => 'assign.city'*/], function () {
    Route::get('area', 'CategoryController@getArea');
    Route::get('street', 'CategoryController@getStreet');
});


Route::prefix('auth')->group(function($router) {
    $router->post('login', 'AuthController@login');
    $router->post('logout', 'AuthController@logout');

});

Route::middleware('refresh.token')->group(function($router) {
    $router->get('profile','UserController@profile');
});