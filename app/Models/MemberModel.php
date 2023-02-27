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

    public function insert_member($insert_member_arr)
    {
        DB::table('members')->insert($insert_member_arr);
    }

    public function select_member_info($member_number)
    {
        $member_info = DB::table('members')
            ->select('member_number','member_height','member_weight',
                'member_sex','member_age','create_time','create_user')
            ->where('member_number','=',$member_number)
            ->first();
        return $member_info;
    }

    public function update_member_info($modify_member_info,$member_number)
    {
        DB::table('members')
            ->where('member_number','=',$member_number)
            ->update($modify_member_info);
    }

}
