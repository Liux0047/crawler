<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
	return View::make('hello');
});


Route::get('crawler/nea/{batch}/{sessionId}', 'CrawlerController@getNea');


Route::get('read-file', 'MatrixMapController@getReadFile');

Route::get('map-matrix/{sourcePos}/{destinationPos}', 'MatrixMapController@getMapMatrix');
Route::post('store-distance', 'MatrixMapController@recordDistance');

Route::get('bus-service/{batch}', 'BusController@getDistance');
Route::get('bus-service-test', 'BusController@testConnection');
Route::get('bus-service-stops', 'BusController@padStopId');


Route::get('bus-service-smrt', 'SMRTController@getDistance');

//map bus stops to stop ID
Route::get('bus-stop-map/{direction}', 'BusController@mapStopID');

//Transit link data
Route::get('bus-transitlink', 'TransitLinkController@getTransitLinkDistance');

Route::get('convert-grid', 'CrawlerController@convertGridIndex');


//random walking index
Route::get('map-walking-random/{sourcePos}/{destinationPos}', 'WalkingRandom@getWalkingRandom');
Route::post('store-distance-random', 'WalkingRandom@recordDistance');
Route::get('flush', 'WalkingRandom@getFlush');


//cea data
Route::get('cea', 'CeaController@getData');






