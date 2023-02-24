<?php

function helper_test()
{
    echo("This is helper test!");
}

if (!function_exists('paramsCheck')) {
    /**
     * 必須チェック
     *
     * @params array $params パラメータ
     * @params array $requires 必須項目のキー
     * @throws OneException
     */
    function paramsCheck($params = array(), $requires = array())
    {

        foreach ($requires as $require) {
            if (!isset($params[$require]) || $params[$require] == '') {
                throw new \OneException(2, "Missing : {$require}");
            }
        }

        return true;
    }
}

if (!function_exists('hashPassword')) {
    /**
     * Default password hash method
     *
     * @param string
     * @return  string
     */
    function hashPassword($password)
    {
        return hash('sha256', config('auth.salt') . $password);
    }
}

if (!function_exists('getRandomNumber')) {
    /**
     * Default password hash method
     *
     * @param string
     * @return  string
     */
    function getRandomNumber($str_num)
    {
        $code = "";
        for ($i = 0; $i < $str_num; $i++) {
            if($i == 0){
                $code .= rand(1, 9);
            }
            else{
                $code .= rand(0, 9);
            }
        }
        return $code;
    }
}