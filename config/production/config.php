<?php

return array(
    /**
     * base_url - The base URL of the application.
     * MUST contain a trailing slash (/)
     *
     * You can set this to a full or relative URL:
     *
     *     'base_url' => '/foo/',
     *     'base_url' => 'http://foo.com/'
     *
     * Set this to null to have it automatically detected.
     */
    'base_url' => isset($_SERVER['BASE_URL']) ? $_SERVER['BASE_URL'] : '',

    // アプリのバージョンチェック 以下の数値より小さい場合は動かない
    'APP_VERSION' => array(
        'MIN_APP_VERSION_IOS' => '2.2.1',
        'MIN_APP_VERSION_ANDROID' => '2.2.6',
        // 強制バージョンアップ時ストアのURL
        'GOOGLEPLAY_URL' => 'https://play.google.com/store/apps/details?id=jp.co.onetapbuy.jpstock', // AndroidのAPPStore的なものへのURLスキーム
        'APPSTORE_URL' => 'https://itunes.apple.com/jp/app/id1254383356'  // APPStoreへのURLスキーム
    ),
    'APP_STORE_NAME' => 'App Store',
    'GOOGLE_PLAY_NAME' => 'Google Play',
);
