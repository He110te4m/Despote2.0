<?php
/**
 *
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class Cache
{
    //////////////////
    // 缓存属性配置 //
    //////////////////
    // 缓存文件目录，默认值为 /Despote/Runtime/Caches/
    private $path = PATH_CACHE;
    // 自动清理设置，取值为 0-100，数值越小清理越频繁
    private $gc = 100;

    /**
     * 构造函数，传入数组初始化缓存对象
     * @param array $setting 'path' => '缓存文件目录', 'gc' => '自动清理设置'
     */
    public function __construct($setting = [])
    {
        empty($setting['path']) || $this->path = $setting['path'];
        is_dir($this->path) || @mkdir($this->path, 0777, true);
        (empty($setting['gc']) && $setting['gc'] >= 0 && $setting['gc'] <= 100) || $this->gc = $setting['gc'];
    }

    /**
     * 根据缓存键名获取缓存文件对应文件名
     * @param  String $key 缓存键名
     * @return String      缓存文件名 (含绝对路径)
     */
    private function getFilename($key)
    {
        return $this->path . DS . md5($key) . '.cache';
    }

    /**
     * 校验缓存文件有效性
     * @param  String  $key 缓存键名
     * @return Boolean      是否有效
     */
    private function check_key($key)
    {
        return @filemtime($this->getFilename($key)) > time();
    }

    /**
     * 设置文件缓存，该方法会覆盖原有的缓存文件
     * @param String  $key       缓存键名
     * @param Mixed   $value     缓存值
     * @param integer $cacheLife 缓存有效时间，时间戳格式
     */
    public function set($key, $value, $cacheLife = 0)
    {
        // 启动随机清理缓存机制
        $this->gc();

        $filename = $this->getFilename($key);
        if (file_put_contents($filename, serialize($value), LOCK_EX) !== false) {
            $expire = time() + ($cacheLife > 0 ? $cacheLife : 259200);

            return touch($filename, $expire);
        }
        return false;
    }

    /**
     * 批量设置文件缓存，该方法会覆盖原有的缓存文件
     * @param  Array   $datas     缓存键值对数组
     * @param  integer $cacheLife 缓存有效时间，时间戳格式
     * @return Array              添加失败的缓存键名列表
     */
    public function mset($datas, $cacheLife = 0)
    {
        // 添加缓存失败的键名列表
        $failKeys = [];

        foreach ($datas as $key => $value) {
            if (false === $this->set($key, $value, $cacheLife)) {
                $failKeys[] = $key;
            }
        }

        return $failKeys;
    }

    /**
     * 添加文件缓存，如果原缓存未失效，则不覆盖
     * @param String  $key       缓存键名
     * @param Mixed   $value     缓存值
     * @param integer $cacheLife 缓存有效时间，时间戳格式
     */
    public function add($key, $value, $cacheLife = 0)
    {
        // 启动随机清理缓存机制
        $this->gc();

        return $this->check_key($key) ? false : $this->set($key, $value, $cacheLife);
    }

    /**
     * 批量添加文件缓存，如果原缓存未失效，则不覆盖
     * @param  Array   $datas     缓存键值对数组
     * @param  integer $cacheLife 缓存有效时间，时间戳格式
     * @return Array              添加失败的缓存键名列表
     */
    public function madd($datas, $cacheLife = 0)
    {
        // 添加缓存失败的键名列表
        $failKeys = [];

        foreach ($datas as $key => $value) {
            if (false === $this->add($key, $value, $cacheLife)) {
                $failKeys[] = $key;
            }
        }

        return $failKeys;
    }

    /**
     * 获取缓存值
     * @param  String $key 缓存键名
     * @return Mixed       缓存值
     */
    public function get($key)
    {
        $filename = $this->getFilename($key);

        // 使用文件独占锁读取文件并反序列化
        if ($this->check_key($key) && $fp = @fopen($filename, 'r')) {
            @flock($fp, LOCK_SH);
            $value = unserialize(stream_get_contents($fp));
            @flock($fp, LOCK_UN);
            @fclose($fp);

            return $value;
        }
        return false;
    }

    /**
     * 批量获取缓存值
     * @param  Array  $keys 缓存键名列表
     * @return Array        缓存键值对数组
     */
    public function mget($keys)
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * 删除文件缓存
     * @param  String  $key 缓存键名
     * @return Boolean      缓存是否删除成功
     */
    public function del($key)
    {
        return @unlink($this->getFilename($key));
    }

    public function gc($all = false)
    {
        if ($all) {
            foreach (glob($this->path . DS . '*.cache') as $file) {
                @unlink($file);
            }
        } else if(mt_rand(0, 100) > $this->gc) {
            foreach (glob($this->path . DS . '*.cache') as $file) {
                if (@filemtime($file) < time()) {
                    @unlink($file);
                }
            }
        }
    }

    public function flush()
    {
        $this->gc(true);
    }
}
