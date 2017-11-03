<?php
/**
 * 默认控制器，用于测试路由解析 和 控制器和视图的交互
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace App\Controller;

class Index extends \Despote\Base\Controller
{
    // 默认动作，用于测试路由解析
    public function index()
    {
        $setting = [
            //左定位符
            'tplbegin'    => '{(',

            //右定位符
            'tplend'      => ')}',

            //模版文件后缀
            'suffix'      => 'html',

            //模版字符编码
            'charset'     => 'utf-8',

            //模版文件存放目录,请注意要有读写的权限
            'tpldir'      => 'D:/wwwroot/eztpl-master/templates/',

            //编译文件存放目录,请注意要有读写的权限
            'cachedir'    => 'D:/wwwroot/eztpl-master/templates_c/',

            // //模板不存在,需要跳转的地址，默认会提示一句警告,相对路径，绝对路径皆可
            // 'errorurl' => '',

            // //模版目录下的子目录名
            // 'tempdirname' => '',

            //强制编译页面,调试时使用,每次执行,模版文件将会强制编译,true为开启，false为关闭，默认关闭,此项不可随意开启,太消耗资源
            'tempopen'    => false,

            //定义模版引用方式,为true则采用实时编译，当模板文件改变，引用此模板文件的文件将进行重新编译
            //缺点，效率有所下降，好处是不必一一修改包含此模板文件的模板文件，使其重新编译
            //false为关闭,默认开启,如果确定模版不需要在改变,可以删除所有编译文件后,关闭此项,可加快程序的运行速度
            'includeopen' => true,
        ];

        try {
            $obj = \Despote\Base\Tpl::getInstance();
            $obj->config($setting);

            $preg = [
                '#\[\@(.*)\]#isuU'                      => '{(if isset(@\\1))}{(@\\1)}{(/if)}',

                '#\[\$(.*)\]#isuU'                      => '{(if isset($\\1))}{($\\1)}{(/if)}',

                '#\[inc\((.*)\)\]#isuU'                 => '{(include file="\\1")}',

                '#\[md5\((.*)\)\]#isuU'                 => function ($m) {
                    return md5($m[1]);
                },

                '#\[list="(.*)"\]([\w\W]+?)\[\/list\]#' => '{(if isset(\\1))}{(foreach \\1)}\\2{(/foreach)}{(/if)}',

                '#\[time\]#'                            => '{(date("Y-m-d H:i"))}',
            ];

            //匹配正则绑定
            $obj->bind($preg);

            //如果想要中途改变一项设置可以使用魔术方法set类变量名来进行设置
            $obj->settempopen(true); //更改为强制编译

            $world = 'hello world';

            //传值，可以在模板中使用,hello代表在模板中的变量名称，为了更好的区分变量，所有的外部转入的变量都用@符号来获取
            $obj->assign('hello', $world);

            //编译模板,生成缓存
            $obj->display('index');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
