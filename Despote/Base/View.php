<?php
/**
 * 视图类，用于定义视图的渲染以及布局的加载方法
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class View
{
    public function render($view = 'index.php', $params = [], $layout = false, $layoutParams = [])
    {
        $content = $this->renderView(PATH_VIEW . $view, $params);
        // var_dump($content);
        echo empty($layout) ? $content : $this->renderView(
            PATH_LAYOUT . $layout,
            array_merge($layoutParams, ['container' => $content])
        );
    }

    private function renderView($view = 'index.php', $params = [])
    {
        // 开启输出缓存
        ob_start();
        // 开启绝对刷送，即每次操作都会自动 flush，无需手动使用 flush
        ob_implicit_flush(false);
        // 如果参数表不为空则分配变量
        empty($params) || extract($params);
        // 包含视图文件
        require $view;

        // 获取 PHP 解析后的视图文件并作为字符串返回
        return ob_get_clean();
    }
}
