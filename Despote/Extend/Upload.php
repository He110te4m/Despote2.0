<?php

namespace Despote\Extend;

class Upload
{
    ///////////////////
    // 上传文件属性设置 //
    ///////////////////

    // 上传文件保存的路径
    private $path = "./uploads/";
    // 设置限制上传文件的类型
    private $allowType = ['jpg', 'gif', 'png'];
    // 限制文件上传大小（字节），默认为 10M
    private $maxSize = 10485760;
    // 设置是否随机重命名文件， false 不随机
    private $isRandonName = true;

    ////////////////
    // 上传文件属性 //
    ////////////////

    // 源文件名
    private $originName;
    // 临时文件名
    private $tmpFileName;
    // 文件类型(文件后缀)
    private $fileType;
    // 文件大小
    private $fileSize;
    // 新文件名
    private $newFileName;
    // 新文件全路径
    private $newFileFullPath;
    // 错误号
    private $errNum = 0;
    // 错误报告消息
    private $errMsg = "";

    public function __construct($conf = [])
    {
        isset($conf['path']) && $this->path                 = $conf['path'];
        isset($conf['maxSize']) && $this->maxSize           = $conf['maxSize'];
        isset($conf['allowType']) && $this->allowType       = $conf['allowType'];
        isset($conf['isRandonName']) && $this->isRandonName = $conf['isRandonName'];
    }

    /**
     * 获取上传后的文件名称
     * @return string 上传后新文件的名称，如果是多文件上传返回数组
     */
    public function getFileName()
    {
        return $this->newFileName;
    }

    /**
     * 获取上传前的文件名称
     * @return string 上传前的文件名称，如果是多文件上传返回数组
     */
    public function getOriginName()
    {
        return $this->originName;
    }

    /**
     * 获取上传后的文件全路径
     * @return string 上传后，新文件的全路径， 如果是多文件上传返回数组
     */
    public function getFileFullPath()
    {
        if (count($this->newFileFullPath) == 1) {
            return $this->newFileFullPath[0];
        } else {
            return $this->newFileFullPath;
        }
    }

    /**
     * 上传失败后，调用该方法则返回，上传出错信息
     * @return string  返回上传文件出错的信息报告，如果是多文件上传返回数组
     */
    public function getErrMsg()
    {
        return $this->errMsg;
    }

    /**
     * 调用该方法上传文件
     * @param  $field  string   上传文件的表单名称
     * @return bool             如果上传成功返回数true
     */
    public function upload($field)
    {
        // 标记上传过程是否出错
        $flag = true;

        // 校验路径是否合法
        if (!$this->checkFilePath()) {
            $this->errMsg = $this->getError();
            return false;
        }

        // 获取文件信息并保存
        $name     = $_FILES[$field]['name'];
        $tmp_name = $_FILES[$field]['tmp_name'];
        $size     = $_FILES[$field]['size'];
        $error    = $_FILES[$field]['error'];

        // 判断上传的文件数量
        if (is_array($name)) {
            $errors = [];
            // 检查每个上传的文件的有效性
            for ($i = 0; $i < count($name); $i++) {
                // 设置文件信息
                if ($this->setFiles($name[$i], $tmp_name[$i], $size[$i], $error[$i])) {
                    // 文件安全性校验，验证文件大小和文件类型是否合法
                    if (!$this->checkFileSize() || !$this->checkFileType()) {
                        $errors[] = $this->getError();
                        $flag     = false;
                    }
                } else {
                    $errors[] = $this->getError();
                    $flag     = false;
                }
                // 如果上传的文件遇到问题则使用默认值进行文件处理
                if (!$flag) {
                    $this->setFiles();
                }

            }

            // 如果遇到错误就没有必要保存文件了，直接返回错误，结束函数
            if ($flag) {
                // 上传的文件数组
                $fileNames = [];
                // 循环保存有效文件
                for ($i = 0; $i < count($name); $i++) {
                    if ($this->setFiles($name[$i], $tmp_name[$i], $size[$i], $error[$i])) {
                        // 设置新文件名
                        $this->setNewFileName();
                        // 保存文件到服务器上
                        if (!$this->copyFile()) {
                            $flag     = false;
                            $errors[] = $this->getError();
                        }
                        $fileNames[] = $this->newFileName;
                    }
                }
                $this->newFileName = $fileNames;
            }
            $this->errMsg = $errors;
        } else {
            // 设置文件信息
            if ($this->setFiles($name, $tmp_name, $size, $error)) {
                // 文件安全性校验，验证文件大小和文件类型是否合法
                if ($this->checkFileSize() && $this->checkFileType()) {
                    // 设置新文件名
                    $this->setNewFileName();
                    // 保存文件到服务器上
                    if ($this->copyFile()) {
                        return true;
                    }
                }
            }
            $flag         = false;
            $this->errMsg = $this->getError();
        }

        return $flag;
    }

