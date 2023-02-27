<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

Route::group(['prefix' => 'v1'], function ($app) {
    $app->get('version', function () {
        return helper_test();
    });
    //1-1-1 用户登录
    $app->post('member/login', 'v1\MemberController@actionMemberLogin');
    //1-1-1 修改用户信息
    $app->post('member/member_change_info', 'v1\MemberController@actionChangeMemberInfo');
});