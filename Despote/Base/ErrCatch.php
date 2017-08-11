<?php
/**
 * 自定义错误处理类，自动追踪出错文件并显示出错代码，默认显示包括错误代码在内的 10 行代码
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class ErrCatch
{
    private static $map = [
        '1'     => '运行时致命的错误',
        '2'     => '运行时非致命的错误',
        '4'     => '编译时语法解析错误',
        '8'     => '运行时通知',
        '16'    => 'PHP 初始化启动过程中发生的致命错误',
        '32'    => 'PHP 初始化启动过程中发生的警告 ',
        '64'    => '致命编译时错误',
        '128'   => '编译时警告',
        '256'   => '用户产生的错误信息',
        '512'   => '用户产生的警告信息',
        '1024'  => '用户产生的通知信息',
        '2048'  => 'PHP 对代码的修改建议',
        '4096'  => '可被捕捉的致命错误',
        '8192'  => '运行时通知',
        '16384' => '用户产生的警告信息',
        '32767' => 'E_STRICT 触发的所有错误和警告信息',
    ];

    public static function listen()
    {
        // // 自定义异常处理
        // set_exception_handler(['\Despote\Base\ErrCatch', 'exceptionHandle']);
        // 自定义普通错误处理
        set_error_handler(['\Despote\Base\ErrCatch', 'errorHandle']);
        // // 自定义致命错误处理
        // register_shutdown_function(['\Despote\Base\ErrCatch', 'fatalHandle']);
    }

    private static function getLine($filename, $startLine = 1, $endLine = 20, $method = 'rb')
    {
        $content = [];
        $count   = $endLine - $startLine;
        $fp      = new \SplFileObject($filename, $method);
        $half    = ($startLine + $endLine) / 2;
        // 转到第N行, seek方法参数从0开始计数
        $fp->seek($startLine - 1);
        for ($i = 0; $i <= $count; ++$i) {
            $nowline = $startLine + $i;
            // current()获取当前行内容
            $content[] = sprintf("<li>[root@He110 ~] %s </li>", $fp->current());
            // 下一行
            $fp->next();
            if ($fp->eof()) {
                array_pop($content);
                break;
            }
        }

        // array_filter过滤：false,null,''
        return implode('', array_filter($content));
    }

    private static function getCode($file, $line, $length = 4096)
    {
        $returnTxt = null; // 初始化返回
        $i         = 1; // 行数

        $handle = @fopen($file, "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, $length);
                if ($line == $i) {
                    $returnTxt = $buffer;
                }

                $i++;
            }
            fclose($handle);
        }
        return $returnTxt;
    }

    public static function exceptionHandle($exception)
    {
    }

    public static function errorHandle($errno, $errstr, $errfile, $errline)
    {
        // 获取报异常时间
        $mtime = explode(' ', microtime());
        $Etime = $mtime[1] + $mtime[0];
        // 获取系统启动时间
        $Stime = CORE_RUN_AT;
        // 获取页面执行时间
        $time = $Etime - $Stime;
        // // 获取错误详情
        // $explain = isset(self::$map[$errno]) ? self::$map[$errno] : '未知错误';
        // 获取错误代码
        $code = self::getCode($errfile, $errline);
        // 获取错误追踪
        $trace = self::getLine($errfile, $errline - 5, $errline + 5);

        // 输出错误信息
        echo <<<EOF
<div style="font-family: 'Consolas'; width: 100%; border: 1px solid #000;">
    <h1 style="margin: 0; padding: 5px 10px; font-size: 14px; border-bottom: 1px solid #000;">
        An error occurred while Despote running.
    </h1>
    <ul style="list-style: none; padding: 5px 10px; margin: 0; font-size: 13px; background-color: #000; color: #fff;">
        <li>Despote Framework [Version 2.0]. Copyright (c) 2017 He110. All rights reserved.</li>
        <li>Copyright (c) 2017 He110. All rights reserved.</li>
        <li>[root@He110 ~] Error Code ：$code </li>
        <li>[root@He110 ~] Error Info ：$errstr </li>
        <li>[root@He110 ~] Error File ：$errfile </li>
        <li>[root@He110 ~] Error Line ：$errline </li>
        <li>&nbsp;</li>
        $trace
    </ul>
</div>
EOF;
    }

    public static function fatalHandle()
    {
    }
}
