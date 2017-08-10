<?php
/**
 * 系统唯一入口
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */

version_compare(PHP_VERSION, '5.4.0', '>=') || exit('Sorry, Despote require php5.4 or higher.<br>很抱歉，Despote 需要 php5.4 或更高的版本。<br><a href="https://www.github.com/he110te4m/despote.git" target="_blank">查看项目地址</a>');

// 根目录
define('PATH_ROOT', __DIR__ . '/');

// 应用目录
define('PATH_APP', PATH_ROOT . 'App/');
// 视图目录
define('PATH_VIEW', PATH_APP . 'Views/');
define('PATH_LAYOUT', PATH_VIEW . 'Layout/');

// 配置目录
define('PATH_CONFIG', PATH_ROOT . 'Config/');

// 框架核心
define('PATH_CORE', PATH_ROOT . 'Despote/');
// 框架基础类
define('PATH_BASE', PATH_CORE . 'Base/');
// 框架扩展类
define('PATH_EXTEND', PATH_CORE . 'Extends/');

//计时开始
$mtime = explode(' ', microtime());
define('CORE_RUN_AT', $mtime[1] + $mtime[0]);

// 开启调试模式
define('DEBUG', true);

// 加载配置文件
$config = require PATH_CONFIG . 'Config.php';
// 加载框架文件
require PATH_CORE . 'App.php';

// 实例化框架类
$obj = new \Despote\App();
$obj->run();
