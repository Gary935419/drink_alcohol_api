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
    //1-1-2 修改用户信息
    $app->post('member/member_change_info', 'v1\MemberController@actionChangeMemberInfo');
    //1-1-3 获得饮酒测试履历
    $app->post('member/member_drinking_history', 'v1\MemberController@actionDrinkingHistory');
    //1-1-4 获取用户信息
    $app->post('member/member_info', 'v1\MemberController@actionMemberInfo');
    //1-1-5 问题咨询反馈
    $app->post('member/member_consultation_feedback', 'v1\MemberController@actionConsultationFeedback');
});