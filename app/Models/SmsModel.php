<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class SmsModel extends Model
{
    private $Lara;
    public function __construct(&$Lara)
    {
        $this->Lara = $Lara;
    }

    public function select_mobile_code($mobile)
    {
        try {
            $code_info = DB::table('t_verification_code')
                ->select(DB::raw('count(*) as count'))
                ->where('vctel','=',$mobile)
                ->where('expired_time','>',time())
                ->value('count');
            return $code_info;
        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function insert_mobile_code($insert_sms_arr)
    {
        DB::table('t_verification_code')->insert($insert_sms_arr);
    }
}
