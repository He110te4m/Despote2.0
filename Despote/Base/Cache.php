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
    /**
     * 根据不同的缓存类型获取缓存操作类对象
     * @param  string $type   缓存类型，可选值为：file(文件缓存)、memcache(memcache 缓存)
     * @param  array  $config [description]
     * @return [type]         [description]
     */
    public static function getObject($type = "file", $config = [])
    {
        $type = strtolower($type);

        switch ($type) {
            case 'file':
                $object = new \Despote\Base\Cache\FileCache($config);
                break;
            case 'memcache':
                $object = new \Despote\Base\Cache\MemCache($config);
                break;
            default:
                // 不是任何一种缓存，返回 false
                $object = false;
                break;
        }

        return $object;
    }
}
