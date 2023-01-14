<?php
/**
 */
namespace YjOutApi\Out\tools;

Class Cache
{
    private $path;

    public function __construct($params = [])
    {
        if (!empty($params['path'])) {
            $this->path =  $params['path'];
        }else{
            $this->path = dirname(__FILE__) .'/runtime/cache/';
        }
    }

    /*
     * @param key is file name
     * @return data or null
     * */
    public function get($key)
    {
        $file_path = $this->path . $key;
        $data = @file_get_contents($file_path);
        if (empty($data)) return null;
        $data = unserialize($data);
        $expire_time = $data['expire_time'];
        if (time() < $expire_time) {
            return $data['value'];
        }
        $this->delete($key);
        return null;
    }


    /*
     * @param key file name
     * @param data  string or array
     * @param ttl  expire time
     * @param mode  open file mode
     * @return false or not false
     * */
    public function set($key, $value, $ttl = 86100, $mode = 'wb')
    {
        if(!file_exists($this->path)) mkdir($this->path,0777,true);
        if (!$fp = @fopen($this->path . $key, $mode)) {
            return FALSE;
        }
        $data = [
            'value' => $value,
            'expire_time' => time() + (int)$ttl
        ];
        $data = serialize($data);
        flock($fp, LOCK_EX);
        if (($result = fwrite($fp, $data)) === FALSE){
            return FALSE;
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        return is_int($result);
    }

    /*
    * @param key file name
     * */
    public function delete($key)
    {
        // Trim the trailing slash
        $path = rtrim($this->path . $key);
        @unlink($path);
        return (file_exists($path)) ? @rmdir($path) : TRUE;
    }


}