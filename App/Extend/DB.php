<?php
/**
 * PDO 操作类，包含连接与预处理功能，暂时只支持 MySQL 数据库
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace App\Extend;

class DB
{
    // 临时打开的 PDO 对象
    protected $pdo;
    // 数据库记录集对象
    protected $res;
    // 数据库表名前缀
    protected $prefix;

    public function connect($readOnly = false)
    {
    }
}
