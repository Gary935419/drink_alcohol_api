<?php

function helper_test()
{
    echo("This is helper test!");
}

if (!function_exists('selectArray')) {
    /**
     * Convert array to string
     * @params array $columns
     * @return string
     */
    function selectArray($columns = ['*'])
    {
        if (is_array($columns)) {
            $columns = implode(',', $columns);
        }
        return $columns;
    }
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

        foreach ($requires as $key => $require) {
            if (!isset($params[$require]) || $params[$require] == '') {
                throw new \OneException(4, array('param_name' => $require));
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


if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

if (!function_exists('convertStrType')) {
    /**
     * 字符串半角和全角间相互转换
     * @param string $str 待转换的字符串
     * @param int  $type TODBC:转换为半角；TOSBC，转换为全角
     * @return string 返回转换后的字符串
     */
    function convertStrType($str,$type) {
        //全角
        $dbc = array(
            '０' , '１' , '２' , '３' , '４' ,
            '５' , '６' , '７' , '８' , '９' ,
            'Ａ' , 'Ｂ' , 'Ｃ' , 'Ｄ' , 'Ｅ' ,
            'Ｆ' , 'Ｇ' , 'Ｈ' , 'Ｉ' , 'Ｊ' ,
            'Ｋ' , 'Ｌ' , 'Ｍ' , 'Ｎ' , 'Ｏ' ,
            'Ｐ' , 'Ｑ' , 'Ｒ' , 'Ｓ' , 'Ｔ' ,
            'Ｕ' , 'Ｖ' , 'Ｗ' , 'Ｘ' , 'Ｙ' ,
            'Ｚ' , 'ａ' , 'ｂ' , 'ｃ' , 'ｄ' ,
            'ｅ' , 'ｆ' , 'ｇ' , 'ｈ' , 'ｉ' ,
            'ｊ' , 'ｋ' , 'ｌ' , 'ｍ' , 'ｎ' ,
            'ｏ' , 'ｐ' , 'ｑ' , 'ｒ' , 'ｓ' ,
            'ｔ' , 'ｕ' , 'ｖ' , 'ｗ' , 'ｘ' ,
            'ｙ' , 'ｚ' , '－' , '　' , '：' ,
            '．' , '，' , '／' , '％' , '＃' ,
            '！' , '＠' , '＆' , '（' , '）' ,
            '＜' , '＞' , '＂' , '＇' , '？' ,
            '［' , '］' , '｛' , '｝' , '＼' ,
            '｜' , '＋' , '＝' , '＿' , '＾' ,
            '￥' , '￣' , '｀'
        );
        //半角
        $sbc = array(
            '0', '1', '2', '3', '4',
            '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E',
            'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y',
            'Z', 'a', 'b', 'c', 'd',
            'e', 'f', 'g', 'h', 'i',
            'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x',
            'y', 'z', '-', ' ', ':',
            '.', ',', '/', '%', ' #',
            '!', '@', '&', '(', ')',
            '<', '>', '"', '\'','?',
            '[', ']', '{', '}', '\\',
            '|', '+', '=', '_', '^',
            '￥','~', '`'
        );
        if($type == 'TODBC'){
            //半角到全角
            return str_replace( $sbc, $dbc, $str );
        }elseif($type == 'TOSBC'){
            //全角到半角
            return str_replace( $dbc, $sbc, $str );
        }else{
            return $str;
        }
    }
}
