<?php

namespace App\Http\Controllers\v1;
require_once(dirname(dirname(dirname(__DIR__))) . '/Libs/JSMS.php');
use App\Http\Controllers\Controller;
use App\Models\SmsModel;
use Illuminate\Support\Facades\DB;

class SmsController extends Controller
{
    /**
     *  1-1-1
     * 发送短信验证码
     */
    public function actionSmsSend()
    {
        try {
            $paramsAll = request()->all();

            if (!isset($paramsAll['mobile'])||empty($paramsAll['mobile'])) {
                throw new \OneException(7);
            }
            $mobile = trim($paramsAll['mobile']);
            if(!preg_match("/^1[34578]\d{9}$/",$mobile)){
                throw new \OneException(4);
            }

            //数据库事务处理
            DB::beginTransaction();
            //获取当前是否有未过期的验证码
            $SmsModel = new SmsModel($this);
            $code_info_count = $SmsModel->select_mobile_code($mobile);
            if (!empty($code_info_count)){
                throw new \OneException(5);
            }

            //生成随机验证码
            $code = getRandomNumber(4);
            //极光短信发送
            $app_key = config('config.app_key');
            $app_secret = config('config.app_secret');
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
            $insert_sms_arr['expired_time'] = time() + 900;
            $SmsModel->insert_mobile_code($insert_sms_arr);

            DB::commit();
            return response()->json(self::ok());

        } catch (\OneException $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage() . chr(10) . $e->getTraceAsString());
        }
    }
}
