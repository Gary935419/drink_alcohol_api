<?php

namespace App\Http\Controllers\v1;
require_once(dirname(dirname(dirname(__DIR__))) . '/Libs/CODE.php');
use App\Http\Controllers\Controller;
use App\Models\MemberModel;
use App\Models\SmsModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class MemberController extends Controller
{
    /**
     *  1-1-2
     * 用户注册
     */
    public function actionMemberRegist()
    {
        try {
            $paramsAll = request()->all();

            //model的初始化处理
            $SmsModel = new SmsModel($this);
            $MemberModel = new MemberModel($this);

            //手机号码验证
            if (!isset($paramsAll['mobile'])||empty($paramsAll['mobile'])) {
                throw new \OneException(7);
            }
            $mobile = trim($paramsAll['mobile']);
            if(!preg_match("/^1[34578]\d{9}$/",$mobile)){
                throw new \OneException(4);
            }

            //注册密码验证
            if (!isset($paramsAll['passwd'])||empty($paramsAll['passwd'])) {
                throw new \OneException(8);
            }
            $passwd = $paramsAll['passwd'];

            //验证码验证
            if (!isset($paramsAll['verificationcode'])||empty($paramsAll['verificationcode'])) {
                throw new \OneException(9);
            }
            $verificationcode = trim($paramsAll['verificationcode']);

            //设备id验证
            if (!isset($paramsAll['registration_id'])||empty($paramsAll['registration_id'])) {
                throw new \OneException(12);
            }
            $registration_id = $paramsAll['registration_id'];

            //数据库事务处理
            DB::beginTransaction();

            //验证码是否过期、是否使用
            $code_info_count_effective = $SmsModel->select_mobile_code_effective($mobile);
            if (empty($code_info_count_effective)){
                throw new \OneException(10);
            }

            //验证手机验证码是否正确
            if ($verificationcode != $code_info_count_effective['vccode']) {
                throw new \OneException(15);
            }

            $member_mobile_info_count = $MemberModel->select_member_mobile_info($mobile);
            if(!empty($member_mobile_info_count)){
                throw new \OneException(14);
            }

            //邀请码 可选测试   无邀请默认为系统邀请
            //系统邀请码为：123456789
            $invite_code = isset($paramsAll['invite_code']) ? $paramsAll['invite_code'] : '123456789';
            if($invite_code!='123456789') {
                $check_invite_code_count = $MemberModel->select_member_invite_code($invite_code);
                if(empty($check_invite_code_count)){
                    throw new \OneException(11);
                }
                //会员邀请信息insert处理
                $insert_member_invite_record_arr = array();
                $insert_member_invite_record_arr['create_time'] = time();
                $insert_member_invite_record_arr['create_user'] = $mobile;
                $insert_member_invite_record_arr['invite_code'] = $invite_code;
                $insert_member_invite_record_arr['is_effective'] = 0;
            }

            //会员卡号生成
            $make_code = new \CODE();
            $RandomNumber = $make_code->encodeID(getRandomNumber(8),6);
            $card_pre = 'HYK';
            $card_vc = substr(md5($card_pre.$RandomNumber),0,2);
            $card_vc = strtoupper($card_vc);
            $member_card_number = $card_pre.$RandomNumber.$card_vc;

            //会员信息insert处理
            $insert_member_arr = array();
            $insert_member_arr['member_card_number'] = $member_card_number;
            $insert_member_arr['is_lock'] = 0;
            //获得初始会员卡数据  初始会员卡的信息写死，后台可统一管理其余会员卡等级 初始会员卡等级不可修改
            $member_level = $MemberModel->select_member_level_initialization();
            $insert_member_arr['mlid'] = $member_level['mlid'];
            $insert_member_arr['member_exppoints'] = $member_level['level_experience'];
            $insert_member_arr['inviter_code'] = $invite_code;
            $insert_member_arr['member_points'] = 0;
            $insert_member_arr['registration_id'] = $registration_id;
            $insert_member_arr['token'] = $this->createToken();
            $insert_member_arr['create_time'] = time();
            $insert_member_arr['create_user'] = $mobile;
            $insert_member_arr['member_tel'] = $mobile;
            $insert_member_arr['member_password'] = hashPassword($passwd);
            $insert_member_arr['invite_code'] = $RandomNumber;
            $result_mid = $MemberModel->insert_member($insert_member_arr);

            //验证码处理为已使用
            $SmsModel->update_mobile_code($mobile);

            if ($result_mid){
                DB::commit();
                //会员邀请信息insert处理
                $insert_member_invite_record_arr['mid']  = $result_mid;
                $MemberModel->insert_member_invite_record($insert_member_invite_record_arr);
                return response()->json(self::ok($insert_member_arr));
            }else{
                throw new \OneException(13);
            }
        } catch (\OneException $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage() . chr(10) . $e->getTraceAsString());
        }
    }

    /**
     *  1-1-3
     * 用户忘记密码
     */
    public function actionMemberResetPassword()
    {
        try {
            $paramsAll = request()->all();

            //model的初始化处理
            $SmsModel = new SmsModel($this);
            $MemberModel = new MemberModel($this);

            //手机号码验证
            if (!isset($paramsAll['mobile'])||empty($paramsAll['mobile'])) {
                throw new \OneException(7);
            }
            $mobile = trim($paramsAll['mobile']);
            if(!preg_match("/^1[34578]\d{9}$/",$mobile)){
                throw new \OneException(4);
            }

            //密码验证
            if (!isset($paramsAll['passwd'])||empty($paramsAll['passwd'])) {
                throw new \OneException(8);
            }
            $passwd = $paramsAll['passwd'];

            //验证码验证
            if (!isset($paramsAll['verificationcode'])||empty($paramsAll['verificationcode'])) {
                throw new \OneException(9);
            }
            $verificationcode = trim($paramsAll['verificationcode']);

            //数据库事务处理
            DB::beginTransaction();

            //验证是否为有效注册手机号
            $member_mobile_info_count = $MemberModel->select_member_mobile_info($mobile);
            if(empty($member_mobile_info_count)){
                throw new \OneException(16);
            }

            //验证码是否过期、是否使用
            $code_info_count_effective = $SmsModel->select_mobile_code_effective($mobile);
            if (empty($code_info_count_effective)){
                throw new \OneException(10);
            }

            //验证手机验证码是否正确
            if ($verificationcode != $code_info_count_effective['vccode']) {
                throw new \OneException(15);
            }

            //重置密码处理
            $modify_member_arr = array();
            $modify_member_arr['token'] = $this->createToken();
            $modify_member_arr['modify_time'] = time();
            $modify_member_arr['modify_user'] = $mobile;
            $modify_member_arr['member_password'] = hashPassword($passwd);
            $MemberModel->update_member_passwd($modify_member_arr,$mobile);

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

    /**
     *  1-1-4
     * 用户登录
     */
    public function actionMemberLogin()
    {
        try {
            $paramsAll = request()->all();

            //model的初始化处理
            $MemberModel = new MemberModel($this);

            //手机号码验证
            if (!isset($paramsAll['mobile'])||empty($paramsAll['mobile'])) {
                throw new \OneException(7);
            }
            $mobile = trim($paramsAll['mobile']);
            if(!preg_match("/^1[34578]\d{9}$/",$mobile)){
                throw new \OneException(4);
            }

            //登录密码验证
            if (!isset($paramsAll['passwd'])||empty($paramsAll['passwd'])) {
                throw new \OneException(8);
            }
            $passwd = hashPassword($paramsAll['passwd']);

            //初始化登录信息
            $this->clearAuth();

            //数据库事务处理
            DB::beginTransaction();

            //验证账号密码是否正确
            $select_member_login_info = $MemberModel->select_member_login_info($mobile,$passwd);
            if(empty($select_member_login_info)){
                throw new \OneException(17);
            }

            //验证账户是否锁定
            if (!empty($select_member_login_info['is_lock'])){
                throw new \OneException(18);
            }

            //token更新处理
            $modify_member_arr = array();
            $modify_member_arr['token'] = $this->createToken();
            $modify_member_arr['modify_time'] = time();
            $modify_member_arr['modify_user'] = $mobile;
            $MemberModel->update_member_passwd($modify_member_arr,$mobile);

            // Redis设定
            $payload = json_encode([
                'mid' => $select_member_login_info['mid']
            ]);
            \Redis::set('t:'. $modify_member_arr['token'], $payload);
            \Redis::expire('t:'. $modify_member_arr['token'], config('auth.ttl',60));
            \Redis::disconnect();

            Session::put('mid', $select_member_login_info['mid']);
            Session::put('token', $modify_member_arr['token']);

            DB::commit();
            return response()->json(self::ok($modify_member_arr));
        } catch (\OneException $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage() . chr(10) . $e->getTraceAsString());
        }
    }
}
