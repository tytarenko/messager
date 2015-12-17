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

Route::group(
    [
        'prefix' => 'api',
        'namespace' => 'Api'
    ],
    function()
    {
        Route::group(
            [
                'prefix' => 'v1',
            ],
            function() {

                Route::resource(
                    'users',
                    'UsersController',
                    ['except' => ['create', 'edit']]
                );

                Route::resource(
                    'users.messages',
                    'MessagesController',
                    ['except' => ['create', 'edit']]
                );

            }
        );
    }
);

Route::get('/', function () {
    return view('welcome');
});
