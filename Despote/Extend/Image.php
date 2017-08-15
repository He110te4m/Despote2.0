<?php
/**
 * 图片处理类
 *
 * 调用方法：
 * 1、实例化类
 * 2、使用 thumb('待处理的文件名', 宽, 高, '新文件前缀，为空字符串时覆盖源文件') 方法缩放图片
 * 3、使用 watermark('待处理的文件名', '水印图片', 水印位置, '新文件前缀，为空字符串时覆盖源文件') 方法添加水印
 * 4、使用 cut('待处理的文件名', 起点 X 坐标, 起点 y 坐标, 裁剪后的宽度, 裁剪后的高度, '新文件前缀，为空字符串时覆盖源文件') 方法裁剪图片
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */

class Image
{
    // 图片保存的路径
    private $path;

    /**
     * 实例图像对象时传递图像的一个路径，默认值是当前目录
     * @param    string    $path    可以指定处理图片的路径
     */
    public function __construct($path = "./")
    {
        $this->path = rtrim($path, "/") . "/";
    }

    /**
     * 对指定的图像进行缩放
     * @param    string    $name    是需要处理的图片名称
     * @param    int    $width        缩放后的宽度
     * @param    int    $height        缩放后的高度
     * @param    string    $qz        是新图片的前缀
     * @return    mixed            是缩放后的图片名称,失败返回false;
     */
    public function thumb($name, $width, $height, $qz = "despote_")
    {
         // 获取图片宽度、高度、及类型信息
        $imgInfo = $this->getInfo($name);
         // 获取背景图片的资源
        $srcImg = $this->getImg($name, $imgInfo);
         // 获取新图片尺寸
        $size = $this->getNewSize($name, $width, $height, $imgInfo);
         // 获取新的图片资源
        $newImg = $this->kidOfImage($srcImg, $size, $imgInfo);
         // 通过本类的私有方法，保存缩略图并返回新缩略图的名称，以"th_"为前缀
        return $this->createNewImage($newImg, $qz . $name, $imgInfo);
    }

    /**
     * 为图片添加水印
     * @param    string    $groundName    背景图片，即需要加水印的图片，暂只支持GIF,JPG,PNG格式
     * @param    string    $waterName    图片水印，即作为水印的图片，暂只支持GIF,JPG,PNG格式
     * @param    int    $waterPos        水印位置，有10种状态，0为随机位置；
     *                                 1为顶端居左，2为顶端居中，3为顶端居右；
     *                                 4为中部居左，5为中部居中，6为中部居右；
     *                                7为底端居左，8为底端居中，9为底端居右；
     * @param    string    $qz            加水印后的图片的文件名在原文件名前面加上这个前缀
     * @return    mixed                是生成水印后的图片名称,失败返回false
     */
    public function waterMark($groundName, $waterName, $waterPos = 0, $qz = "despote_")
    {
        // 获取水印图片是当前路径，还是指定了路径
        $curpath = rtrim($this->path, "/") . "/";
        $dir     = dirname($waterName);
        if ($dir == ".") {
            $wpath = $curpath;
        } else {
            $wpath     = $dir . "/";
            $waterName = basename($waterName);
        }

        // 水印图片和背景图片必须都要存在
        if (file_exists($curpath . $groundName) && file_exists($wpath . $waterName)) {
            // 获取背景信息
            $groundInfo = $this->getInfo($groundName);
            // 获取水印图片信息
            $waterInfo  = $this->getInfo($waterName, $dir);
            // 如果背景比水印图片还小，就会被水印全部盖住
            if (!$pos = $this->position($groundInfo, $waterInfo, $waterPos)) {
                echo '水印不应该比背景图片小！';
                return false;
            }

            // 获取背景图像资源
            $groundImg = $this->getImg($groundName, $groundInfo);
            // 获取水印图片资源
            $waterImg  = $this->getImg($waterName, $waterInfo, $dir);

             // 调用私有方法将水印图像按指定位置复制到背景图片中
            $groundImg = $this->copyImage($groundImg, $waterImg, $pos, $waterInfo);
             // 通过本类的私有方法，保存加水图片并返回新图片的名称，默认以 "despote_" 为前缀
            return $this->createNewImage($groundImg, $qz . $groundName, $groundInfo);

        } else {
            echo '图片或水印图片不存在！';
            return false;
        }
    }

