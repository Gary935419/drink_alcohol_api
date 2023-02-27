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

    public function select_member_by_token_info($token)
    {
        try {
            $member_info_token = DB::table('members')
                ->where('token','=',$token)
                ->first();
            return $member_info_token;
        } catch(\Exception $e) {
            throw $e;
        }
    }

}
