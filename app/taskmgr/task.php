<?php
class taskmgr_task{

    function post_install($options){
        //添加导出任务需要的文件夹路径,本地缓存目录
        if(!is_dir(DATA_DIR.'/export/cache')){
            utils::mkdir_p(DATA_DIR.'/export/cache');
        }

        //导出本地数据文件目录
        if(!is_dir(DATA_DIR.'/export/file')){
            utils::mkdir_p(DATA_DIR.'/export/file');
        }

        //下载ftp模式的文件本地缓存目录
        if(!is_dir(DATA_DIR.'/export/tmp_local')){
            utils::mkdir_p(DATA_DIR.'/export/tmp_local');
        }

        //上传ftp模式的文件本地缓存目录
        if(!is_dir(DATA_DIR.'/export/tmp_remote')){
            utils::mkdir_p(DATA_DIR.'/export/tmp_remote');
        }
    }

}
