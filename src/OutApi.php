<?php
// +----------------------------------------------------------------------
// | Author: SunRain
// +----------------------------------------------------------------------
namespace YjOutApi\Out;

use YjOutApi\Out\tools\HttpService;
use YjOutApi\Out\tools\Cache;

class OutApi extends HttpService
{

    /**
     * 配置
     * @var string
     */
    protected $appid;

    /**
     * @var string
     */
    protected $appsecret;

    /**
     * @var Cache|null
     */
    protected $cache;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $cacheTokenPrefix = "_SunRain";

    /**
     * @var string
     */
    protected $apiHost = 'https://dc.yjmt191314.com/outapi';
    // protected $apiHost = 'http://www.yjv5.com/outapi';

    /**
     * 登录接口
     */
    protected $OutapiUrl = [
        'access_token' => 'access_token',
        'user' => 'user'
    ];
    /**
     * AccessTokenServeService constructor.
     * @param string $appid
     * @param string $appsecret
     * @param Cache|null $cache
     */
    public function __construct(string $appid, string $appsecret, $cache = null)
    {
        if (!$cache) {
            $cache = new Cache();
        }
        $this->appid = $appid;
        $this->appsecret = $appsecret;
        $this->cache = $cache;
        // var_dump($this->getConfig());
    }
    /**
     * 获取配置
     * @return array
     */
    public function getConfig()
    {
        return [
            'appid' => $this->appid,
            'appsecret' => $this->appsecret
        ];
    }

    /**
     * 获取缓存token
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getToken($force = false)
    {
        $accessTokenKey = md5($this->appid . '_' . $this->appsecret . $this->cacheTokenPrefix);
        $cacheToken = $this->cache->get($accessTokenKey);
        if (!$cacheToken || $force) {
            $getToken = $this->getTokenFromServer();
            $this->cache->set($accessTokenKey, $getToken['access_token'], $getToken['expire']??600);
            $cacheToken = $getToken['access_token'];
        }
        $this->accessToken = $cacheToken;
        return $cacheToken;

    }

    /**
     * 从服务器获取token
     * @return mixed
     */
    public function getTokenFromServer()
    {
        $params = $this->getConfig();
        $response = $this->postRequest($this->get('access_token'), $params);
        // var_dump($response);die;
        $response = json_decode($response,true);
        if (!$response) {
            throw new \Exception('获取token失败');
        }
        if ($response['status'] === 200) {
            return $response['data'];
        } else {
            throw new \Exception($response['msg']);
        }
    }

    /**
     * 请求
     * @param string $url
     * @param array $data
     * @param string $method
     * @param bool $isHeader
     * @return array|mixed
     */
    public function httpRequest(string $url, array $data = [], string $method = 'POST', bool $isHeader = true)
    {
        $header = [];
        if ($isHeader) {
            $this->getToken();
            if (!$this->accessToken) {
                throw new \Exception('配置已更改或token已失效');
            }
            $header = ['authori-zation:Bearer ' . $this->accessToken];
        }
        $res = $this->request($this->get($url), $method, $data, $header);
        if (!$res) {
            throw new \Exception('平台错误：发生异常，请稍后重试');
        }
        $result = json_decode($res, true) ?: false;
        // 判断登录失效重新发起登录
        if(isset($result['status']) && $result['status'] == 110006 && $isHeader){
            $this->getToken(true);
            if (!$this->accessToken) {
                throw new \Exception('配置已更改或token已失效');
            }
            $header = ['authori-zation:Bearer ' . $this->accessToken];
            $res = $this->request($this->get($url), $method, $data, $header);
            if (!$res) {
                throw new \Exception('平台错误：发生异常，请稍后重试');
            }
            $result = json_decode($res, true) ?: false;
        }
        if (!isset($result['status']) || $result['status'] != 200) {
            throw new \Exception(isset($result['msg']) ? '平台错误：' . $result['msg'] : '平台错误：发生异常，请稍后重试');
        }
        return $result['data'] ?? [];

    }

    /**
     * @param string $apiUrl
     * @return string
     */
    public function get(string $apiUrl = null)
    {
        return $this->apiHost .'/'. trim($apiUrl,'/');
    }
    // 推送用户信息
    public function getYjUserUid(array $data):int
    {
        $params = [
            'user_type' => $data['login_type'] ?? '',
            'unionid' => $data['unionid'] ?? '',
            'openid' => $data['openid'] ?? '',
            'ali_user_id' => $data['ali_user_id'] ?? '',
            'phone' => $data['phone'] ?? '',
        ];
        $response = $this->httpRequest('user',$params);
        $uid = $response['uid'] ?? 0;
        if(!$uid) throw new \Exception('接口地址有误！');
        return $uid;
    }
    // 获取用户信息
    public function getUserInfo(array $data):array
    {
        $params = [
            'user_info_type' => $data['user_info_type'] ?? $data['login_type'] ?? '',
        ];
        $uid = $data['uid'] ?? $data['YJ_uid'] ?? 0;
        return $this->httpRequest('user/info/'.$uid,$params);
    }
    // 获取用户积分明细
    public function getUserSubsidy($data):array
    {
        $params = [
            'user_info_type' => $data['user_info_type'] ?? $data['login_type'] ?? '',
            'query_type' => $data['query_type'] ?? 'integral',
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 20
        ];
        $uid = $data['uid'] ?? $data['YJ_uid'] ?? 0;
        return $this->httpRequest('/user/subsidy_details/'.$uid,$params);
    }
}
