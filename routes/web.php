<?php

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

//Route::get('/', function () {
//    return view('welcome');
//});

Auth::routes();

//Route::get('/home', 'HomeController@index');

Route::get('/','HtmlToPdfController@index')->middleware('auth');

Route::group(['middleware' => 'auth', 'as'=>'admin.', 'prefix' => 'admin'], function() {
    Route::get('htmltopdf/download/{id}','HtmlToPdfController@download')->name('htmltopdf.download');
    Route::resource('htmltopdf', 'HtmlToPdfController');
});