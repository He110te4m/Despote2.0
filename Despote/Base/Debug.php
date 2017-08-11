<?php
/**
 * 统计运行时的信息，方便调试用
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class Debug
{
    public static function display()
    {
        // 获取脚本结束时程序内存占用
        $END_MEMORY = memory_get_usage();
        // 获取脚本结束时时间戳
        $mtime = explode(' ', microtime());
        $CORE_STOP_AT = $mtime[1] + $mtime[0];

        // 统计内存占用
        $memoryCost = $END_MEMORY - START_MEMORY;
        $memoryCost = $memoryCost / 1048576;
        // 统计时间花费
        $timeCost = $CORE_STOP_AT - CORE_RUN_AT;

        echo <<<EOF
<div style="font-family: 'Consolas'; width: 100%; border: 1px solid #000;">
    <h1 style="margin: 0; padding: 5px 10px; font-size: 14px; border-bottom: 1px solid #000;">
        Time and space expenditure statistics while Despote running.
    </h1>
    <ul style="list-style: none; padding: 5px 10px; margin: 0; font-size: 13px; background-color: #000; color: #fff;">
        <li>Despote Framework [Version 2.0]. Copyright (c) 2017 He110. All rights reserved.</li>
        <li>Copyright (c) 2017 He110. All rights reserved.</li>
        <li>[root@He110 ~] Run Time：$timeCost s</li>
        <li>[root@He110 ~] Memory statistics：$memoryCost MB</li>
    </ul>
</div>
EOF;
    }
}
