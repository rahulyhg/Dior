<?php
/**
 * 数据文件生成ftp类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_storage_ftp extends taskmgr_storage_abstract implements taskmgr_storage_interface{

    static private $_storageConn = null;

    function __construct(){
        $this->connect();
    }

    public function connect(){
        if(!isset(self::$_storageConn)){
            if(isset($GLOBALS['__STORAGE_CONFIG'])){

                $config = $GLOBALS['__STORAGE_CONFIG'];

                self::$_storageConn = ftp_connect($config['HOST'], $config['PORT'], $config['TIMEOUT']);
                if(!self::$_storageConn){
                    trigger_error('connect ftp failed, please check it', E_USER_ERROR);
                }

                $login_result = ftp_login(self::$_storageConn, $config['USER'], $config['PASSWD']);
                if(!$login_result){
                    trigger_error('login ftp failed, please check it', E_USER_ERROR);
                }

                if($config['PASV'] == true){
                    ftp_pasv(self::$_storageConn, true);
                }

                return true;
            }else{
                trigger_error('can\'t load __STORAGE_CONFIG, please check it', E_USER_ERROR);
            }
        }
    }

    /**
     * 向远程ftp上传保存生成文件
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
        $upload_result = ftp_put(self::$_storageConn, $destination_file, $source_file, FTP_BINARY);

        //ftp链接退出
        ftp_close(self::$_storageConn);
        //unset(self::$_storageConn);

        //上传结果
        if(!$upload_result){
            return false;
        }else{
            $url = $destination_file;
            return true;
        }
    }

    /**
     * 向远程ftp下载文件到本地
     * 
     * @param string $url 远程源文件
     * @param string $local_file 本地目标文件
     * @return boolean true/false
     */
    public function get($url, $local_file){
        $download_result = ftp_get(self::$_storageConn, $local_file, $url, FTP_BINARY);

        //ftp链接退出
        ftp_close(self::$_storageConn);
        //unset(self::$_storageConn);

        if(!$download_result){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 向远程ftp删除指定文件
     * 
     * @param string $url 远程源文件
     */
    public function delete($url){
        $del_result = ftp_delete(self::$_storageConn, $url);

        //ftp链接退出
        ftp_close(self::$_storageConn);

        if(!$del_result){
            return false;
        }else{
            return true;
        }
    }

    //含完整路径的生成文件地址
    public function _get_ident($key){
        $need_mkdir = true;
        $folder = date('Ymd');

        //判断ftp中是否包含指定日期的文件夹，有就不需要新建
        $directories = ftp_nlist(self::$_storageConn, '/');
        if($directories){
            foreach ($directories as $k => $direct) {
                if(strpos($direct, $folder) !== false){
                    $need_mkdir = false;
                    break;
                }
            }
        }else{
            $need_mkdir = true;
        }

        //新建日期文件夹，创建失败指定当前文件夹位置为unknown
        if($need_mkdir){
            $mkdir_res = ftp_mkdir(self::$_storageConn, $folder);
            if(!$mkdir_res){
                $folder = 'unknown';
            }
        }

        $path = $this->_ident($key);
        $url = $folder.'/'.$path;
        return $url;
    }


}