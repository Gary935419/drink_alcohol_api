<?php

namespace App\Http\Controllers\v1;
use App\Http\Controllers\Controller;
use App\Models\SmsModel;

class SmsController extends Controller
{
    /**
     *  1-1-1
     * 发送短信验证码
     */
    public function actionSendSms()
    {
        try {
            $paramsAll = request()->all();
            paramsCheck($paramsAll, array('mobile'));
            $mobile = trim($paramsAll['mobile']);
            if(!preg_match("/^1[34578]\d{9}$/",$mobile)){
                throw new \OneException(4);
            }
            //获取当前是否有未过期的验证码
            $SmsModel = new SmsModel($this);
            $code_info_count = $SmsModel->select_mobile_code($mobile);
            if ($code_info_count>=1){
                throw new \OneException(5);
            }
            //生成随机验证码
            $code = "";
            for ($i = 0; $i < 4; $i++) {
                if($i == 0){
                    $code .= rand(1, 9);
                }
                else{
                    $code .= rand(0, 9);
                }
            }

            //TODO 测试验证码
            $code = '1234';

            //极光短信发送
            $app_key = config(app()->environment() . '/config.app_key');
            $app_secret = config(app()->environment() . '/config.app_secret');
            $client = new \JSMS($app_key, $app_secret,[ 'disable_ssl' => true ]);
            $temp_para = array('code'=>$code);
            $res = $client->sendMessage($mobile, 156203,$temp_para);
            if($res["http_code"]!=200){
                throw new \OneException(6);
            }

            //验证码insert处理
            $insert_sms_arr = array();
            $insert_sms_arr['vccode'] = $code;
            $insert_sms_arr['vctel'] = $mobile;
            $insert_sms_arr['is_use'] = 0;
            $insert_sms_arr['create_time'] = time();
            $insert_sms_arr['expired_time'] = time() + 300;
            $SmsModel->insert_mobile_code($insert_sms_arr);
            
            $response = array();
            return response()->json(self::ok($response));
        } catch (\OneException $e) {
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            return $this->error($e->getMessage() . chr(10) . $e->getTraceAsString());
        }
    }
}
