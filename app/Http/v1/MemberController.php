<?php

namespace App\Http\v1;

use App\Http\Controllers\Controller;
use App\Http\Model\InvestBrandModel;
use Illuminate\Http\Request;
use App\Http\Model\Clients;
use App\Http\Model\SmsmailModel;
use App\Http\Model\ClientreqsModel;
use Illuminate\Support\Facades\Session;
use App\Http\Model\v1\clientreqs\AccountModel;
use function App\Http\Controllers\v2\Client\paramsCheck;

class MemberController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 1-2-88_パスワード変更完了
     *
     * ・移行元：controller/v1/client/me.php  post_password()
     */
    public function postPassword(Request $request)
    {
        try {
            $this->authCheck(true);
            $params = $request->all();
            $params['CLIENT_ID'] = $this->Client->CLIENT_ID;
            $params['CLIENT_SEQ_NO'] = $this->Client->CLIENT_SEQ_NO;
            $HASHED_PASSWORD = $this->Clients->passwordChange($params);
            return response()->json(self::ok(array('HASHED_PASSWORD' => $HASHED_PASSWORD)));
        } catch (\OneException $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",OneException =" . $e->getMessage());
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",Exception =" . $e->getMessage() . chr(10) . $e->getTraceAsString());
            exit();
        }
    }

    /**
     * 1-1-4 認証のみ
     * ・移行元：controller/v1/client/me.php  post_auth()
     */
    public function postAuth(Request $request)
    {
        try {
            $this->authCheck();
            $params = $request->all();
            $MEMBER_ID = $this->Client->CLIENT_ID;

            $ENCRYPTED_PASSWORD = $params['ENCRYPTED_PASSWORD'];
            $user = $this->encryptedValidateUser($MEMBER_ID, $ENCRYPTED_PASSWORD);

            if (!$user) {
                // 認証失敗
                throw new \OneException(93);
            } else if ($user == 'lock') {
                throw new \OneException(94);
            } else if ($user == 'expire') {
                throw new \OneException(120);
            }
            return response()->json(self::ok());
        } catch (\OneException $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",OneException =" . $e->getMessage());
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",Exception =" . $e->getMessage() . chr(10) . $e->getTraceAsString());
            exit();
        }
    }

    /**
     * 1-2-18 アプリのトップ画面に表示する、評価額合計や保有銘柄一覧などを返す。
     * ・移行元：controller/v1/client/me.php  action_get()
     */
    public function postGet(Request $request)
    {
        try {
            $this->authCheck();
            $params = $request->all();
            $param['CLIENT_SEQ_NO'] = $this->Client->CLIENT_SEQ_NO;
            $this->Request = $request;
            $STOCK_COUNTRY_FLG = empty($params['STOCK_COUNTRY_FLG']) ? 0 : $params['STOCK_COUNTRY_FLG'];
            $get_invest_flg = true;
            $get_stock_flg = true;
            $DATA_FLG = isset($params['DATA_FLG']) ? $params['DATA_FLG'] : 0;
            if ($DATA_FLG == 1) {
                $get_stock_flg = true;  // 株の情報を取得する
                $get_invest_flg = false;  // 投信の情報を取得しない
            }
            if ($DATA_FLG == 2) {
                $get_stock_flg = false;  // 株の情報を取得しない
                $get_invest_flg = true;  // 投信の情報を取得する
            }

            $clients = new Clients($this);
            if ($get_stock_flg == true){    // 株の情報を取得
                $clients->getClientAssets($STOCK_COUNTRY_FLG);
            }
            if ($get_invest_flg == true){   // 投信の情報を取得
                $INVEST_REFRESH_FLG = empty($params['INVEST_REFRESH_FLG']) ? 0 : $params['INVEST_REFRESH_FLG'];
                $clients->getClientAssetsInvest($INVEST_REFRESH_FLG, $this->OS);
            }

            // トップ画面に表示する、評価額合計や保有銘柄一覧などを取得
            $dataset = $clients->me();

            if ($get_invest_flg == true) {   // 投信の情報を取得
                //顧客保有する投資信託全銘柄配列積立状態を取得する
                $INVEST_BRAND_RESERVE_STATUS_ARRAY = InvestBrandModel::get_invest_brand_reserve_status_array($param);
                $dataset['INVEST_BRAND_RESERVE_STATUS_ARRAY'] = empty($INVEST_BRAND_RESERVE_STATUS_ARRAY) ? (object)array() : $INVEST_BRAND_RESERVE_STATUS_ARRAY;
            }

            if ($get_stock_flg == true) {    // 株の情報を取得
                $brand_config = array();        // P170404-2554 銘柄の状態 (初期化)
                // P170404-2554 閉場時の在庫チェック    (const.phpに定義)
                $OTB_STOCK_BALANCE_REAL_CHECK_CONFIG = config('const.OTB_STOCK_BALANCE_REAL_CHECK');
                if (!empty($OTB_STOCK_BALANCE_REAL_CHECK_CONFIG)) {

                    // 閉場時の在庫チェックする場合、自己の株式リアル残高を取得 (閉場時＆米株のみ)
                    $otb_stock_balances = $this->Brands->getOtbStockBalanceInfo();
                    if (!empty($otb_stock_balances)) {

                        // 銘柄単位でチェック
                        foreach ($otb_stock_balances as $otb_stock_balance) {
                            // 買止め(顧客の買制限) ・・・自己リアル残高 ＜ 注文可能下限 * X
                            if (isset($OTB_STOCK_BALANCE_REAL_CHECK_CONFIG['BUY']) && count($OTB_STOCK_BALANCE_REAL_CHECK_CONFIG['BUY']) && $otb_stock_balance['ORDER_LIMIT_LOWER_QTY'] != null && $otb_stock_balance['ORDER_LIMIT_LOWER_QTY'] >= 0) {
                                // 1つ目の設定
                                if ($otb_stock_balance['SELLABLE_QTY'] <= bcmul($otb_stock_balance['ORDER_LIMIT_LOWER_QTY'], $OTB_STOCK_BALANCE_REAL_CHECK_CONFIG['BUY'][1])) {
                                    $brand_config[$otb_stock_balance['BRAND_ID']]['BUY_ORDER_STATUS'] = 1;
                                } // 2つ目の設定(定義されている場合)
                                elseif (count($OTB_STOCK_BALANCE_REAL_CHECK_CONFIG['BUY']) == 2) {
                                    // 自己リアル残高 ＜ 注文可能下限 * X
                                    if ($otb_stock_balance['SELLABLE_QTY'] < bcmul($otb_stock_balance['ORDER_LIMIT_LOWER_QTY'], $OTB_STOCK_BALANCE_REAL_CHECK_CONFIG['BUY'][2])) {
                                        $brand_config[$otb_stock_balance['BRAND_ID']]['BUY_ORDER_STATUS'] = 2;
                                    }
                                }
                            }

                            // 売止め(顧客の売制限) ・・・自己リアル残高 > 注文可能上限 * X
                            if (isset($OTB_STOCK_BALANCE_REAL_CHECK_CONFIG['SELL']) && count($OTB_STOCK_BALANCE_REAL_CHECK_CONFIG['SELL']) && $otb_stock_balance['ORDER_LIMIT_UPPER_QTY'] != null && $otb_stock_balance['ORDER_LIMIT_UPPER_QTY'] > 0) {
                                // 1つ目の設定
                                if ($otb_stock_balance['SELLABLE_QTY'] > bcmul($otb_stock_balance['ORDER_LIMIT_UPPER_QTY'], $OTB_STOCK_BALANCE_REAL_CHECK_CONFIG['SELL'][1])) {
                                    $brand_config[$otb_stock_balance['BRAND_ID']]['SELL_ORDER_STATUS'] = 1;
                                }
                            }
                        }
                    }
                }
                if (!empty($brand_config)) {
                    $dataset['BRAND_CONFIG'] = $brand_config;
                }
                //つみたて配列を取得
                $dataset['STOCK_BRAND_RESERVE_STATUS_ARRAY'] = $clients->getBrandReserveStatus($this->Client->CLIENT_SEQ_NO);

            }
            return response()->json(self::ok($dataset));
        } catch (\OneException $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",OneException =" . $e->getMessage());
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",Exception =" . $e->getMessage() . chr(10) . $e->getTraceAsString());
            exit();
        }
    }

    /**
     *  1-1-5認証SMSの発送の請求
     *  移行元：controller/v1/client/me.php  post_request_sms()
     */
    public function postRequestSms(Request $request)
    {
        try {
            $MEMBER_ID = $request->session()->get('TMP_MEMBER_ID');
            $ENCRYPTED_PASSWORD = $request->session()->get('TMP_ENCRYPTED_PASSWORD');
            if (empty($MEMBER_ID) || empty($ENCRYPTED_PASSWORD)) {
                if ($this->OS == 'pc') {
                    // PCWebの場合、trade画面へリダイレクト
                    return redirect('/trade');
                }
                return $this->error('タイムアウトしました。', false, 1, false, array('FORCE_LOGOUT' => 1));

            }
            $paramsAll = $request->all();
            $params = array();
            $params['REQUEST_CHANNEL'] = isset($paramsAll['APP_ID']) ? $paramsAll['APP_ID'] : config('const.APP_ID_ONETAPBUY_JPN');
            $params['REQUEST_METHOD'] = isset($paramsAll['SMS_AUTH_METHOD']) ? $paramsAll['SMS_AUTH_METHOD'] : '';
            $params['SEND_AGAIN_FLG'] = isset($paramsAll['SEND_AGAIN_FLG']) ? $paramsAll['SEND_AGAIN_FLG'] : 0;
            if($this->OS=='pc'){
                $params['REQUEST_CHANNEL'] = 101;    //PC端SMS送信のチャネル
                $params['REQUEST_METHOD'] = "login"; //PC端SMS送信のメソッド
            }
            //CLIENT_SEQ_NO取得
            $clients = new Clients($this);
            $result = $clients->selectClientSeqNo($MEMBER_ID);
            if (empty($result)) {
                throw new \OneException(4);
            } else {
                $params['CLIENT_SEQ_NO'] = $result['CLIENT_SEQ_NO'];
            }
            $params['SMSAUTH_NOUSE_FLAG'] = 0;
            // 30秒で一回しか請求できない
            //前回請求情報取得
            $params['SMSAUTH_RESULT'] = config(app()->environment() . '/smsmail.SMSAUTH_RESULT_LIST');
            if(!isset($paramsAll['REFRESH_FLG']) || empty($paramsAll['REFRESH_FLG'])){
                $smsmail = new SmsmailModel($this);
                //認証SMS発信請求
                $response = $smsmail->requestSMSMail($params);
                //認証SMSが発信成功の場合、レスポンス値を整形する
                // レスポンスコードは200ではないとき、エラーメッセージを出す
                switch ($response['RESPONSECD']) {
                    case 200:
                        if ($params['SEND_AGAIN_FLG'] === "1") {
                            $response['SMS_TIPS'] = "再送信が完了しました。";
                        }
                        $request->session()->put('now_time', time());
                        break;
                    case 1101:
                        //有効認証コードがない場合、メッセージ出す
                        $result = $clients->checkSmsauthCode($params);
                        //送信10回以上、再発行を押した時、メッセージ出す
                        if ($params['SEND_AGAIN_FLG'] === "1" || empty($result)) {
                            throw new \OneException(345);
                        }
                        break;
                    case 503:
                        throw new \OneException(348);
                        break;
                    case 550:
                        throw new \OneException(349);
                        break;
                    case 565:
                        throw new \OneException(349);
                        break;
                    case 1103:
                        throw new \OneException(349);
                        break;
                    default :
                        throw new \OneException(349);
                        break;
                }

            }else{
                $response['RESPONSECD'] = 200;
                $response['MOBILENUMBER'] = empty($request->session()->get('SEND_SMS_TEL'))?'':$request->session()->get('SEND_SMS_TEL');

            }
            // 再送可能時間(送信)
            if ($this->OS == 'pc'){
                $request->session()->put('SEND_SMS_TEL', $response['MOBILENUMBER']);
                if( !empty($request->session()->get('now_time'))){
                    $SECONDS = floatval(time()) - floatval($request->session()->get('now_time'));
                    if ($SECONDS >= 60){
                        $response['SMS_RETRY_COUNTDOWN'] = 0;
                    }else{
                        $response['SMS_RETRY_COUNTDOWN'] = 60 - floatval($SECONDS);
                    }
                }
            }
            $response['REFERRER'] = empty($request->session()->get('login_referrer'))?'':$request->session()->get('login_referrer');
            return response()->json(self::ok($response));
        } catch (\OneException $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",OneException =" . $e->getMessage());
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",Exception =" . $e->getMessage() . chr(10) . $e->getTraceAsString());
            exit();
        }

    }

    /**
     *  1-1-6認証コードのチェック
     *  移行元：controller/v1/client/me.php  post_check_sms_auth()
     */
    public function postCheckSmsAuth(Request $request)
    {
        try {
            $MEMBER_ID = $request->session()->get('TMP_MEMBER_ID');
            $ENCRYPTED_PASSWORD = $request->session()->get('TMP_ENCRYPTED_PASSWORD');
            if (empty($MEMBER_ID) || empty($ENCRYPTED_PASSWORD)) {
                if ($this->OS == 'pc') {
                    // PCWebの場合、trade画面へリダイレクト
                    return redirect('/trade');
                }
                return $this->error('タイムアウトしました。', false, 1, false, array('FORCE_LOGOUT' => 1));
            }
            $paramsAll = $request->all();
            $params = array();

            $params['SMSAUTH_CD'] = isset($paramsAll['SMS_AUTH_CD']) ? $paramsAll['SMS_AUTH_CD'] : '';
            if($this->OS=='pc') {
                $params['SMSAUTH_CHANNEL'] = 101; //SMS認証チャネル
                $params['SMSAUTH_METHOD'] = "login";
                //设置sms认证通过cookie的认证串
                // SMS认证串を暗号化
                $random_str= AccountModel::random();
                $SMS_AUTH_STRING = strval(AccountModel::encrypt_value(trim($MEMBER_ID).time().$random_str));
                $params['SMS_AUTH_STRING']= substr($SMS_AUTH_STRING , 0 , 40);
            }else{
                $params['SMSAUTH_CHANNEL'] = isset($paramsAll['APP_ID']) ? $paramsAll['APP_ID'] : config('const.APP_ID_ONETAPBUY_JPN');
                $params['SMSAUTH_METHOD'] = isset($paramsAll['SMS_AUTH_METHOD']) ? $paramsAll['SMS_AUTH_METHOD'] : '';
                $params['SMS_AUTH_STRING'] = isset($paramsAll['SMS_AUTH_STRING']) ? $paramsAll['SMS_AUTH_STRING'] : '';
                $params['SMS_AUTH_STRING'] = substr($params['SMS_AUTH_STRING'], 0, 40);
                // 必須項目のチェック
                if (!isset($params['SMS_AUTH_STRING']) || $params['SMS_AUTH_STRING'] == "") {
                    throw new \OneException(4);
                }
            }

            //CLIENT_SEQ_NO取得
            $clients = new Clients($this);
            $CLIENT_SEQ_NO = $clients->selectClientSeqNo($MEMBER_ID);
            $params['CLIENT_SEQ_NO'] = $CLIENT_SEQ_NO['CLIENT_SEQ_NO'];
            //認証SMS発信請求
            $smsmail = new SmsmailModel($this);
            $CHECK_SMSMAIL = $smsmail->checkSMSAuthenticate($params);
            //認証SMSが発信成功の場合、レスポンス値を整形する
            if ($CHECK_SMSMAIL === 1) {
                //SMS認証情報挿入
                $clients->insertSmsAuthInfo($params);
            } elseif ($CHECK_SMSMAIL === 0) {
                // 認証コードの有効期限切れ
                throw new \OneException(347);
            } elseif ($CHECK_SMSMAIL === 2) {
                // 一定回数失敗したら認証コード無効になる
                throw new \OneException(346);
            } elseif ($CHECK_SMSMAIL === 3) {
                // 認証コードが正しくない
                throw new \OneException(344);
            }
            // 認証成功したら、ホワイトリストの情報を更新
            $clients->updateWhiteListInfo($params['CLIENT_SEQ_NO']);

            if($this->OS=='pc'){
                //有効期限５年を設定する
                \Cookie::queue($CLIENT_SEQ_NO['CLIENT_SEQ_NO'].'_SMS_AUTH_STRING',$params['SMS_AUTH_STRING'], 2628000);
                $request->session()->remove('login_referrer');
            }
            $request->session()->remove('TMP_MEMBER_ID');
            $request->session()->remove('TMP_ENCRYPTED_PASSWORD');

            $this->checkIp($MEMBER_ID, $params['SMSAUTH_CHANNEL']);
            // サンプルでログインしている時のゴミが残らないように一旦削除
            $this->clearAuth();
            $TOKEN = $this->encrypted_login($MEMBER_ID, $ENCRYPTED_PASSWORD);
            $this->Client->CLIENT_SEQ_NO = Session::get('CLIENT_SEQ_NO');
            // ログイン可能かチェック
            $this->Clients->loginCheck(array('CLIENT_SEQ_NO' => $this->Client->CLIENT_SEQ_NO));
            $this->Client->MEMBER_ID = $MEMBER_ID;
            // セッションにトークンを保存 トークンがセッションにセットされている場合は、base コントローラーで有効化をチェックする
            // 無効になっていれば、ログアウト処理を実行する
            $clientreqs = new ClientreqsModel($this);
            $seq_no = $clientreqs->getReqNo($this->Client->CLIENT_SEQ_NO);
            $reception_no = empty($seq_no) ? '' : hash('sha256', $seq_no . config('const.FA_CLIENT_IDENTITY_SALT'));

            //パスワードが修正されたか検証する
            $ekyc_release_time = config(app()->environment() . '/config.EKYC_RELEASE_TIME');
            $release_time = strtotime($ekyc_release_time);
            $result_client_one = $this->Clients->getClientOne($MEMBER_ID);
            //口座开设承认时间
            $release_account_time = strtotime($result_client_one['ACCOUNT_OPEN_D']);
            if ($release_account_time >= $release_time) {
                $result_verify = $this->Clients->verifyPasswordChanged($result_client_one['CLIENT_SEQ_NO']);
                if (empty($result_verify['PASS_CHG_FLG'])) {
                    //必要
                    $IF_MOD_PASS_FLG = 1;
                } else {
                    //NO必要
                    $IF_MOD_PASS_FLG = 0;
                }
            } else {
                //NO必要
                $IF_MOD_PASS_FLG = 0;
            }
            $response['TOKEN'] = $TOKEN;
            $response['SEQ_NO'] = strval($this->Client->CLIENT_SEQ_NO);
            $response['RECEPTION_NO'] = $reception_no;
            $response['IF_MOD_PASS_FLG'] = $IF_MOD_PASS_FLG;
            return response()->json(self::ok($response));
        } catch (\OneException $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",OneException =" . $e->getMessage());
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",Exception =" . $e->getMessage() . chr(10) . $e->getTraceAsString());
            exit();
        }

    }

    /**
     * 1-1-2 ログインAPI
     * ・移行元：controller/v1/client/me.php  post_login()
     */
    public function postLogin(Request $request)
    {
        try {
            $params = $request->all();
            $MEMBER_ID = $params['MEMBER_ID'];
            $ENCRYPTED_PASSWORD = $params['ENCRYPTED_PASSWORD'];
            $referrer  = isset($params['REFERRER'])?$params['REFERRER']:'';
            $UUID  = isset($params['UUID']) ? $params['UUID'] : '';
            $APP_ID = $this->APP_ID;
            $this->checkIp($MEMBER_ID, $APP_ID);
            // サンプルでログインしている時のゴミが残らないように一旦削除
            $this->clearAuth();
            $TOKEN = $this->encrypted_login($MEMBER_ID, $ENCRYPTED_PASSWORD);
            $this->Client->CLIENT_SEQ_NO = Session::get('CLIENT_SEQ_NO');
            // ログイン可能かチェック
            $clients = new Clients($this);
            $clients->loginCheck(array('CLIENT_SEQ_NO' => $this->Client->CLIENT_SEQ_NO));

            //SMS認証OK取得
            $result = $clients->getSysConfig('smsmail.service.config');
            $SMS_NEED_AUTH_FLG = (int)$result['CONFIG_VALUE'];
            if ($SMS_NEED_AUTH_FLG === 0) {
                // 全員sms認証を行わない
                $OPEN_SMSPAGE_FLG = 0;
            } else {
                // ホワイトリストのチェック
                $date = $clients->getSMSWhiteListInfo($this->Client->CLIENT_SEQ_NO);
                if (!empty($date)) {
                    if (strtotime(getNowJst()) <= strtotime($date['SMSAUTH_IGNORE_DT'])) {
                        // ホワイトリスト期限内SMS認証チェックなし
                        $OPEN_SMSPAGE_FLG = 0;
                    } else {
                        // ホワイトリスト期限外SMS認証チェック強制的行う
                        $OPEN_SMSPAGE_FLG = 1;
                    }
                } else {
                    // ホワイトリストにいないメンバーは下記チェックを行う
                    if ($this->OS == 'pc') {
                        //ユーザがメッセージ認証操作を行ったか検証する
                        $APP_ID = 101;
                        $SMS_AUTH_STRING = empty(\Cookie::get($this->Client->CLIENT_SEQ_NO . '_SMS_AUTH_STRING')) ? "" : \Cookie::get($this->Client->CLIENT_SEQ_NO . '_SMS_AUTH_STRING');
                    } else {
                        //必須項目のチェック
                        paramsCheck($params, ['SMS_AUTH_STRING']);
                        $SMS_AUTH_STRING = substr($params['SMS_AUTH_STRING'], 0, 40);
                    }
                    $clients = new Clients($this);
                    $terminal = $clients->checkSmsAuthInfo($SMS_AUTH_STRING, $APP_ID);
                    if (!empty($SMS_AUTH_STRING) && !empty($terminal) && ((config(app()->environment() . '/smsmail.SMSAUTH_LOGIN_INTERVAL') === -1) || (((strtotime(getNowJst()) - strtotime($terminal['LAST_MOD_DT'])) / 86400) < config(app()->environment() . '/smsmail.SMSAUTH_LOGIN_INTERVAL')))) {
                        $OPEN_SMSPAGE_FLG = 0;
                    } else {
                        $OPEN_SMSPAGE_FLG = 1;
                        if ($SMS_NEED_AUTH_FLG === 2) {
                            // sms認証を行うかどうかは一定のルールで判断する
                            $rule = $clients->getSysConfig('smsmail.loadbalance.target');
                            if (!empty($rule) && !empty($rule['CONFIG_VALUE'])) {
                                $regular_expression = $rule['CONFIG_VALUE'];
                                $result = $clients->selectClientSeqNo($MEMBER_ID);
                                try {
                                    // ルールに合わせないメンバーはSMS認証チェックなし
                                    if (!(preg_match("/" . $regular_expression . "/", $result['TEL_NO_02']))) {
                                        $OPEN_SMSPAGE_FLG = 0;
                                    }
                                } catch (\Exception $e) {
                                    // エラーになる場合、管理者にメールを送信する
                                    $detail = "＜エラー詳細＞正規表現は正しくありません。「" . $regular_expression . "」";
                                    $content = config(app()->environment() . '/smsmail.regular_expression_content');
                                    $title = config(app()->environment() . '/smsmail.regular_expression_title');
                                    $clients->common_admin_mail("smsmail", $detail . $content, $title);
                                    // 正規表現が間違うとき、SMS認証チェックなし
                                    $OPEN_SMSPAGE_FLG = 0;
                                }
                            } else {
                                // 正規表現が定義されていないとき、SMS認証チェックなし、管理者にメールを送信する
                                $detail = "＜エラー詳細＞正規表現が定義されいません。";
                                $content = config(app()->environment() . '/smsmail.regular_expression_content');
                                $title = config(app()->environment() . '/smsmail.regular_expression_title');
                                $clients->common_admin_mail("smsmail", $detail . $content, $title);
                                $OPEN_SMSPAGE_FLG = 0;
                            }
                        }
                    }
                }
            }

            if ($OPEN_SMSPAGE_FLG === 1) {
                // サンプルでログインしている時のゴミが残らないように一旦削除
                $this->clearAuth();
                $request->session()->flush();
                $request->session()->put('login_referrer', $referrer);
                $request->session()->put('UUID', $UUID);
                $request->session()->put('TMP_MEMBER_ID', $MEMBER_ID);
                $request->session()->put('TMP_ENCRYPTED_PASSWORD', $ENCRYPTED_PASSWORD);
                return response()->json(self::ok(array('OPEN_SMSPAGE_FLG' => $OPEN_SMSPAGE_FLG)));
            } else {
                $this->Client->MEMBER_ID = $MEMBER_ID;
                // セッションにトークンを保存 トークンがセッションにセットされている場合は、base コントローラーで有効化をチェックする
                // 無効になっていれば、ログアウト処理を実行する
                $clientreqs = new ClientreqsModel($this);
                $seq_no = $clientreqs->getReqNo($this->Client->CLIENT_SEQ_NO);
                $reception_no = empty($seq_no) ? '' : hash('sha256', $seq_no . config('const.FA_CLIENT_IDENTITY_SALT'));
                //パスワードが修正されたか検証する
                $ekyc_release_time = config(app()->environment() . '/config.EKYC_RELEASE_TIME');
                $release_time = strtotime($ekyc_release_time);
                $clients = new Clients($this);
                $result_client_one = $clients->getClientOne($MEMBER_ID);
                //口座开设承认时间
                $release_account_time = strtotime($result_client_one['ACCOUNT_OPEN_D']);
                //是否需要强制修改密码flg  1：是  0：不是
                $IF_MOD_PASS_FLG = 0;
                if ($release_account_time >= $release_time) {
                    $result_verify = $clients->verifyPasswordChanged($result_client_one['CLIENT_SEQ_NO']);
                    if (empty($result_verify['PASS_CHG_FLG'])) {
                        //必要
                        $IF_MOD_PASS_FLG = 1;
                    }
                }
                $request->session()->remove('login_referrer');
                return response()->json(self::ok(array(
                    'TOKEN' => $TOKEN,
                    'SEQ_NO' => strval($this->Client->CLIENT_SEQ_NO),
                    'RECEPTION_NO' => $reception_no,
                    'IF_MOD_PASS_FLG' => $IF_MOD_PASS_FLG,
                    'OPEN_SMSPAGE_FLG' => $OPEN_SMSPAGE_FLG
                )));
            }
        } catch (\OneException $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",OneException =" . $e->getMessage());
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",Exception =" . $e->getMessage() . chr(10) . $e->getTraceAsString());
            exit();
        }
    }

    /**
     * 1-2-24
     * ログアウト処理の結果を返す。
     * ログアウトに成功した場合、共通レスポンスで「ステータス：正常」を返し、受け取った端末の情報をDBから削除する。
     * ログアウト成功の場合は、「STATUS=0」を返す。ログアウト失敗の場合は「STATUS=1」を返す。
     * ・移行元：controller/v1/client/me.php  post_logout()
     */
    public function postLogout(Request $request)
    {

        try {
            $this->clearAuth();
            $clients = new Clients($this);
            $clients->logout();
            \Cookie::queue(\Cookie::forget('CLIENT_SEQ_NO'));
            \Cookie::queue(\Cookie::forget('laravel_session'));
            return response()->json(self::ok());
        } catch (\OneException $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",OneException =" . $e->getMessage());
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",Exception =" . $e->getMessage() . chr(10) . $e->getTraceAsString());
            exit();
        }
    }

    /**
     * 1-2-20_メニューAPI
     * ・移行元：controller/v1/client/me.php  post_menu()
     */
    public function postMenu() {
        try {
            $this->authCheck();
            $result = array(
                'COMPANY_URL' => config('const.COMPANY_URL'),
                'PRIVACY_POLICY_URL' => config('const.PRIVACY_POLICY_URL'),
                'TERMS_OF_SERVICE_URL' => config('const.TERMS_OF_SERVICE_URL'),
                'PAYPAYSEC_FAQ_URL' => config(app()->environment() .'/config.PAYPAYSEC_FAQ_URL'),
                'TSUMITATE_FAQ_URL' => config(app()->environment() .'/config.TSUMITATE_FAQ_URL'),
                'INVESTMENT_KNOWHOW_URL' => config('const.INVESTMENT_KNOWHOW_URL'),
            );
            return response()->json(self::ok($result));
        } catch (\OneException $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",OneException =" . $e->getMessage());
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            \Log::debug(__FUNCTION__ . ",L=" . __LINE__ . ",Exception =" . $e->getMessage() . chr(10) . $e->getTraceAsString());
            exit();
        }
    }
}
