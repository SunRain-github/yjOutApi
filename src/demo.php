<?php
// +----------------------------------------------------------------------
// | Author: SunRain
// +----------------------------------------------------------------------

require '../vendor/autoload.php';
use EasyWeChat\Factory;
use YjOutApi\Out\Outapi;

class App
{
    private $configData = null;
    public function __construct()
    {
        $this->configData = [
            'app_id' => 'wxcbe7d9c3bf31bdc4',
            'secret' => 'b9079512efb07d077464dec369f7231e'
        ];
    }
    public function appObject()
    {
        return $app = Factory::miniProgram($this->configData);
    }

}
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
    $data = getYjUserUid($userInfo);
    echo resultData('ok',$data);die;
}
// 推送用户信息
function getYjUserUid($data)
{
    $params = [
        'user_type' => $data['login_type'] ?? '',
        'unionid' => $data['unionid'] ?? '',
        'openid' => $data['openid'] ?? '',
        'ali_user_id' => $data['ali_user_id'] ?? '',
        'phone' => $data['phone'] ?? '',
    ];
    try {
        $OutApiObj = new OutApi('APPID_demo','APPSECRET_demo');
        $response = $OutApiObj->httpRequest('user',$params);
        $data['YJ_uid'] = $response['uid'] ?? 0;
        if(!$data['YJ_uid']){
            echo resultData('获取用户UID失败！',[],0);die;
        }
        return $data;
    } catch (\Exception $e) {
        echo resultData($e->getMessage(),[],0);die;
    }
}
// 用户信息
function getUserInfo($data)
{
    $params = [
        'user_info_type' => $data['user_info_type'] ?? $data['login_type'] ?? '',
    ];
    $YJ_uid = $data['YJ_uid'] ?? 0;
    try {
        $OutApiObj = new OutApi('APPID_demo','APPSECRET_demo');
        return $OutApiObj->httpRequest('/user/info/'.$YJ_uid,$params);
    } catch (\Exception $e) {
        echo resultData($e->getMessage(),[],0);die;
    }
}
// 积分明细
function getUserSubsidy($data)
{
    $params = [
        'user_info_type' => $data['user_info_type'] ?? $data['login_type'] ?? '',
        'query_type' => $data['query_type'] ?? 'integral',
        'page' => 1,
        'limit' => 1
    ];
    $YJ_uid = $data['YJ_uid'] ?? 0;
    try {
        $OutApiObj = new OutApi('APPID_demo','APPSECRET_demo');
        return $OutApiObj->httpRequest('/user/subsidy_details/'.$YJ_uid,$params);
    } catch (\Exception $e) {
        echo resultData($e->getMessage(),[],0);die;
    }
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
    "openid": "o_nta5RXHmpH1o8VTOdjRz2GD7DI",
    "login_type": "routine",
    "unionid": "ofB9I5r5j8lZzLS5v0zB0zy1sNPg"
}';
    // "login_type": "routine",
    // "unionid": "ofB9I5r5j8lZzLS5v0zB0zy1sNPg",
    // "openid": "o_nta5RXHmpH1o8VTOdjRz2GD7DI",
    // "YJ_uid": 13205146
$data = json_decode($jsonData ,true);
try {
    // $OutApiObj = new OutApi('APPID_demo','APPSECRET_demo');
    // var_export($OutApiObj->getToken());exit;

    $userInfo = getYjUserUid($data);
    $userInfo += ['userInfo'=>getUserInfo($userInfo)];

    $userInfo['query_type'] = 'integral';
    $userInfo += ['UserSubsidy_integral'=>getUserSubsidy($userInfo)];

    $userInfo['query_type'] = 'Withdrawal';
    $userInfo += ['UserSubsidy_Withdrawal'=>getUserSubsidy($userInfo)];
    echo resultData('ok',$userInfo);die;
} catch (\Exception $e) {
    echo resultData($e->getMessage(),[],0);die;
}