    /**
     * 设置出错信息
     * @return String 错误信息
     */
    private function getError()
    {
        $str = "上传文件<font color='red'>{$this->originName}</font>时出错 : ";
        switch ($this->errNum) {
            case 4:
                $str .= "没有文件被上传";
                break;
            case 3:
                $str .= "文件只有部分被上传";
                break;
            case 2:
                $str .= "上传文件的大小超过了HTML表单中MAX_FILE_SIZE选项指定的值";
                break;
            case 1:
                $str .= "上传的文件超过了php.ini中upload_max_filesize选项限制的值";
                break;
            case -1:
                $str .= "未允许类型";
                break;
            case -2:
                $str .= "文件过大,上传的文件不能超过{$this->maxSize}个字节";
                break;
            case -3:
                $str .= "上传失败";
                break;
            case -4:
                $str .= "建立存放上传文件目录失败，请重新指定上传目录";
                break;
            case -5:
                $str .= "必须指定上传文件的路径";
                break;
            default:
                $str .= "未知错误";
        }
        return $str . '<br>';
    }

    /**
     * 设置和$_FILES有关的内容
     * @param string  $name     上传的文件名
     * @param string  $tmp_name 临时文件名
     * @param integer $size     文件大小
     * @param integer $error    错误码
     */
    private function setFiles($name = "", $tmp_name = "", $size = 0, $error = 0)
    {
        $this->setOption('errNum', $error);
        if ($error) {
            return false;
        }

        $this->setOption('originName', $name);
        $this->setOption('tmpFileName', $tmp_name);
        $aryStr = explode(".", $name);
        $this->setOption('fileType', strtolower($aryStr[count($aryStr) - 1]));
        $this->setOption('fileSize', $size);
        return true;
    }

    /* 为单个成员属性设置值 */
    private function setOption($key, $val)
    {
        $this->$key = $val;
    }

    /* 设置上传后的文件名称 */
    private function setNewFileName()
    {
        if ($this->isRandonName) {
            $this->setOption('newFileName', $this->proRandName());
        } else {
            $this->setOption('newFileName', $this->originName);
        }
    }

    /* 检查上传的文件是否是合法的类型 */
    private function checkFileType()
    {
        if (in_array(strtolower($this->fileType), $this->allowType)) {
            return true;
        } else {
            $this->setOption('errNum', -1);
            return false;
        }
    }

    /* 检查上传的文件是否是允许的大小 */
    private function checkFileSize()
    {
        if ($this->fileSize > $this->maxSize) {
            $this->setOption('errNum', -2);
            return false;
        } else {
            return true;
        }
    }

    /* 检查是否有存放上传文件的目录 */
    private function checkFilePath()
    {
        if (empty($this->path)) {
            $this->setOption('errNum', -5);
            return false;
        }
        if (!file_exists($this->path) || !is_writable($this->path)) {
            if (!@mkdir($this->path, 0755)) {
                $this->setOption('errNum', -4);
                return false;
            }
        }
        return true;
    }

    /* 复制上传文件到指定的位置 */
    private function copyFile()
    {
        if (!$this->errNum) {
            $path = rtrim($this->path, '/') . '/';
            $path .= $this->newFileName;
            createDir(dirname($path));
            if (move_uploaded_file($this->tmpFileName, $path)) {
                $this->newFileFullPath[] = $path;
                return true;
            } else {
                $this->setOption('errNum', -3);
                return false;
            }
        } else {
            return false;
        }
    }

    /* 设置随机文件名 */
    private function proRandName()
    {
        $fileName = date('YmdHis') . "_" . rand(100, 999);
        $full     = $fileName . '.' . $this->fileType;
        if (is_file($full)) {
            return $this->proRandName();
        } else {
            return $full;
        }
    }
}
