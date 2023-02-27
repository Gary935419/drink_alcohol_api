<?php

namespace App\Http\Controllers\v1;
require_once(dirname(dirname(dirname(__DIR__))) . '/Libs/CODE.php');
use App\Http\Controllers\Controller;

class MemberController extends Controller
{
    /**
     *  1-1-4
     * 用户登录
     */
    public function actionMemberLogin()
    {
        try {
            $paramsAll = request()->all();

            //初始化登录信息
            $this->clearAuth();

            $response = array();
            return response()->json(self::ok($response));
        } catch (\OneException $e) {
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            return $this->error($e->getMessage() . chr(10) . $e->getTraceAsString());
        }
    }

}
