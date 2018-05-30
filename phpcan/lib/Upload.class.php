<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/5/8 17:21
 * 官网：http://www.phpcan.cn
 * ===============================================
 * UPLOAD类库
 * ===============================================
 */

namespace phpcan\lib;

class Upload{

    // 图片
    private $_img;
    // 原图信息
    private $_imgInfo;
    // 原图宽度
    private $_imgWitdh;
    // 原图高度
    private $_imgHeight;
    // 图片容器
    private $_imgBox;

    /**
     * 描述：将图片保存到本地
     * @param string $base64
     * @param string $path
     *
     */
    public function local(string $base64 = '', string $path = '')
    {
        if (preg_match('#^data:\s*image\/(\w+);base64,#U', $base64,$data))
        {
            // 判断文件格式
            if ( ! in_array($data[1], conf('UPLOAD')))
            {
                error(1026);
            }
            // 获取文件大小
            $content  = preg_replace('#^data:\s*image\/(\w+);base64,#U', '', $base64);
            $content  = str_replace('=', '', $content);
            $len      = strlen($content);
            $fileSize = floor(($len - ($len / 8) * 2) / 1024);
            // 判断文件大小
            if ($fileSize > conf('UPLOAD_SIZE'))
            {
                error(1027, [
                    'size' => conf('UPLOAD_SIZE')
                ]);
            }
            $doc = ($data[1] == 'jpeg') ? 'jpg' : $data[1];
            $doc = strtolower($doc);
            if ( ! is_dir($path))
            {
                mkdir($path, 0777);
            }
            $path = rtrim($path, '/');
            $name = str_replace('.','', str_replace(' ', '', microtime()));
            $localFile = $path.'/'.$name.'.'.$doc;
            // 保存文件
            file_put_contents($localFile, base64_decode($content));
            return $localFile;
        }
        else
        {
            error(1025);
        }
    }

    /**
     * 描述：水印
     * @param $waterPic
     * @param $localImg
     * @param $position
     */
    public function water(string $waterImg = '', $img = FALSE, int $position = 0)
    {
        if ($waterImg == '')
        {
            error(1024);
        }
        $img = ( ! $img) ? $this->_img : $img;
        $box = $this->_createImgBox($img);
        // 获取水印图片的相关信息
        $imgInfo = getimagesize($waterImg);
        // 水印图片宽度
        $width   = $imgInfo[0];
        // 水印图片高度
        $height  = $imgInfo[1];
        // 如果图片尺寸小于水印图片则不加水印
        if ($this->_imgWitdh < $width || $this->_imgHeight < $height)
        {
            return $this;
        }
        switch ($imgInfo[2])
        {
            case 1:
                $waterBox = imagecreatefromgif($waterImg);
                break;
            case 2:
                $waterBox = imagecreatefromjpeg($waterImg);
                break;
            case 3:
                $waterBox = imagecreatefrompng($waterImg);
                break;
        }
        // 设置水印图片位置
        switch ($position)
        {
            case 0:
                $dstX = ($this->_imgWitdh - $width) / 2;
                $dstY = ($this->_imgHeight - $height) / 2;
                break;
            case 1:
                $dstX = 30;
                $dstY = 30;
                break;
            case 2:
                $dstX = $this->_imgWitdh - $width - 30;
                $dstY = 30;
                break;
            case 3:
                $dstX = 30;
                $dstY = $this->_imgHeight - $height - 30;
                break;
            case 4:
                $dstX = $this->_imgWitdh - $width - 30;
                $dstY = $this->_imgHeight - $height - 30;
                break;
        }
        imagecopy($box,
            $waterBox,
            $dstX,
            $dstY,
            0,
            0,
            $width,
            $height);
        // 生成图片
        $this->_createImg($box, $img);
        return $this;
    }

    /**
     * 描述：执行FTP上传
     * @param array $conf
     * @param $img
     */
    public function ftp(array $conf = [], $img = FALSE, $localImg = FALSE)
    {
        if ( ! isset($conf['HOST']))
        {
            error(1018);
        }
        if ( ! isset($conf['PORT']))
        {
            error(1019);
        }
        if ( ! isset($conf['UNAME']))
        {
            error(1020);
        }
        if ( ! isset($conf['PWORD']))
        {
            error(1021);
        }
        $img = ( ! $img) ? $this->_img : $img;
        $Ftp = ftp_connect($conf['HOST'], $conf['PORT']);
        if ( ! $Ftp)
        {
            error(1022);
        }
        if ( ! ftp_login($Ftp, $conf['UNAME'], $conf['PWORD']))
        {
            error(1023);
        }
        $dir = '';
        if ($conf['DIR'] != '')
        {
            $dirs = explode('/', $conf['DIR']);
            foreach ($dirs as $d)
            {
                $dir .= '/'.$d;
                if ( ! @ftp_chdir($Ftp, $dir)) ftp_mkdir($Ftp, $dir);
            }
        }
        $img = $dir.'/'.$img;
        ftp_pasv($Ftp,TRUE);
        return ftp_put($Ftp, $img, $localImg,FTP_BINARY);
    }

    /**
     * 描述：创建图片容器
     * @param $img
     * @param int $w
     * @param int $h
     * @return mixed
     */
    private function _createImgBox(string $img, int $width = 0, int $height = 0)
    {
        // 获取原图信息
        $this->_imgInfo   = getimagesize($img);
        // 图片宽度
        $this->_imgWitdh  = $this->_imgInfo[0];
        // 图片高度
        $this->_imgHeight = $this->_imgInfo[1];
        // 创建图片容器
        switch ($this->_imgInfo[2])
        {
            case 1:
                $this->_imgBox = imagecreatefromgif($img);
                break;
            case 2:
                $this->_imgBox = imagecreatefromjpeg($img);
                break;
            case 3:
                $this->_imgBox = imagecreatefrompng($img);
                break;
        }
        // 如果宽度和高度没有传说明水印图片，使用原图的高度宽度
        if ($width == 0 && $height == 0)
        {
            $box = $this->_waterImgBox($img);
        }
        else
        {
            //$box = $this->_thumb_img_box($w, $h);
        }
        return $box;
    }

    /**
     * 描述：生成水印图片容器
     * @return resource
     */
    private function _waterImgBox()
    {
        $box = imagecreatetruecolor($this->_imgWitdh, $this->_imgHeight);
        // 图片信息写入容器
        imagecopyresampled($box,
            $this->_imgBox,
            0,
            0,
            0,
            0,
            $this->_imgWitdh,
            $this->_imgHeight,
            $this->_imgWitdh,
            $this->_imgHeight);
        return $box;
    }

    /**
     * 描述：生成最终图片
     * @param $img
     * @param $path
     */
    private function _createImg($box, string $img)
    {
        // 生成图片
        switch ($this->_imgInfo[2])
        {
            case 1:
                imagegif($box, $img);
                break;
            case 2:
                imagejpeg($box, $img);
                break;
            case 3:
                imagepng($box, $img);
                break;
        }
        // 销毁图片容器
        imagedestroy($box);
    }

}