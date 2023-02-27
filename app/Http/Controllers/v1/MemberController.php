<?php

namespace App\Http\Controllers\v1;
require_once(dirname(dirname(dirname(__DIR__))) . '/Libs/CODE.php');
use App\Http\Controllers\Controller;
use App\Models\MemberModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class MemberController extends Controller
{
    /**
     *  1-1-1
     * 用户登录
     */
    public function actionMemberLogin()
    {
        try {
            $paramsAll = request()->all();

            //model的初始化处理
            $MemberModel = new MemberModel($this);

            $login_type = empty($paramsAll['login_type'])?0:$paramsAll['login_type'];//0:注册登录 1:账号登录

            //初始化登录信息
            $this->clearAuth();

            //数据库事务处理
            DB::beginTransaction();

            if (empty($login_type)){
                //用户身高验证
                if (!isset($paramsAll['member_height'])||empty($paramsAll['member_height'])) {
                    throw new \OneException(4);
                }
                $member_height = $paramsAll['member_height'];

                //用户体重验证
                if (!isset($paramsAll['member_weight'])||empty($paramsAll['member_weight'])) {
                    throw new \OneException(5);
                }
                $member_weight = $paramsAll['member_weight'];

                //用户年龄验证
                if (!isset($paramsAll['member_age'])||empty($paramsAll['member_age'])) {
                    throw new \OneException(6);
                }
                $member_age = $paramsAll['member_age'];
                $member_sex = empty($paramsAll['member_sex'])?0:$paramsAll['member_sex'];//0:男 1:女

                //用户标识id生成
                $make_code = new \CODE();
                $RandomNumber = $make_code->encodeID(getRandomNumber(8),6);
                $card_pre = 'HY';
                $card_vc = substr(md5($card_pre.$RandomNumber),0,2);
                $card_vc = strtoupper($card_vc);
                $member_number = $card_pre.$RandomNumber.$card_vc;

                $member_info = array();
                $member_info['member_number'] = $member_number;
                $member_info['member_height'] = $member_height;
                $member_info['member_weight'] = $member_weight;
                $member_info['member_sex'] = $member_sex;
                $member_info['member_age'] = $member_age;
                $member_info['create_time'] = time();
                $member_info['create_user'] = $member_number;
                $MemberModel->insert_member($member_info);
            }else{
                //用户标识id验证
                if (!isset($paramsAll['member_number'])||empty($paramsAll['member_number'])) {
                    throw new \OneException(7);
                }
                $member_number = $paramsAll['member_number'];
                $member_info = $MemberModel->select_member_info($member_number);
                if (empty($member_info)){
                    throw new \OneException(8);
                }
            }
            Session::put('member_number',$member_number);
            DB::commit();
            $response = array();
            $response['DATA'] = $member_info;
            return response()->json(self::ok($response));
        } catch (\OneException $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage() . chr(10) . $e->getTraceAsString());
        }
    }

    /**
     *  1-1-2
     * 修改用户信息
     */
    public function actionChangeMemberInfo()
    {
        try {
            $paramsAll = request()->all();

            //model的初始化处理
            $MemberModel = new MemberModel($this);

            //用户标识id验证
            if (!isset($paramsAll['member_number'])||empty($paramsAll['member_number'])) {
                throw new \OneException(7);
            }
            $member_number = $paramsAll['member_number'];

            //验证挡墙用户是否存在
            $member_info = $MemberModel->select_member_info($member_number);
            if (empty($member_info)){
                throw new \OneException(8);
            }

            //用户身高验证
            if (!isset($paramsAll['member_height'])||empty($paramsAll['member_height'])) {
                throw new \OneException(4);
            }
            $member_height = $paramsAll['member_height'];

            //用户体重验证
            if (!isset($paramsAll['member_weight'])||empty($paramsAll['member_weight'])) {
                throw new \OneException(5);
            }
            $member_weight = $paramsAll['member_weight'];

            //用户年龄验证
            if (!isset($paramsAll['member_age'])||empty($paramsAll['member_age'])) {
                throw new \OneException(6);
            }
            $member_age = $paramsAll['member_age'];
            $member_sex = empty($paramsAll['member_sex'])?0:$paramsAll['member_sex'];//0:男 1:女

            //数据库事务处理
            DB::beginTransaction();

            $member_info = array();
            $member_info['member_height'] = $member_height;
            $member_info['member_weight'] = $member_weight;
            $member_info['member_age'] = $member_age;
            $member_info['member_sex'] = $member_sex;
            $MemberModel->update_member_info($member_info,$member_number);

            DB::commit();
            $response = array();
            $response['DATA'] = $member_info;
            return response()->json(self::ok($response));
        } catch (\OneException $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage() . chr(10) . $e->getTraceAsString());
        }
    }
}
