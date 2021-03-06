<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class pam_vcode{

    var $use_gd = false;
    
    function init($m = 8){
        if(false && function_exists('imagecreatefrompng')){
            $this->use_gd = true;
            $codeDir= DATA_DIR.'/vcode';

            if ($handle = opendir($codeDir)) {
                    while (false !== ($file = readdir($handle))) {
                            if (substr($file,-4)=='.png') {
                                $lib[] = substr($file,0,-4);
                            }
                    }
                    closedir($handle);
            }
            $n = count($lib)-1;
            $str = '';
            for($i=0;$i<$m;$i++){
                $str.=$c = $lib[rand(0,$n)];
                $ret[] = $codeDir.'/'.$c.'.png';
            }
            $this->ret = &$ret;
        }else{
            $this->softGif = new pam_softvcode;
            $str = $this->softGif->init();
        }

        return $str;
    }

    function output(){
        if($this->use_gd){
             $this->gd_merge();
        }else{
            $this->softGif->output();
        }
    }

    function gd_merge(){
        $arr = $this->ret;
        $bg = DATA_DIR.'/vcodebg.png';
        $image = imagecreatefrompng($bg); 
        list($w, $baseH) = getimagesize($bg);

        header('Content-type: image/png');
        $x = 1;

        foreach($arr as $i=>$filename){
            list($w, $h) = getimagesize($filename);
            $source = imagecreatefrompng($filename);
            $t_id = imagecolortransparent($source);
            $rotate = imagerotate($source, rand(-20,20),$t_id);
            $w2 = $w*$baseH/$h;
            imagecopyresized($image, $rotate, $x, 0, 0, 0, $w2, $baseH, $w, $h);
            imagedestroy($source);
            imagedestroy($rotate);
            $x+=$w2;
        }
        $x+=1;

        $dst = imagecreatetruecolor($x, $baseH);
        imagecopyresampled($dst, $image, 0, 0, 0, 0, $x, $baseH, $x, $baseH);
        imagepng($dst);
        imagedestroy($image);
        imagedestroy($dst);
        exit();
    }

}
?>
