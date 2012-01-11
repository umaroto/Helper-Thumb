<?php
//Imagem default para caso de nao encontrar a imagem solicitada. Deve estar em img/
define("IMGDEFAULT", "img/default.jpg");

class ThumbHelper extends AppHelper {
    
    private function _cacheDir() //Diretorio cache
    {
        $cacheDir = 'img' . DS . 'cache' . DS;
        return $cacheDir;
    }
    
    private function _imgCache($filename, $width, $height, $dirname = null)
    {
        $imgDir = $this->_cacheDir() . $dirname . $width . 'x' . $height . '_' . $filename . '.jpg';
        return $imgDir;
    }
    
    function resize($path, $width = 800, $height = 600)
    {
        $cachedir = WWW_ROOT . $this->_cacheDir();        
        if(!is_dir($cachedir))
        {
            mkdir($cachedir, 0777);
        }
        
        $dir = dirname($path);
        if(!is_dir($cachedir . DS . $dir))
        {
            $folders = explode("/", $dir);
            foreach($folders as $folder)
            {
                $cachedir .= $folder . DS;
                if(!is_dir($cachedir))
                {
                    mkdir($cachedir, 0777);
                }
            }
        }
        
        if(!file_exists($path))
        {
            $cachefile = $this->_handleImage(IMGDEFAULT, $width, $height);
        }
        else
        {
            $cachefile = $this->_writeCache($path, $width, $height);
            
            if($cachefile == false)
            {
                $mime = mime_content_type($path);
                switch($mime)
                {
                    case 'image/jpeg':
                    case 'image/gif':
                    case 'image/png':
                    case 'image/bmp':
                        $cachefile = $this->_handleImage($path, $width, $height, dirname($path));
                        break;
                    default:
                        $cachefile = $this->_handleImage(IMGDEFAULT, $width, $height);
                }
            }
        }
        
        return Router::url("/") . $cachefile;
    }
    
    private function _writeCache($path, $width, $height)
    {
        $filename = $this->_imageName($path);
        $cachefile = $this->_imgCache($filename, $width, $height);
        
        if(is_file($cachefile))
        {
            if(time() - filemtime($cachefile) > 60 * 60) // If older than 1 hour
            {
                unlink($cachefile);
                return false;
            }
            else
            {
                return $cachefile;
            }
        }
        else
        {
            return false;
        }
    }
    
    private function _imageName($path)
    {
        return substr(basename($path), 0, strrpos(basename($path), '.'));
    }
    
    private function _n_abs($num) {
        return ($num > 0) ? $num * -1 : $num;
    }
    
    private function _handleImage($fullpath, $width, $height, $dirname = null)
    {
        $filename = $this->_imageName($fullpath);
        $dirname = strlen($dirname) > 1 ? $dirname .= DS : "";
        $cachefile = $this->_imgCache($filename, $width, $height, $dirname);

        $imageSize = getimagesize($fullpath);
        $process = imagecreatetruecolor($width, $height);

        $original_width = $imageSize[0];
        $original_height = $imageSize[1];
        $original_mime = $imageSize['mime'];

        switch ($original_mime)
        {
            case 'image/jpeg':
                $original_image = imagecreatefromjpeg($fullpath);
                break;
            case 'image/png':
                $original_image = imagecreatefrompng($fullpath);
                break;
            case 'image/gif':
                $original_image = imagecreatefromgif($fullpath);
                break;
        }
        
        $new_width = $original_width / $width;
        $new_height = $original_height / $height;
        
        if ($new_width > $new_height) {
            $img_x = ($height/$original_height) * $original_width;
            $img_y = $height;
            $diff = $this->_n_abs(($img_x - $width) / 2);
            imagecopyresampled($process, $original_image, $diff, 0, 0, 0, $img_x, $img_y, $original_width, $original_height);
        } else {
            $img_y = ($width/$original_width) * $original_height;
            $img_x = $width;
            $diff = $this->_n_abs(($img_y - $height) / 2);
            imagecopyresampled($process, $original_image, 0, $diff, 0, 0, $img_x, $img_y, $original_width, $original_height);
        }
        
        imagejpeg($process, $cachefile, 95);
        imagedestroy($process);

        return $cachefile;
    }
}
?>