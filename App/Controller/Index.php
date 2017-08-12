<?php
/**
 * 默认控制器，用于测试路由解析 和 控制器和视图的交互
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace App\Controller;

class Index extends \Despote\Base\Controller
{
    // 默认动作，用于测试路由解析
    public function index()
    {
        $this->render('index.php', [], 'Default.php');
    }
}
