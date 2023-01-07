<?php
// +----------------------------------------------------------------------
// | Author: SunRain
// +----------------------------------------------------------------------
namespace YjOutApi\Out\tools;
use think\facade\Cache as thinkCache;

class Cache
{
    /**
     * 标签名
     * @var string
     */
    protected static $globalCacheName = '_accessToken_1515146130';
    public function __construct()
    {
        // 缓存配置
        thinkCache::config([
            'default'   =>  'file',
            'stores'    =>  [
                'file'  =>  [
                    'type'   => 'File',
                    // 缓存保存目录
                    'path'   => './tools/runtime/cache/',
                    // 缓存前缀
                    'prefix' => self::$globalCacheName,
                    // 缓存有效期 0表示永久缓存
                    'expire' => 5,
                ]
            ],
        ]);
    }
    /**
     * 获取缓存
     * @return array
     */
    public function get(string $name)
    {
        return thinkCache::get($name,null);
    }
    /**
     * 写入缓存
     * @param string $name 缓存名称
     * @param mixed $value 缓存值
     * @param int $expire 缓存时间，为0读取系统缓存时间
     * @return bool
     */
    public function set(string $name, $value, int $expire = 86000)
    {
        return thinkCache::set($name,$value,$expire);
    }

 
}
