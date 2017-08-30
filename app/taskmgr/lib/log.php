<?php
/**
 * 日志记录类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_log{

    /**
     * 写日志
     *
     * @param string $filename
     * @param string $info
     * @return null
     */
    static public function log($filename, $info){
        $logfile = dirname(__FILE__) . '/../logs/'.date('Ymd').'/'.$filename.'.log';
        if(!file_exists($logfile)){
            if(!is_dir(dirname($logfile)))  self::mkdir_p(dirname($logfile));
        }
        error_log(date('Y-m-d H:i:s') . "\t" . $info."\n",3,$logfile);
    }

    static public function mkdir_p($dir,$dirmode=0755){
        $path = explode('/',str_replace('\\','/',$dir));
        $depth = count($path);
        for($i=$depth;$i>0;$i--){
            if(file_exists(implode('/',array_slice($path,0,$i)))){
                break;
            }
        }
        for($i;$i<$depth;$i++){
            if($d= implode('/',array_slice($path,0,$i+1))){
                if(!is_dir($d)) mkdir($d,$dirmode);
            }
        }
        return is_dir($dir);
    }
}
