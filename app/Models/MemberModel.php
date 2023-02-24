<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class MemberModel extends Model
{
    private $Lara;
    public function __construct(&$Lara)
    {
        $this->Lara = $Lara;
    }

    public function select_member_invite_code($invite_code)
    {
        try {
            $member_invite_code = DB::table('m_members')
                ->select(DB::raw('count(*) as count'))
                ->where('invite_code','=',$invite_code)
                ->value('count');
            return $member_invite_code;
        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function select_member_mobile_info($mobile)
    {
        try {
            $member_mobile_info = DB::table('m_members')
                ->select(DB::raw('count(*) as count'))
                ->where('member_tel','=',$mobile)
                ->value('count');
            return $member_mobile_info;
        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function select_member_level_initialization()
    {
        try {
            $member_level_initialization = DB::table('m_member_level')
                ->select('*')
                ->where('level_experience','=',0)
                ->where('level_discount_ratio','=',0)
                ->first();
            return $member_level_initialization;
        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function insert_member($insert_member_arr)
    {
        return DB::table('m_members')->insertGetId($insert_member_arr);
    }

    public function insert_member_invite_record($insert_member_invite_record_arr)
    {
        DB::table('t_invite_record')->insert($insert_member_invite_record_arr);
    }

    public function update_member_passwd($modify_member_arr,$mobile)
    {
        DB::table('m_members')
            ->where('member_tel', '=', $mobile)
            ->update($modify_member_arr);
    }

    public function select_member_login_info($mobile,$passwd)
    {
        try {
            $member_login_info = DB::table('m_members')
                ->select('*')
                ->where('member_tel','=',$mobile)
                ->where('member_password','=',$passwd)
                ->first();
            return $member_login_info;
        } catch(\Exception $e) {
            throw $e;
        }
    }
}
