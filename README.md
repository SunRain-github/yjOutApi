# yjOutApi
予集对外接口SDK

## 安装
```shell
composer require yj_out_api/out
```

## demo.php
```
<?php
// +----------------------------------------------------------------------
// | Author: SunRain
// +----------------------------------------------------------------------

require '../vendor/autoload.php';
use EasyWeChat\Factory;
use YjOutApi\Out\Outapi;

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
```
# 微信官方接口获取用户的 unionid 和 openid
```
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
$data = json_decode($jsonData ,true);
$APPID_demo = 'APPID_demo'; // 测试账号 appid
$APPSECRET_demo = 'APPSECRET_demo'; // 测试密钥 appsecret
try {
    $OutApiObj = new OutApi($APPID_demo,$APPSECRET_demo);
    ```
    # 推送用户信息并获取予集用户id
    ```
    $YJ_uid = $OutApiObj->getYjUserUid($data);
    echo '$YJ_uid = '.$YJ_uid.PHP_EOL.PHP_EOL;
    // 公共条件
    $publicWhere = [
        'user_info_type'=>$data['login_type'],
        'uid'=>$YJ_uid,
        'page' => 1,
        'limit' => 1
    ];
    # 获取用户信息（包含冻结和乐园积分）
    $userInfo = $OutApiObj->getUserInfo($publicWhere);
    echo '$userInfo = '. var_export($userInfo,true).PHP_EOL.PHP_EOL;
    # 获取用户乐园积分明细
    $publicWhere['query_type'] = 'integral';
    $UserSubsidy_integral = $OutApiObj->getUserSubsidy($publicWhere);
    echo '$UserSubsidy_integral = '. var_export($UserSubsidy_integral,true).PHP_EOL.PHP_EOL;
    # 获取用户冻结积分明细
    $publicWhere['query_type'] = 'Withdrawal';
    $UserSubsidy_Withdrawal = $OutApiObj->getUserSubsidy($publicWhere);
    echo '$UserSubsidy_Withdrawal = '. var_export($UserSubsidy_Withdrawal,true).PHP_EOL.PHP_EOL;
    # 小程序支付
    $pay_type = 'routine';//支付渠道
    // 支付参数
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
```
