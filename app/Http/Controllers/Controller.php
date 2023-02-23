<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public $OS;
    public $APP_VERSION;
    protected $VERSIONUP_MESSAGE;
    protected $MAINTENANCE_FLG = 0;   //0:不需要、1:需要

    public function __construct()
    {
        try {
            //获取请求参数
            $request_params = request()->post();
            paramsCheck($request_params, array('APP_VERSION','OS','SIGN'));
            $this->APP_VERSION = $request_params['APP_VERSION'];
            $this->OS = $request_params['OS'];
            $sign = $request_params['SIGN'];
            // 参数非法check
            if ($sign != $this->getSign($request_params)) {
                throw new \OneException(3);
            }
            // 最小版本check
            $this->check_app_version();
        } catch (\OneException $e) {
            $this->error($e->getMessage(), true);
        } catch (\Exception $e) {
            $this->error($e->getMessage() . chr(10) . $e->getTraceAsString(), true);
        }
    }

    /**
     * 获得签名认证
     */
    public function getSign($data = array())
    {
        ksort($data);
        $str = "";
        foreach ($data as $key => $value) {
            if ($value != "" && $key != "SIGN") {
                $str .= $value;
            }
        }
        return md5($str);
    }

    /**
     * app最小版本check
     */
    public function check_app_version()
    {
        $os = $this->OS;
        $app_version = $this->APP_VERSION;
        $version_check = true;
        $APP_VERSIONS = config(app()->environment() . '/config.APP_VERSION');
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
            $this->MAINTENANCE_FLG = 1;
            $STORE_NAME = config(app()->environment() . '/config.APP_STORE_NAME');
            if ($os == 'android') {
                $STORE_NAME = config(app()->environment() . '/config.GOOGLE_PLAY_NAME');
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
        $MEMBER_ID = Session::get('mid');
        if ($MEMBER_ID != '') {
            $LOGIN_STATUS = 0;
        }
        $response = array(
            'STATUS' => 0,
            'MAINTENANCE_FLG' => $this->MAINTENANCE_FLG,
            'LOGIN_STATUS' => $LOGIN_STATUS,
            'SYSTEM_DATE' => date('Y-m-d H:i:s'),
        );
        if ($MEMBER_ID != '') {
            $response['MEMBER_ID'] = $MEMBER_ID;
        }
        if ($this->MAINTENANCE_FLG == 1) {
            $response['MESSAGE_ARRAY'][] = array('MESSAGE' => $this->VERSIONUP_MESSAGE);
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
        $MEMBER_ID = Session::get('mid');
        if ($MEMBER_ID != '') {
            $LOGIN_STATUS = 0;
        }

        $response = array(
            'STATUS' => 1,
            'MAINTENANCE_FLG' => $this->MAINTENANCE_FLG,
            'LOGIN_STATUS' => $LOGIN_STATUS,
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

}
