<?php
/**
 * 数据库日志操作类
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class Logger
{
    //////////////
    // 日志属性 //
    //////////////
    // 日志存储路径
    private $path = PATH_LOG;
    // 日志等级划分
    private $level = [
        // 操作日志，比如访问某页面等
        'info'  => 5,
        // 调试时输出的信息
        'debug' => 4,
        // 有安全风险的操作，如：登陆等
        'warn'  => 3,
        // 系统运行错误
        'error' => 2,
        // 系统致命错误，将导致脚本结束运行
        'fatal' => 1,
    ];
    // 设置日志颜色
    private $color = [
        // 操作日志为绿色
        'info'  => '#00FF00',
        // Debug 信息为默认颜色
        'debug' => '#FFFFFF',
        // 警告为黄色
        'warn'  => '#FFFF00',
        // 系统错误为红色
        'error' => '#FF0000',
        // 系统致命错误为红色
        'fatal' => '#FF0000',
    ];
    // 记录日志的级别，默认全显示
    private $limit = 5;
    // 日志文件头
    private $head = <<<EOF
<meta charset="utf8">
<style>
    .log {
        font-family: 'Consolas';
        width: 100%;
        border: 1px solid #000000;
    }
    .log h1 {
        margin: 0;
        padding: 5px 10px;
        font-size: 14px;
        border-bottom: 1px solid #000000;
    }
    .log ul {
        list-style: none;
        padding: 5px 10px;
        margin: 0;
        font-size: 13px;
        background-color: #000000;
        color: #FFFFFF;
    }
</style>
<div class="log">
    <h1>Despote Framework operation log</h1>
    <ul>
        <li>Despote Framework [Version 2.0]. Copyright (c) 2017 He110. All rights reserved.</li>
        <li>Copyright (c) 2017 He110. All rights reserved.</li>
EOF;

    /**
     * 初始化日志记录的级别
     * @param integer $limit 可选值为 0-5，0 为不启用日志，1 为只记录导致系统停止运行的错误，2 为记录导致系统停止运行的错误 和 运行错误，3 为记录导致系统停止运行的错误、运行错误 和 警告，4 为记录导致系统停止运行的错误、运行错误、警告 和 调试信息，5 为记录导致系统停止运行的错误、运行错误、警告、调试信息 和 操作日志
     */
    public function __construct($limit = 5)
    {
        $this->limit = $limit;
    }

    /**
     * 记录日志
     * @param  String $level 可选值为：fatal(导致系统停止运行的致命错误)、error(程序运行错误)、warn(警告)、debug(Debug 显示的信息)、info(操作日志)
     * @param  String $msg   需要记录的信息
     */
    public function log($level, $msg)
    {
        if ($this->level[$level] > $this->limit) {
            return;
        }

        // 记录日志的时间
        $time = date('Y-m-d H-i-s');
        // 日志存放地址
        $file = $this->path . date('Y-m-d') . '.html';
        // 日志颜色高亮
        $color = $this->color[$level];
        $level = strtoupper($level);
        // 日志模板
        $tpl = <<<EOF
        <li>[root@He110 ~] log -d $time</li>
        <li><span style="color: {$color}">[ $level ]</span> $msg</li>
        <li></li>
EOF;

        // 写入日志
        is_file($file) || file_put_contents($file, $this->head, LOCK_EX);
        file_put_contents($file, $tpl, FILE_APPEND | LOCK_EX);
    }
}
