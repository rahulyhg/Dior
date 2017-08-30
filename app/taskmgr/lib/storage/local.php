<?php
/**
 * 数据文件生成local类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_storage_local extends taskmgr_storage_abstract implements taskmgr_storage_interface{

    private $_path = '';

    function __construct(){
        $this->_path = DATA_DIR.'/export/file/';
    }

    /**
     * 本地保存生成文件
     * 
     * @param string $source_file 源文件含路径
     * @param string $task_id 目标文件名命名传入参数
     * @param string $url 生成目标文件路径
     * @return boolean true/false
     */
    public function save($source_file, $task_id, &$url){
        //存储的目的地文件路径
        $destination_file = $this->_get_ident($task_id);

        //传输上传文件
        if(!copy($source_file, $this->_path.$destination_file)){
            return false;
        }else{
            @chmod($this->_path.$destination_file,0666);
            $url = $destination_file;
            return true;
        }
    }

    /**
     * 本地文件生成本地临时读取数据的文件
     * 
     * @param string $url 源文件
     * @param string $local_file 本地临时文件
     * @return boolean true/false
     */
    public function get($url, $local_file){
        if(!copy($this->_path.$url, $local_file)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 删除本地指定的文件
     * 
     * @param string $url 源文件
     */
    public function delete($url){
        @unlink($this->_path.$url);
    }

    //含完整路径的生成文件地址
    public function _get_ident($key){
        $need_mkdir = true;
        $folder = date('Ymd');

        /*
        检查本地是否包含指定日期的文件夹，有就不需要新建
        if(is_dir($path.$folder)){
            $need_mkdir = false;
        }else{
            $need_mkdir = true;
        }

        新建日期文件夹，创建失败指定当前文件夹位置为unknown
        if($need_mkdir){
            $mkdir_res = mkdir($path.$folder, 0755);
            if(!$mkdir_res){
                $folder = 'unknown';
            }
        }
        */

        if(!is_dir($this->_path.$folder)){
            utils::mkdir_p($this->_path.$folder);
        }

        $filename = $this->_ident($key);
        $url = $folder.'/'.$filename;
        return $url;
    }


}