    /**
     * 在一个大的背景图片中剪裁出指定区域的图片
     * @param    string    $name    需要剪切的背景图片
     * @param    int    $x            剪切图片左边开始的位置
     * @param    int    $y            剪切图片顶部开始的位置
     * @param    int    $width        图片剪裁的宽度
     * @param    int    $height        图片剪裁的高度
     * @param    string    $qz        新图片的名称前缀
     * @return    mixed            裁剪后的图片名称,失败返回false;
     */
    public function cut($name, $x, $y, $width, $height, $qz = "despote_")
    {
        // 获取图片信息
        $imgInfo = $this->getInfo($name);
         // 裁剪的位置不能超出背景图片范围
        if ((($x + $width) > $imgInfo['width']) || (($y + $height) > $imgInfo['height'])) {
            echo "裁剪的位置超出了背景图片范围!";
            return false;
        }

        // 获取图片资源
        $back = $this->getImg($name, $imgInfo);
         // 创建一个可以保存裁剪后图片的资源
        $cutimg = imagecreatetruecolor($width, $height);
         // 使用 imagecopyresampled() 函数对图片进行裁剪
        imagecopyresampled($cutimg, $back, 0, 0, $x, $y, $width, $height, $width, $height);
        imagedestroy($back);
         // 通过本类的私有方法，保存剪切图并返回新图片的名称，默认以 "despote_" 为前缀
        return $this->createNewImage($cutimg, $qz . $name, $imgInfo);
    }

     /**
      * 用来确定水印图片的位置
      * @param  Array  $groundInfo 图片信息数组
      * @param  Array  $waterInfo  水印信息
      * @param  int    $waterPos   水印位置
      * @return Array              水印坐标信息
      */
    private function position($groundInfo, $waterInfo, $waterPos)
    {
         // 需要加水印的图片的长度或宽度比水印还小，无法生成水印
        if (($groundInfo["width"] < $waterInfo["width"]) || ($groundInfo["height"] < $waterInfo["height"])) {
            return false;
        }
        switch ($waterPos) {
            // 1 为顶端居左
            case 1:
                $posX = 0;
                $posY = 0;
                break;
            // 2 为顶端居中
            case 2:
                $posX = ($groundInfo["width"] - $waterInfo["width"]) / 2;
                $posY = 0;
                break;
            // 3 为顶端居右
            case 3:
                $posX = $groundInfo["width"] - $waterInfo["width"];
                $posY = 0;
                break;
            // 4 为中部居左
            case 4:
                $posX = 0;
                $posY = ($groundInfo["height"] - $waterInfo["height"]) / 2;
                break;
            // 5 为中部居中
            case 5:
                $posX = ($groundInfo["width"] - $waterInfo["width"]) / 2;
                $posY = ($groundInfo["height"] - $waterInfo["height"]) / 2;
                break;
            // 6 为中部居右
            case 6:
                $posX = $groundInfo["width"] - $waterInfo["width"];
                $posY = ($groundInfo["height"] - $waterInfo["height"]) / 2;
                break;
            // 7 为底端居左
            case 7:
                $posX = 0;
                $posY = $groundInfo["height"] - $waterInfo["height"];
                break;
            // 8 为底端居中
            case 8:
                $posX = ($groundInfo["width"] - $waterInfo["width"]) / 2;
                $posY = $groundInfo["height"] - $waterInfo["height"];
                break;
            // 9 为底端居右
            case 9:
                $posX = $groundInfo["width"] - $waterInfo["width"];
                $posY = $groundInfo["height"] - $waterInfo["height"];
                break;
            // 随机
            case 0:
            default:
                $posX = rand(0, ($groundInfo["width"] - $waterInfo["width"]));
                $posY = rand(0, ($groundInfo["height"] - $waterInfo["height"]));
                break;
        }
        return ["posX" => $posX, "posY" => $posY];
    }

     /**
      * 获取图片的属性信息（宽度、高度和类型）
      * @param  String $name 文件名
      * @param  string $path 生成路径
      * @return Array        图片信息
      */
    private function getInfo($name, $path = ".")
    {
        $spath = $path == "." ? rtrim($this->path, "/") . "/" : $path . '/';

        $data              = getimagesize($spath . $name);
        $imgInfo["width"]  = $data[0];
        $imgInfo["height"] = $data[1];
        $imgInfo["type"]   = $data[2];

        return $imgInfo;
    }

