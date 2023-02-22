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
            // 最小版本check
            $this->check_app_version();
        } catch (\OneException $e) {
            $this->error($e->getMessage(), true);
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
            $lang_msg = trans($error_key,[],'ja');
            $this->VERSIONUP_MESSAGE = sprintf($lang_msg, $STORE_NAME);
            $this->ok();
        }
    }
    /**
     * api正常情况下返回处理
     */
    public function ok($DATA = null)
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

        return $response;
    }

    /**
     * api异常情况下返回处理
     */
    public function error($MESSAGE)
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

        return $response;
    }
}
