<?php
/**
 * 系统主配置文件
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */

return [
    // 默认控制器配置
    'default' => [
        'controller' => 'Index',
        'methon'     => 'index',
    ],
    // 数据库配置，可使用多个配置，但键名必须与这个相同
    'mysql' => [
        'type' => 'mysql',
        'host' => 'localhohst',
        'port' => '3306',
        'user' => 'root',
        'pwd'  => 'root',
        'name' => 'test',
        'option' => [],
    ],
];
