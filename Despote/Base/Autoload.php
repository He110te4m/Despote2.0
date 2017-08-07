<?php
/**
 * 自动加载类，遵循 PSR4 自动加载原则
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class Autoload
{
    public function register()
    {
        spl_autoload_register([$this, 'autoload']);
    }

    public function autoload($class)
    {
        if (class_exists($class, false)) {
            return;
        }
        if ($class[0] != '\\') {
            $class = '\\' . $class;
        }
        $vendor = PATH_ROOT;
        $path   = $vendor . str_replace('\\', '/', $class) . '.php';
        if (file_exists($path)) {
            require $path;
            return;
        // } else {
        //     $vendor = PATH_EXTEND;
        //     $path   = $vendor . str_replace('\\', '/', $class) . '.php';
        //     if (file_exists($path)) {
        //         self::$logFileLoaded[] = $path;
        //         require $path;
        //         return;
        //     }
        }
    }
}
