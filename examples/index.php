<?php 

require 'vendor/autoload.php';
Selvi\Route::get('/', 'HomeController@index', function ($next, $args) {
    return $next();
});
Selvi\Framework::run();