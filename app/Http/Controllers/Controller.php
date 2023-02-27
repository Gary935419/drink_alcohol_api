<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public $OS;
    public $MEMBER_INFO = array();
    public $APP_VERSION;
    //强制版本更新
    protected $FORCE_VERSION_UP_FLG_FLG = 0;   //0:不需要、1:需要
    protected $VERSIONUP_MESSAGE;

    public function __construct()
    {
        try {
            //获取请求参数
            $request_params = request()->post();
            paramsCheck($request_params, array('APP_VERSION','OS'));
            $this->APP_VERSION = $request_params['APP_VERSION'];
            $this->OS = $request_params['OS'];
            // 最小版本check
            $this->check_app_version();
        } catch (\OneException $e) {
            $this->error($e->getMessage(), true);
        } catch (\Exception $e) {
            $this->error($e->getMessage() . chr(10) . $e->getTraceAsString(), true);
        }
    }

    /**
     * app最小版本check
     */
    public function check_app_version()
    {
        $os = $this->OS;
        $app_version = $this->APP_VERSION;
        $version_check = true;
        $APP_VERSIONS = config('config.APP_VERSION');
        if ($app_version != '') {
            if ($os == 'ios') {
                if ($APP_VERSIONS['MIN_APP_VERSION_IOS'] > $app_version) {
                    $version_check = false;
                }
            } else if ($os == 'android') {
                if ($APP_VERSIONS['MIN_APP_VERSION_ANDROID'] > $app_version) {
                    $version_check = false;
                }
            }
        }
        if (!$version_check) {
            $this->FORCE_VERSION_UP_FLG_FLG = 1;
            $STORE_NAME = config('config.APP_STORE_NAME');
            if ($os == 'android') {
                $STORE_NAME = config('config.GOOGLE_PLAY_NAME');
            }
            $error_key = sprintf("errors.ERROR%04d", 1);
            $lang_msg = trans($error_key, [], 'ja');
            $this->VERSIONUP_MESSAGE = sprintf($lang_msg, $STORE_NAME);
            $this->ok(array(),true);
        }
    }

    /**
     * api正常情况下返回处理
     */
    public function ok($DATA=null,$force=false)
    {
        $LOGIN_STATUS = 1;
        $MEMBER_ID = Session::get('member_number');
        if ($MEMBER_ID != '') {
            $LOGIN_STATUS = 0;
        }
        $response = array(
            'STATUS' => 0,
            'FORCE_VERSION_UP_FLG_FLG' => $this->FORCE_VERSION_UP_FLG_FLG,
            'LOGIN_STATUS' => $LOGIN_STATUS,
            'MEMBER_ID' => '',
            'MESSAGE' => '',
            'SYSTEM_DATE' => date('Y-m-d H:i:s'),
        );
        if ($MEMBER_ID != '') {
            $response['MEMBER_ID'] = $MEMBER_ID;
        }
        if ($this->FORCE_VERSION_UP_FLG_FLG == 1) {
            $response['MESSAGE'] = $this->VERSIONUP_MESSAGE;
        }

        if ($DATA != null) {
            $response = array_merge($DATA, $response);
        }
        if ($force) {
            echo json_encode($response);
            exit();
        }

        return $response;
    }

    /**
     * api异常情况下返回处理
     */
    public function error($MESSAGE,$force=false)
    {
        $LOGIN_STATUS = 1;
        $MEMBER_ID = Session::get('member_number');
        if ($MEMBER_ID != '') {
            $LOGIN_STATUS = 0;
        }

        $response = array(
            'STATUS' => 1,
            'FORCE_VERSION_UP_FLG_FLG' => $this->FORCE_VERSION_UP_FLG_FLG,
            'LOGIN_STATUS' => $LOGIN_STATUS,
            'MEMBER_ID' => '',
            'MESSAGE' => $MESSAGE,
            'SYSTEM_DATE' => date('Y-m-d H:i:s'),
        );

        if ($MEMBER_ID != '') {
            $response['MEMBER_ID'] = $MEMBER_ID;
        }
        if ($force) {
            echo json_encode($response);
            exit();
        }

        return response()->json($response);
    }

    /**
     * 初始化登录信息
     */
    public function clearAuth()
    {
        Session::remove('member_number');
        Session::flush();
    }
}