    /**
     * 创建支持各种图片格式（jpg,gif,png三种）资源
     * @param  String $name    文件名
     * @param  Array  $imgInfo 图片信息
     * @param  string $path    生成路径
     * @return Object          图片对象
     */
    private function getImg($name, $imgInfo, $path = '.')
    {

        $spath  = $path == "." ? rtrim($this->path, "/") . "/" : $path . '/';
        $srcPic = $spath . $name;

        switch ($imgInfo["type"]) {
            // gif
            case 1:
                $img = imagecreatefromgif($srcPic);
                break;
            // jpg
            case 2:
                $img = imagecreatefromjpeg($srcPic);
                break;
            // png
            case 3:
                $img = imagecreatefrompng($srcPic);
                break;
            default:
                return false;
                break;
        }
        return $img;
    }

     /**
      * 返回等比例缩放的图片宽度和高度，如果原图比缩放后的还小保持不变
      * @param  String $name    文件名
      * @param  int    $width   新图片宽度
      * @param  int    $height  新图片高度
      * @param  Array  $imgInfo 原图片信息
      * @return Array           新图片信息
      */
    private function getNewSize($name, $width, $height, $imgInfo)
    {
        // 原图片的宽度
        $size["width"]  = $imgInfo["width"];
        // 原图片的高度
        $size["height"] = $imgInfo["height"];

        if ($width < $imgInfo["width"]) {
            // 缩放的宽度如果比原图小才重新设置宽度
            $size["width"] = $width;
        }

        if ($height < $imgInfo["height"]) {
            // 缩放的高度如果比原图小才重新设置高度
            $size["height"] = $height;
        }
         // 等比例缩放的算法
        if ($imgInfo["width"] * $size["width"] > $imgInfo["height"] * $size["height"]) {
            $size["height"] = round($imgInfo["height"] * $size["width"] / $imgInfo["width"]);
        } else {
            $size["width"] = round($imgInfo["width"] * $size["height"] / $imgInfo["height"]);
        }

        return $size;
    }

     /**
      * 保存图像，并保留原有图片格式
      * @param  Object $newImg  新图片对象
      * @param  String $newName 新图片文件名
      * @param  Array  $imgInfo 新图片信息
      * @return String          返回新图片文件名，用于连续操作
      */
    private function createNewImage($newImg, $newName, $imgInfo)
    {
        $this->path = rtrim($this->path, "/") . "/";
        switch ($imgInfo["type"]) {
            // gif
            case 1:
                $result = imageGIF($newImg, $this->path . $newName);
                break;
            // jpg
            case 2:
                $result = imageJPEG($newImg, $this->path . $newName);
                break;
            // png
            case 3:
                $result = imagePng($newImg, $this->path . $newName);
                break;
        }
        imagedestroy($newImg);
        return $newName;
    }

     /**
      * 加水印时复制图像
      * @param  Object $groundImg 图片对象
      * @param  Object $waterImg  水印对象
      * @param  Array  $pos       水印坐标
      * @param  Array  $waterInfo 水印信息
      * @return Object            加水印后的图片对象
      */
    private function copyImage($groundImg, $waterImg, $pos, $waterInfo)
    {
        imagecopy($groundImg, $waterImg, $pos["posX"], $pos["posY"], 0, 0, $waterInfo["width"], $waterInfo["height"]);
        imagedestroy($waterImg);
        return $groundImg;
    }

     /**
      * 处理带有透明度的图片保持原样
      * @param  Object $srcImg  图片对象
      * @param  Array  $size    新图片尺寸
      * @param  Array  $imgInfo 图片信息
      * @return Object          新图片对象
      */
    private function kidOfImage($srcImg, $size, $imgInfo)
    {
        $newImg = imagecreatetruecolor($size["width"], $size["height"]);
        $otsc   = imagecolortransparent($srcImg);
        if ($otsc >= 0 && $otsc < imagecolorstotal($srcImg)) {
            $transparentcolor    = imagecolorsforindex($srcImg, $otsc);
            $newtransparentcolor = imagecolorallocate(
                $newImg,
                $transparentcolor['red'],
                $transparentcolor['green'],
                $transparentcolor['blue']
            );
            imagefill($newImg, 0, 0, $newtransparentcolor);
            imagecolortransparent($newImg, $newtransparentcolor);
        }
        imagecopyresized($newImg, $srcImg, 0, 0, 0, 0, $size["width"], $size["height"], $imgInfo["width"], $imgInfo["height"]);
        imagedestroy($srcImg);
        return $newImg;
    }
}
