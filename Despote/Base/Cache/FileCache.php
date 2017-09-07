<?php
/**
 * 文件缓存类，提供不覆盖原有缓存的 add 方法、覆盖原有缓存的 set 方法、获取已有缓存的 get 方法、刷新所有缓存的 flush 方法、删除指定缓存的 del 方法。其中 add、set、get 支持批量操作，批量操作的方法分别为：madd、mset、mget
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base\Cache;

class FileCache
{
    //////////////////
    // 文件缓存设置 //
    //////////////////
    // 文件缓存目录
    public $path = PATH_CACHE;
    // 随机缓存设置，取值为 0-100，设置的值越小，缓存刷新越频繁
    public $gc = 100;

    /**
     * 初始化缓存类对象
     * @param array $config 缓存配置数组
     */
    public function __construct($config = [])
    {
        isset($config['path']) && $this->path = $config['path'];
        is_dir($this->path) || @mkdir($this->path, 0777, true);
        (isset($config['gc']) && $config['gc'] >= 0 && $config['gc'] <= 100) && $this->gc = $config['gc'];
    }

    /**
     * 添加缓存，缓存文件如果存在且有效，则不添加
     * @param String  $key   缓存键名
     * @param Mixed   $value 缓存文件对应的值
     * @param integer $ttl   缓存过期时间，格式为时间戳
     */
    public function add($key, $value, $ttl = 259200)
    {
        $this->gc();

        return $this->exist($key) ? false : $this->set($key, $value, $ttl);
    }

    /**
     * 添加一组缓存，如果缓存已经存在，此次设置的值不会覆盖原来的值
     * @param  Array   $data 缓存的键值对数组
     * @param  integer $ttl  缓存文件过期时间
     * @return Array         成功加入缓存的键名数组
     */
    public function madd($data, $ttl = 259200)
    {
        $fieldKeys = [];
        foreach ($data as $key => $value) {
            if (false === $this->add($key, $value, $ttl)) {
                $fieldKeys[] = $key;
            }
        }

        return $fieldKeys;
    }

    /**
     * 添加一个缓存，如果缓存已经存在，此次缓存会覆盖原来的值并且重新设置生存时间
     * @param String  $key   缓存文件的键名
     * @param Mixed   $value 缓存文件的值
     * @param integer $ttl   缓存文件的过期时间
     */
    public function set($key, $value, $ttl = 259200)
    {
        $this->gc();
        $cacheFile = $this->cacheFile($key);
        if (file_put_contents($cacheFile, serialize($value), LOCK_EX) !== false) {
            // 直接将过期时间写在文件修改时间上，判断是否有效时就可以直接读取修改时间就是缓存过期时间，如果没有声明过期时间或者过期时间格式错误，则默认为三天后
            return touch($cacheFile, time() + ($ttl > 0 ? $ttl : 259200));
        }

        return false;
    }

    /**
     * 添加一组缓存，如果缓存已经存在,此次缓存会覆盖原来的值并且重新设置生存时间
     * @param  Array   $data 需要设置缓存的键值对数组
     * @param  integer $ttl  缓存过期时间
     * @return Array         成功添加缓存的键名数组
     */
    public function mset($data, $ttl = 0)
    {
        $fieldKeys = [];
        foreach ($data as $key => $value) {
            if (false === $this->set($key, $value, $ttl)) {
                $fieldKeys[] = $key;
            }
        }

        return $fieldKeys;
    }

    /**
     * 从缓存中读取存储的变量
     * @param  String $key 缓存对应的键名
     * @return Mixed       缓存对应的值
     */
    public function get($key)
    {
        $cacheFile = $this->getFilename($key);

        // 判断缓存存在并且可读，满足条件则使用文件锁读取缓存值
        if ($this->exist($key) && $fp = @fopen($cacheFile, 'r')) {
            @flock($fp, LOCK_SH);
            $value = unserialize(stream_get_contents($fp));
            @flock($fp, LOCK_UN);
            @fclose($fp);

            return $value;
        }

        return false;
    }

    /**
     * 从缓存中提取一组存储的变量
     * @param  Array $keys 需要读取的缓存键名数组
     * @return Array       读取出来的缓存键值对，没有缓存的键名不会记录在这里面
     */
    public function mget($keys)
    {
        $values = [];
        foreach ($keys as $key) {
            if ($this->exist($key)) {
                $values[$key] = $this->get($key);
            }
        }

        return $values;
    }

    /**
     * 从缓存中删除存储的变量
     * @param  String  $key 缓存键名
     * @return Boolean      缓存删除结果
     */
    public function del($key)
    {
        return @unlink($this->cacheFile($key));
    }

    /**
     * 清除所有缓存
     * @return Boolean 删除结果
     */
    public function flush()
    {
        $this->gc(true);
        return true;
    }

    /**
     * 获取缓存对应的文件名
     * @param  String $key 缓存键名
     * @return String      缓存对应的文件名，包含绝对地址
     */
    private function getFilename($key)
    {
        return $this->path . DS . md5($key) . '.cache';
    }

    /**
     * 检查缓存是否存在
     * @param  String  $key 缓存键名
     * @return Boolean      缓存是否存在
     */
    private function exist($key)
    {
        $cacheFile = $this->cacheFile($key);
        return @filemtime($cacheFile) > time();
    }

    /**
     * 垃圾清理机制，如果参数为 true，则全部清理，否则随机判断是否执行缓存清理
     * @param  boolean $all 是否清理所有缓存文件
     */
    private function gc($all = false)
    {
        if ($all) {
            foreach (glob($this->path . DS . '*.cache') as $file) {
                @unlink($file);
            }
        } else if (mt_rand(0, 100) > $this->gc) {
            foreach (glob($this->path . DS . '*.cache') as $file) {
                if (@filemtime($file) < time()) {
                    @unlink($file);
                }
            }
        }
    }
}
