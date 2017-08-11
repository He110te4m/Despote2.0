<?php
/**
 * 验证码生成类
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace App\Extend;

class Verify
{
    ////////////////
    // 验证码属性 //
    ////////////////
    // 验证码图片对象
    private $img;
    // 图片验证码宽度
    private $width;
    // 图片验证码高度
    private $height;

    ////////////////
    // 验证码配置 //
    ////////////////
    // 验证码字符串
    private $code;
    // 验证码长度
    private $length;

    /**
     * 构造函数，初始化相关配置
     * @param integer $width  图片宽度
     * @param integer $height 图片高度
     * @param integer $length 验证码长度
     */
    public function __construct($width = 80, $height = 20, $length = 4)
    {
        $this->width  = $width;
        $this->height = $height;
        $this->length = $length;
    }

    /**
     * 获取验证码并输出
     * @param  string $type 验证码类型，可选值为 num、char，分别对应数字验证码、字符验证码等，若留空则默认为字符验证码
     */
    public function getVerifyCode($type = 'char')
    {
        // 创建图片
        $this->createImg();
        // 设置干扰元素
        $this->setDisturb();
        // 取验证码字符串
        $this->createCode($type);
        // 设置验证码
        $this->setVerify();
        // 输出图片
        $this->outputImg();
    }

    /**
     * 校验验证码
     * @param  String $code 用户输入的验证码
     * @return Bool         校验结果
     */
    public function checkVerifyCode($code)
    {
        return $code == $this->code;
    }

    /**
     * 创建图片实例
     */
    private function createImg()
    {
        $this->img = imagecreatetruecolor($this->width, $this->height);
        $bgcolor   = imagecolorallocate($this->img, 0, 0, 0);
        imagefill($this->img, 0, 0, $bgcolor);
    }

    /**
     * 设置干扰元素，包括不超过 250 个干扰点和 5 条弧线
     */
    private function setDisturb()
    {
        // 根据面积取干扰点的数量
        $areaNum = ($this->width * $this->height) / 20;
        // 如果干扰点太多，则取 250 个，避免干扰点太多，用户无法识别
        $disturbNum = ($areaNum > 250) ? 250 : $areaNum;

        // 加入干扰点
        for ($i = 0; $i < $disturbNum; $i++) {
            // 随机取干扰点颜色
            $color = imagecolorallocate($this->img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            // 填充干扰点，大小为一个像素
            imagesetpixel($this->img, mt_rand(1, $this->width - 2), mt_rand(1, $this->height - 2), $color);
        }

        //加入弧线
        for ($i = 0; $i <= 5; $i++) {
            // 随机取干扰线颜色
            $color = imagecolorallocate($this->img, mt_rand(128, 255), mt_rand(125, 255), mt_rand(100, 255));
            // 填充干扰线，线条随机
            imagearc($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(30, 300), mt_rand(20, 200), 50, 30, $color);
        }
    }

    /**
     * 获取验证码字符串
     * @param  string $type 验证码类型，可选值为 num、char，分别对应数字验证码、字符验证码等，若留空则默认为字符验证码
     */
    private function createCode($type = 'char')
    {
        switch ($type) {
            case 'num':
                $this->getNumVerifyCode();
                break;
            default:
            case 'char':
                $this->getCharVerifyCode();
                break;
        }
    }

    /**
     * 生成数字验证码
     */
    private function getNumVerifyCode()
    {
        // 初始化字符串
        $this->code = '';
        // 取随机数字，并将数字转化为字符串
        for ($i = 0; $i < $this->length; $i++) {
            $this->code .= mt_rand(0, 9);
        }
    }

    /**
     * 生成字符验证码
     */
    private function getCharVerifyCode()
    {
        // 验证码字符库
        $str = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKMNPQRSTUVWXYZ';

        // 初始化字符串
        $this->code = '';
        // 取随机下标，将字符串作为只读字符数组
        for ($i = 0; $i < $this->length; $i++) {
            $this->code .= $str[mt_rand(0, strlen($str) - 1)];
        }
    }

    /**
     * 填充验证字符
     */
    private function setVerify()
    {
        for ($i = 0; $i < $this->length; $i++) {
            // 随机取字符颜色
            $color = imagecolorallocate($this->img, mt_rand(50, 250), mt_rand(100, 250), mt_rand(128, 250));
            // 随机取字符大小
            $size = mt_rand(floor($this->height / 5), floor($this->height / 3));
            // 取字符 X 坐标
            $x = floor($this->width / $this->length) * $i + 5;
            // 取字符 Y 坐标
            $y = mt_rand(0, $this->height - 20);
            // 在图片中填充字符
            imagechar($this->img, $size, $x, $y, $this->code{$i}, $color);
        }
    }

    /**
     * 输出图片，根据 PHP 支持的图片类型选择输出不同的图片
     */
    private function outputImg()
    {
        if (imagetypes() & IMG_JPG) {
            // 如果 PHP 支持 JPG 则输出 JPG 图片
            header('Content-type:image/jpeg');
            imagejpeg($this->img);
        } elseif (imagetypes() & IMG_GIF) {
            // 如果 PHP 支持 GIF 则输出 GIF 图片
            header('Content-type: image/gif');
            imagegif($this->img);
        } elseif (imagetypes() & IMG_PNG) {
            // 如果 PHP 支持 PNG 则输出 PNG 图片
            header('Content-type: image/png');
            imagepng($this->img);
        } else {
            die("Don't support image type!");
        }
    }
}
