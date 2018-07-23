<?php
/**
 * Created by PhpStorm.
 * User: D1M
 * Date: 2018/3/1
 * Time: 15:33
 */
class qmwms_ctl_service{
    public function index(){

        $content = file_get_contents('php://input');
        $params = $_REQUEST;
        //error_log(date('Y-m-d H:i:s').'##########XML文件信息##########'."\r\n".var_export($content,true)."\r\n", 3, ROOT_DIR.'/data/logs/wmstoms'.date('Y-m-d').'.xml');
        //error_log(date('Y-m-d H:i:s').'##########HEADER参数信息##########'."\r\n".var_export($params,true)."\r\n", 3, ROOT_DIR.'/data/logs/wmstoms'.date('Y-m-d').'.xml');
        $res = kernel::single('qmwms_response_qmoms')->qmToOms($content,$params);
        echo $res;
    } 











































}