<?php

function helper_test()
{
    echo("This is helper test!");
}
if (!function_exists('getRequestUserAgentInfo')) {
    /**
     * 在UA里获取APP_VERSION和OS信息
     *
     * @return array
     */
    function getRequestUserAgentInfo($userAgent) {

        // 初期化
        $OS = 'mini';
        $APP_VERSION = '';

        // OS情報取得
        if (stripos($userAgent, 'android') !== false) {
            $OS = 'android';
        } elseif (stripos($userAgent, 'ios') !== false || stripos($userAgent, 'iphone') !== false) {
            $OS = 'ios';
        }

        // APP_VERSION情報取得
        $userAgentParts = preg_split("/\s|　/", $userAgent);
        if ($OS == 'ios') {
            if (isset($userAgentParts[1]) && preg_match("/^[0-9\.]+$/i", $userAgentParts[1])) {
                $APP_VERSION = $userAgentParts[1];
            } elseif (count($userAgentParts) >= 2 && preg_match("/^[0-9\.]+$/i", $userAgentParts[count($userAgentParts) - 2])) {
                $APP_VERSION = $userAgentParts[count($userAgentParts) - 2];
            }
        } elseif ($OS == 'android') {
            if (preg_match("/^[0-9\.]+$/i", $userAgentParts[count($userAgentParts) - 1])) {
                $APP_VERSION = $userAgentParts[count($userAgentParts) - 1];
            }
        }

        $result = array(
            'OS' => $OS,
            'APP_VERSION' => $APP_VERSION
        );
        return $result;
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