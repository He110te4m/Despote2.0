<?php
/**
 *
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote;

class App
{
    public function run()
    {
        // 设置 head 头
        $this->setHeader();
        // 开启自动加载函数
        $this->setAutoload();
        // 设置路由解析
        $this->setRoute();
    }

    private function setHeader()
    {
        header('Content-type: text/html; charset=UTF-8;');
    }

    private function setAutoload()
    {
        require PATH_BASE . 'Autoload.php';
        $autoloadObj = new \Despote\Base\Autoload();
        $autoloadObj->register();
    }

    private function setRoute()
    {
        $routeObj = new \Despote\Base\Route();
        $routeObj->parse();
    }
}
