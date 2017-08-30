<?php
class logisticsmanager_print_tmpl {
    
    /**
     * 上传快递单模板
     * 
     * @param object $file 待上传的快递单模板文件
     * @return string 返回上传的消息
     */
    function upload_tmpl($file){
        
        $print_tmplObj = &app::get('logisticsmanager')->model('express_template');
        
        $extname = strtolower($this->extName($file['name']));
        $tar = kernel::single('ome_utility_tar');
        if($extname=='.dtp'){
            if($tar->openTAR($file['tmp_name'],'') && $tar->containsFile('info')){
                if(!($info = unserialize($tar->getContents($tar->getFile('info'))))){
                    $error_msg = "无法读取结构信息,模板包可能已损坏";
                    return $error_msg;
                }
                if ($tar->containsFile('background.jpg')){ //包含背景图
                    $rand = md5(time());
                    if(function_exists('sys_get_temp_dir')){
                        $tmpPath = sys_get_temp_dir().'/'.$rand.'.jpg';
                    }else{
                        $mark = kernel::single('ome_utility_tool');
                        $tmpPath = $mark->get_temp_dir().'/'.$rand.'.jpg';
                    }
                    
                    file_put_contents($tmpPath,$tar->getContents($tar->getFile('background.jpg')));
                }
                if (file_exists($tmpPath)){//保存图片
                    $ss = kernel::single('base_storager');
                    $Path = substr($tmpPath,strrpos($tmpPath,'dly_bg_'));
                    $file['name'] = $Path;
                    $file['type'] = 'image/jpeg';
                    $file['size'] = filesize($tmpPath);
                    $file['tmp_name'] = $tmpPath;
                    $id = $ss->save_upload($file,"file","",$msg);//返回file_id;
                }
                unlink($tmpPath);
                
                $tmpl_info['file_id'] = $id;
                error_log(var_export($info,1),3,__FILE__.'.log');
                $re = $print_tmplObj->save($info);//保存快递单模板 
                
                if ($re){
                    $error_msg = "success";
                    return $error_msg;
                }
                $error_msg = "上传失败";
                return $error_msg;
            }else{
                $error_msg = "无法解压缩,模板包可能已损坏";
                return $error_msg;
            }
        }else{
            $error_msg = "必须是shopex快递单模板包(.dtp)";
            return $error_msg;
        }
        $error_msg = "success";
        return $error_msg;
    }
    /*
     * 提取扩展名
     */
    function extName($file){
        return substr($file,strrpos($file,'.'));
    }
    
}