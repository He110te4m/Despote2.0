<?php
/**
 * 控制器基类，定义控制器与视图交互的行为
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class Controller
{
    protected $view;

    public function __construct()
    {
        $this->view = new View();
    }

    public function render($view = 'index.php', $params = [], $layout = false, $layoutParams = [])
    {
        $this->view->render($view, $params, $layout, $layoutParams);
    }
}
