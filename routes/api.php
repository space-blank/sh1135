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

Route::group(['prefix' => 'v1'], function () {

    Route::middleware('auth:api')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::group(['prefix' => 'category'], function () {
        Route::get('list', 'CategoryController@index');
        Route::get('condition', 'CategoryController@searConfig');
    });

    Route::group(['prefix' => 'news'], function () {
        Route::get('list', 'NewsController@getNews');
        Route::get('detail', 'NewsController@getDetail');
    });

    Route::group(['prefix' => 'corp'], function () {
        Route::get('list', 'CorpController@getCorp');
    });
    //搜索的内容列表
    Route::group(['prefix' => 'information'], function () {
        Route::get('list', 'InformationController@getInformation');
        Route::get('detail', 'InformationController@getDetail');
    });

    Route::group(['prefix' => 'location'], function () {
        Route::get('/', 'IndexController@getCity');
        Route::get('change', 'IndexController@changeCity');
        Route::get('area', 'IndexController@getArea');
        Route::get('street', 'IndexController@getStreet');
    });


    Route::prefix('auth')->group(function($router) {
        $router->post('login', 'AuthController@login');
        $router->post('wxlogin', 'AuthController@weChatLogin');
        $router->post('logout', 'AuthController@logout');

    });

    Route::middleware('refresh.token')->group(function($router) {
        $router->get('profile','UserController@profile');
    });

});