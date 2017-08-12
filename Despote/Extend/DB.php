<?php
/**
 * PDO 操作类，包含连接与预处理功能，暂时只支持 MySQL 数据库，SQLite 数据库测试中...
 *
 * 使用方法：
 * // 使用数组实例化本类，数组格式见 /Despote/Config/Config.php，用于初始化数据库配置
 * $db = new Despote\Extend\DB($config);
 * // 连接数据库，如果失败返回 false
 * if ($db-conn() === false) {
 *     exit("连接数据库失败，请检查数据库配置");
 * }
 * $uid = 10086;
 * $name = 'He110';
 * $pass = '123456';
 * // 插入数据：
 * $db->insert('user', 'name, pass', [$name, $pass]);
 * // 删除数据：
 * $db->delete('user', 'where name=? and pass=?', [$name, $pass]);
 * // 修改数据：
 * $db->update('user', 'uid=?', 'where name=? and pass=?', [$uid, $name, $pass]);
 * // 查找数据：
 * $db->select('uid', 'user, 'where name=? and pass=?', [$name, $pass]);
 * // 获取查找到的第一条数据：
 * $res->fetch();
 * // 获取全部数据：
 * $res->fetchAll();
 * // 执行自定义 SQL 语句：
 * $res = $db->execsql("select * from user");
 * // 执行带参数绑定的 SQL 语句：
 * $res = $db->execsql("select uid form user where name=?", [$name]);
 *
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Extend;

class DB
{
    //////////////
    // 单例对象 //
    //////////////
    // PDO 对象
    protected $pdo = null;

    ////////////////
    // 数据库配置 //
    ////////////////
    // 数据库类型
    protected $type;

    //////////////
    // 公共配置 //
    //////////////
    // 数据库连接选项，可选
    protected $opts;

    ////////////////
    // MySQL 配置 //
    ////////////////
    // 数据库主机地址
    protected $host;
    // 数据库端口，可选
    protected $port;
    // 数据库用户名
    protected $user;
    // 数据库密码
    protected $pwd;
    // 数据库名
    protected $name;
    // 是否开启持久连接，在多进程服务器（如fastcgi、php-fpm）中，使用数据库持久连接可以提升服务器性能和抗压能力，可选
    protected $pconn;
    // 默认字符集，可选
    protected $charset;
    // PDO 错误处理方式
    // 可选的常量有：
    // 1：只设置错误代码，缺省值
    // 2：除了设置错误代码以外，PDO 还将发出一条传统的 E_WARNING 消息。
    // 3：除了设置错误代码以外，PDO 还将抛出一个 PDOException，并设置其属性，以反映错误代码和错误信息。
    protected $errmode = \PDO::ERRMODE_SILENT;
    // 记录集返回方式
    // 可选的常量有：
    // 1：返回关联数组
    // 2：返回数字数组
    // 3：同时返回数字数组和关联数组
    // 4：将结果集中的每一行作为一个属性名对应列名的对象返回
    // 5：将结果集中的每一行作为一个对象返回，此对象的变量名对应着列名
    // 6：从结果集中的下一行返回所需要的那一列
    protected $fetch = \PDO::FETCH_ASSOC;
    // 是否开启模拟预处理
    protected $pretreat;

    /////////////////
    // SQLite 配置 //
    /////////////////
    // 数据库地址
    protected $file;
    // 是否开启强制磁盘同步
    // 可选值为：
    //   FULL  (完全磁盘同步：断电或死机不会损坏数据库，但是很慢)
    //   NORMAL(普通磁盘同步：大部分情况天断电或死机不会损坏数据库，比 OFF 慢)
    //   OFF   (不强制磁盘同步：断电或死机后很容易损坏数据库，但是插入或更新速度比 FULL 提升 50 倍，由系统自行将更改写入数据库中而不强制同步到磁盘)
    protected $sync;

    /**
     * 构造函数，用于初始化数据库操作类
     * @param   Array     $config     传入一个数组，格式见 /Config/Config.php，用于初始化数据库配置
     * @return                        如果失败则返回 false
     */
    public function __construct($config)
    {
        // 判断是否为数组
        if (!is_array($config)) {
            return false;
        }

        // 获取数据库类型，如果存在则赋值，不存在则返回 false
        if (isset($config['type'])) {
            $this->type = $config['type'];
        } else {
            return false;
        }

        switch ($config['type']) {
            case 'mysql':
                // 获取数据库地址，如果存在则赋值，不存在返回 false
                if (isset($config['host'])) {
                    $this->host = $config['host'];
                } else {
                    return false;
                }

                // 获取数据库用户名，如果存在则赋值，不存在返回 false
                if (isset($config['user'])) {
                    $this->user = $config['user'];
                } else {
                    return false;
                }

                // 获取数据库密码，如果存在则赋值，不存在返回 false
                if (isset($config['pwd'])) {
                    $this->pwd = $config['pwd'];
                } else {
                    return false;
                }

                // 获取数据库名，如果存在则赋值，不存在返回 false
                if (isset($config['name'])) {
                    $this->name = $config['name'];
                } else {
                    return false;
                }

                // 获取错误处理模式配置，如果获取不到，默认为 silent
                if (isset($config['errmode'])) {
                    switch ($config['errmode']) {
                        case 2:
                            $this->errmode = \PDO::ERRMODE_WARNING;
                            break;
                        case 3:
                            $this->errmode = \PDO::ERRMODE_EXCEPTION;
                            break;
                        case 1:
                        default:
                            $this->errmode = \PDO::ERRMODE_SILENT;
                            break;
                    }
                }

                // 设置获取数据的方式，如果没有设置，默认为关联数组
                if (isset($config['fetch'])) {
                    switch ($config['fetch']) {
                        case 2:
                            $this->fetch = \PDO::FETCH_NUM;
                            break;
                        case 3:
                            $this->fetch = \PDO::FETCH_BOTH;
                            break;
                        case 4:
                            $this->fetch = \PDO::FETCH_OBJ;
                            break;
                        case 5:
                            $this->fetch = \PDO::FETCH_LAZY;
                            break;
                        case 6:
                            $this->fetch = \PDO::FETCH_COLUMN;
                            break;
                        case 1:
                        default:
                            $this->fetch = \PDO::FETCH_ASSOC;
                            break;
                    }
                }

                // 是否开启模拟预处理，默认关闭
                $this->pretreat = isset($config['pretreat']) ? $config['pretreat'] : false;
                // 获取数据库端口，直接拼凑成 dsn 中的信息
                $this->port = isset($config['port']) ? ';port=' . $config['port'] : '';
                // 获取持久连接配置，如果获取不到，默认为真
                $this->pconn = isset($config['pconn']) ? $config['pconn'] : true;
                // 获取配置，并在配置中加入持久连接配置
                $this->opts = (isset($config['opts']) && is_array($config['opts'])) ? array_merge($config['opts'], [\PDO::ATTR_PERSISTENT => $this->pconn]) : [\PDO::ATTR_PERSISTENT => $this->pconn];
                // 获取字符集配置，默认为 UTF8
                $this->charset = isset($config['charset']) ? $config['charset'] : 'utf8';

                break;
            case 'sqlite':
                // 获取数据库地址，如果存在则赋值，不存在返回 false
                if (isset($config['file'])) {
                    $this->file = $config['file'];
                } else {
                    return false;
                }

                // 获取强制磁盘同步配置，默认为 NORMAL
                $this->sync = isset($config['sync']) ? $config['sync'] : 'NORMAL';
                // 获取配置，默认为空数组
                $this->opts = isset($config['opts']) ? $config['opts'] : [];

                break;
            default:
                trigger_error("暂不支持 $this->type 数据库", E_USER_ERROR);
                break;
        }
    }

    /**
     * 连接数据库并实例化 PDO 对象
     * @return Object 实例化后的 PDO 对象
     */
    public function conn()
    {
        // 判断是否已经实例化过
        if ($this->pdo) {
            return $this->pdo;
        }

        // 选择不同的数据库连接，使用 switch 语句方便扩展
        switch ($this->type) {
            case 'mysql':
                // 创建 PDO 对象
                $this->pdo = new \PDO('mysql:dbname=' . $this->name . ';host=' . $this->host . $this->port, $this->user, $this->pwd, $this->opts);
                // 设置默认字符集
                $this->pdo->exec('SET NAMES ' . $this->charset);
                // 设置以报错形式
                $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, $this->errmode);
                // 设置 fetch 时返回数据形式
                $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, $this->fetch);
                // 设置是否启用模拟预处理
                $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, $this->pretreat);

                break;
            case "sqlite":
                // 创建 PDO 对象
                $this->pdo = new \PDO('sqlite:' . $this->file);
                // 设置强制磁盘同步配置
                $this->pdo->exec('PRAGMA synchronous=' . $this->sync);
                break;
            default:
                trigger_error("暂不支持 $this->type 数据库", E_USER_ERROR);
                break;
        }
    }

    /**
     * 执行 SQL 语句，本方法会根据参数选择不同的执行方法
     * @param  String $sql  待执行的 SQL 语句
     * @param  Array  $data 数据数组，默认为空数组
     * @return Object       记录集对象
     */
    public function execsql($sql, $data = [])
    {
        // 如果不存在就尝试连接
        if(!$this->pdo) {
            $this->conn();
        }

        // 执行 SQL 语句，根据有无参数调用不同的方法执行 SQL
        if ($data !== []) {
            // 对 SQL 语句进行预处理
            $res = $this->pdo->prepare($sql);
            // 如果预处理失败则直接返回
            if (!$res) {
                return false;
            }
            // 执行预处理后的语句
            $res->execute($data);
        } else {
            $res->query($sql);
        }

        // 返回结果集
        return $res;
    }

    /**
     * 插入数据
     * @param  String   $table      待插入的表名
     * @param  String   $colName    插入的字段名，多个字段使用 , 隔开
     * @param  array    $data       要插入的值，与字段一一对应
     * @return Object               执行 SQL 后返回的记录集
     */
    public function insert($table, $colName, $data=[])
    {
        $value = "VALUES(?)";
        if (strpos($colName, ',') !== false) {
            $fields = explode(',', $colName);
            $value = "VALUES(";
            $num = count($fields);
            for ($i=0; $i < $num - 1; $i++) {
                $value .= '?,';
            }
            $value .= '?)';
        }
        $sql = "INSERT INTO $table ($colName) $value";

        return $this->execsql($sql, $data);
    }

    /**
     * 删除数据
     * @param  String   $table      需要删除的表名
     * @param  String   $condition  删除条件，可以包含 where 等语句，如：where name=? and pass=?
     * @param  Array    $data       条件中涉及的变量
     * @return Object               执行 SQL 后的记录集对象
     */
    public function delete($table, $condition, $data = [])
    {
        $sql = "DELETE FROM $table $condition";

        return $this->execsql($sql, $data);
    }

    /**
     * 更新数据
     * @param  String   $table      需要更新的表名
     * @param  String   $set        更新的数据，格式为：字段名=?，如果需要同时更新多个字段，使用 , 隔开
     * @param  String   $condition  更新条件可以包含 where 等语句，如：where name=? and pass=?
     * @param  Array    $data       更新的数据和更新条件中涉及的变量
     * @return Object               执行 SQL 后的记录集对象
     */
    public function update($table, $set, $condition, $data = [])
    {
        $sql = "UPDATE $table SET $set $condition";

        return $this->execsql($sql, $data);
    }

    /**
     * 查找数据
     * @param  String   $colName    需要查找的字段名
     * @param  String   $table      需要查找的表
     * @param  String   $condition  查找条件，可以包含 where 等语句，如：where name=? and pass=?
     * @param  array    $data       条件中涉及的变量
     * @return Object               执行 SQL 后的记录集对象
     */
    public function select($colName, $table, $condition = '', $data = [])
    {
        $sql = "SELECT $colName FROM $table $condition";

        return $this->execsql($sql, $data);
    }
}
