<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function(){
    return "Workbot 1.0";
});

$router->post('config', "TasksController@setConfig");

$router->get('trigger-alerts', "TasksController@triggerAlerts");
