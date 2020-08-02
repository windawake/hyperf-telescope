<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;



Router::addGroup('/telescope/telescope-api', function(){
    Router::post('/requests', 'Wind\Telescope\Controller\RequestsController@index');
    Router::get('/requests/{id}', 'Wind\Telescope\Controller\RequestsController@show');
    Router::post('/queries', 'Wind\Telescope\Controller\QueriesController@index');
    Router::get('/queries/{id}', 'Wind\Telescope\Controller\QueriesController@show');
});

Router::GET('/telescope/{view}', 'Wind\Telescope\Controller\ViewController@index');
Router::GET('/telescope/{view}/{id}', 'Wind\Telescope\Controller\ViewController@index');