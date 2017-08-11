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
        $half = ($startLine + $endLine) / 2;
        // 转到第N行, seek方法参数从0开始计数
        $fp->seek($startLine - 1);
        for ($i = 0; $i <= $count; ++$i) {
            $nowline = $startLine + $i;
            // current()获取当前行内容
            $content[] = sprintf("%03d.\t\t%s", $nowline, $fp->current());
            if ($nowline == $half) {
                $content[$i] = '<span style="background-color: red;">' . $content[$i] . '</span>';
            }
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
        // var_dump($errno);
        $explain = isset(self::$map[$errno]) ? self::$map[$errno] : '未知错误';
        $code    = self::getLine($errfile, $errline -5, $errline + 5);
        // echo "<pre>";
        // var_dump(debug_backtrace());
        // echo "</pre>";
        // echo "<br>-----<br>";
        echo <<<EOF
<meta charset="utf8">
<style>
pre {
    display: block;
    font-family: Monaco, Menlo, Consolas, "Courier New", monospace;
    padding: 9.5px;
    margin-bottom: 10px;
    font-size: 14px;
    line-height: 16px;
    word-break: break-all;
    word-wrap: break-word;
    white-space: pre;
    white-space: pre-wrap;
    background-color: #f5f5f5;
    border: 1px solid #ccc;
    border-radius: 4px;
    color: #333;
}
</style>
<h3>错误详情</h3>
<pre>
    'Type'    => {$errno}
    'Detail'  => {$errstr}
    'Explain' => {$explain}
    'File'    => {$errfile}
    'Line'    => {$errline}
    'Time'    => {$time}
</pre>
<h3>错误追踪</h3>
<pre>{$code}</pre>
EOF;
    }

    public static function fatalHandle()
    {
    }
}

