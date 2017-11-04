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
    /**
     * 开启监听
     */
    public function register()
    {
        // 自定义异常处理
        set_exception_handler([$this, 'onException']);
        // 自定义错误处理
        set_error_handler([$this, 'onError']);
        // 自定义致命错误处理
        register_shutdown_function([$this, 'onShutdown']);
    }

    /**
     * 关闭监听
     */
    public function unregister()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * 异常处理
     * @param  Object $exception 异常对象
     */
    public function onException($exception)
    {
        // 获取错误信息
        $msg = $this->getLine($exception->getFile(), $exception->getLine() - 5, $exception->getLine() + 5);
        // 获取错误代码
        $code = $msg['code'];
        // 获取错误追踪
        $trace = $msg['trace'];

        // 输出错误信息
        echo <<<EOF
<div style="font-family: 'Consolas'; width: 100%; border: 1px solid #000;">
    <h1 style="margin: 0; padding: 5px 10px; font-size: 14px; border-bottom: 1px solid #000;">
        An exception occurred while Despote running.
    </h1>
    <ul style="list-style: none; padding: 5px 10px; margin: 0; font-size: 13px; background-color: #000; color: #fff;">
        <li>Despote Framework [Version 2.0]. Copyright (c) 2017 He110. All rights reserved.</li>
        <li>Copyright (c) 2017 He110. All rights reserved.</li>
        <li style="color: green;">[root@He110 ~] Exception Code ：$code </li>
        <li style="color: red;">[root@He110 ~] Exception Info ：{$exception->getMessage()} </li>
        <li style="color: blue;">[root@He110 ~] Exception File ：{$exception->getFile()} </li>
        <li style="color: yellow;">[root@He110 ~] Exception Line ：{$exception->getLine()} </li>
        <li>&nbsp;</li>
        $trace
    </ul>
</div>
EOF;
    }

    public function onError($errno, $errstr, $errfile, $errline)
    {
        $this->display($errstr, $errfile, $errline);
    }

    /**
     * 当程序停止运行时调用，尝试捕获错误
     * @return [type] [description]
     */
    public function onShutdown()
    {
        // 获取异常信息
        $error = error_get_last();

        if ($error) {
            $this->display($error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * 错误追踪
     * @param  String  $filename  文件名，包含文件路径
     * @param  integer $startLine 起始代码行
     * @param  integer $endLine   结束代码行
     * @param  string  $mode      以什么模式打开文件
     * @return Array              错误代码行(code) 和 错误追踪(trace)
     */
    private function getLine($filename, $startLine = 1, $endLine = 20, $mode = 'rb')
    {
        $content = [];
        $count   = $endLine - $startLine;
        $fp      = new \SplFileObject($filename, $mode);
        $half    = ($startLine + $endLine) / 2;
        // 转到第N行, seek方法参数从0开始计数
        $fp->seek($startLine - 1);
        for ($i = 0; $i <= $count; ++$i) {
            $nowline = $startLine + $i;
            // current()获取当前行内容
            if ($nowline == (($startLine + $endLine) / 2)) {
                $msg['code'] = trim($fp->current());
                $content[]   = sprintf("<li>[root@He110 ~] %s </li>", $msg['code']);
            } else {
                $content[] = sprintf("<li>[root@He110 ~] %s </li>", $fp->current());
            }
            // 下一行
            $fp->next();
            if ($fp->eof()) {
                array_pop($content);
                break;
            }
        }
        $msg['trace'] = implode('', array_filter($content));

        return $msg;
    }

    private function display($errstr, $errfile, $errline)
    {
        // 获取错误信息
        $msg = $this->getLine($errfile, $errline - 5, $errline + 5);
        // 获取错误代码
        $code = $msg['code'];
        // 获取错误追踪
        $trace = $msg['trace'];

        // 输出错误信息
        echo <<<EOF
<div style="font-family: 'Consolas'; width: 100%; border: 1px solid #000;">
    <h1 style="margin: 0; padding: 5px 10px; font-size: 14px; border-bottom: 1px solid #000;">
        An error occurred while Despote running.
    </h1>
    <ul style="list-style: none; padding: 5px 10px; margin: 0; font-size: 13px; background-color: #000; color: #fff;">
        <li>Despote Framework [Version 2.0]. Copyright (c) 2017 He110. All rights reserved.</li>
        <li>Copyright (c) 2017 He110. All rights reserved.</li>
        <li style="color: green;">[root@He110 ~] Error Code ：$code </li>
        <li style="color: red;">[root@He110 ~] Error Info ：$errstr </li>
        <li style="color: blue;">[root@He110 ~] Error File ：$errfile </li>
        <li style="color: yellow;">[root@He110 ~] Error Line ：$errline </li>
        <li>&nbsp;</li>
        $trace
    </ul>
</div>
EOF;
    }
}
