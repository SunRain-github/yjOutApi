<?php
// +----------------------------------------------------------------------
// | Author: SunRain
// +----------------------------------------------------------------------

require '../vendor/autoload.php';
use EasyWeChat\Factory;
use YjOutApi\Out\Outapi;

class App
{
    // ceshi
    private $configData = null;
    public function __construct()
    {
        $this->configData = [
            'app_id' => '',
            'secret' => ''
        ];
    }
    public function appObject()
    {
        return $app = Factory::miniProgram($this->configData);
    }

}
// 12
function getRequestData(){
    if(strtolower($_SERVER['REQUEST_METHOD']) == 'get')
        return $_GET;
    else if(strtolower($_SERVER['REQUEST_METHOD']) == 'post'){
        if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json')
        {
            return json_decode(file_get_contents('php://input'),true);
        }
        return $_POST;
    }
    return [];
}
function resultData($msg='成功',$data=[],$code=200)
{
    $rst = [
        'status' => $code,
        'msg' => (string)$msg,
        'data' => $data
    ];
    if(strtolower(trim(php_sapi_name())) == 'cli'){
        var_export($rst);die;
    }
    return json_encode($rst);
}
if(strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    $_POST = getRequestData();
    $code = $_POST['code'] ?? '';
    $encryptedData = $_POST['encryptedData'] ?? '';
    $iv = $_POST['iv'] ?? '';
    $rawData = $_POST['rawData'] ?? '';
    $signature = $_POST['signature'] ?? '';
    if (!$code)
    {
        echo resultData('授权失败,参数有误',[],0);die;
    }
    $session_key = '';
    try {
        $AppClass = new App();
        $app = $AppClass->appObject();
        $userInfoCong = $app->auth->session($code);
        $session_key = $userInfoCong['session_key']??'';
    } catch (\Exception $e) {
        echo resultData('获取session_key失败，请检查您的配置！:' . $e->getMessage() . 'line' . $e->getLine(),[],0);die;
    }
    if(!$session_key){
        echo resultData('获取session_key失败，请检查您的配置！',[],0);die;
    }
    if (!isset($userInfoCong['unionid']) || !isset($userInfoCong['openid'])) {
        echo resultData('unionid或openid获取失败',[],0);die;
    }
    try {
        //解密获取用户信息
        $userInfo = $app->encryptor->decryptData($session_key, $iv, $encryptedData);
    } catch (\Exception $e) {
        if ($e->getCode() == '-41003') {
            echo resultData('获取会话密匙失败',[],0);die;
        }
        echo resultData($e->getMessage(),[],0);die;
    }
    $userInfo['unionid'] = $userInfoCong['unionid'] ?? '';
    $userInfo['openid'] = $userInfoCong['openid'];
    $userInfo['session_key'] = $session_key;
    $userInfo['login_type'] = 'routine';
    $OutApiObj = new OutApi('APPID_demo','APPSECRET_demo');
    // 推送用户信息
    $data['YJ_uid'] = $OutApiObj->getYjUserUid($data);
    echo resultData('ok',$data);die;
}
$jsonData = '{
    "nickName": "微信用户",
    "gender": 0,
    "language": "",
    "city": "",
    "province": "",
    "country": "",
    "avatarUrl": "https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132",
    "watermark": {
        "timestamp": 1672976305,
        "appid": "wxcbe7d9c3bf31bdc4"
    },
    "is_demote": true,
    "session_key": "Gs9r6GK/98Nz6Hx96QaEaw==",
    "openid": "ofz_K4lbnbma5PQ_gJXb5IITZYtA",
    "login_type": "routine",
    "unionid": "ofB9I5r5j8lZzLS5v0zB0zy1sNPg"
}';
    // "login_type": "routine",
    // "unionid": "ofB9I5r5j8lZzLS5v0zB0zy1sNPg",
    // "openid": "o_nta5RXHmpH1o8VTOdjRz2GD7DI",
    // "YJ_uid": 13205146
$data = json_decode($jsonData ,true);
try {
    $OutApiObj = new OutApi('APPID_demo','APPSECRET_demo');
    var_export($OutApiObj->getToken());exit;
    $OutApiObj = new OutApi('APPID_demo','APPSECRET_demo');
    $YJ_uid = $OutApiObj->getYjUserUid($data);
    echo '$YJ_uid = '.$YJ_uid.PHP_EOL.PHP_EOL;
    $publicWhere = [
        'user_info_type'=>$data['login_type'],
        'uid'=>$YJ_uid,
        'page' => 1,
        'limit' => 1
    ];
    // 推送用户信息
    $userInfo = $OutApiObj->getUserInfo($publicWhere);
    echo '$userInfo = '. var_export($userInfo,true).PHP_EOL.PHP_EOL;
    // 获取用户信息
    $publicWhere['query_type'] = 'integral';
    $UserSubsidy_integral = $OutApiObj->getUserSubsidy($publicWhere);
    echo '$UserSubsidy_integral = '. var_export($UserSubsidy_integral,true).PHP_EOL.PHP_EOL;
    // 获取用户积分明细
    $publicWhere['query_type'] = 'Withdrawal';
    $UserSubsidy_Withdrawal = $OutApiObj->getUserSubsidy($publicWhere);
    echo '$UserSubsidy_Withdrawal = '. var_export($UserSubsidy_Withdrawal,true).PHP_EOL.PHP_EOL;
    // 小程序支付
    $pay_type = 'routine';
    
    $pay_params = [
        "app_id" => "wx99bac8742748a075",
        "merchant_id" => "1636400641",
        "yj_uid" => $userInfo["uid"],
        "body" => "测试",
        "pay_amount" => "1",
        "out_order_no" => time(),
        "openid" => $data["openid"],
        "real_name" => "",
        "phone" => "",
        "address" => "",
        "channel" => $pay_type
    ];
    // var_dump($pay_params);die;
    $pay_jsconfig = $OutApiObj->wxpay($pay_params);
    echo '$pay_jsconfig = '. var_export($pay_jsconfig,true).PHP_EOL.PHP_EOL;

} catch (\Exception $e) {
    echo resultData($e->getMessage(),[],0);die;
}
