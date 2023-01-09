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
    // protected $apiHost = 'https://dc.yjmt191314.com/outapi/';
    protected $apiHost = 'http://www.yjv5.com/outapi/';

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
        $response = json_decode($response,true);
        
        // var_dump($response);die;
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
        if(!isset($this->OutapiUrl[$apiUrl])) throw new \Exception('接口地址有误！');
        return $this->apiHost . $this->OutapiUrl[$apiUrl];
    }
}
