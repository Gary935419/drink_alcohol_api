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
    //用户登录
    $app->post('member/login', 'v1\MemberController@member_login');
    //用户注册
    $app->post('member/register', 'v1\MemberController@member_register');
    //用户登出
    $app->post('member/logout', 'v1\MemberController@member_logout');
    //发送验证码
    $app->post('sms/send_sms', 'v1\SmsController@actionSendSms');
});