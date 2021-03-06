<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', 'WelcomeController@index');
Route::post('generate-site-map', ['as' => 'generate', 'uses' => 'SiteMapGeneratorController@generate']);


$this->app->bind('Generator', function()
{
    return new \App\Generators\SiteMapGenerator();
});