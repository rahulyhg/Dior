<?php

/**
 * 函数库
 * @copyright Copyright (c) 2010, shopex. inc
 * @author dongdong
 * 
 */
class middleware_func
{

    static function class_exists($class_name)
    {
        $p = strpos($class_name,'_');

        if($p){
            $owner = substr($class_name,0,$p);
            $class_name = substr($class_name,$p+1);
            $tick = substr($class_name,0,4);
            switch($tick){
            case 'ctl_':
                if(defined('CUSTOM_CORE_DIR') && file_exists(CUSTOM_CORE_DIR.'/'.$owner.'/controller/'.str_replace('_','/',substr($class_name,4)).'.php')){
                    $path = CUSTOM_CORE_DIR.'/'.$owner.'/controller/'.str_replace('_','/',substr($class_name,4)).'.php'; 
                }else{
                    $path = APP_DIR.'/'.$owner.'/controller/'.str_replace('_','/',substr($class_name,4)).'.php';
                }
                break;
            case 'mdl_':
                if(defined('CUSTOM_CORE_DIR') && file_exists(CUSTOM_CORE_DIR.'/'.$owner.'/model/'.str_replace('_','/',substr($class_name,4)).'.php')){
                    $path = CUSTOM_CORE_DIR.'/'.$owner.'/model/'.str_replace('_','/',substr($class_name,4)).'.php';
                }else{
                    $path = APP_DIR.'/'.$owner.'/model/'.str_replace('_','/',substr($class_name,4)).'.php';
                }
                break;
             default:
                if(defined('CUSTOM_CORE_DIR') && file_exists(CUSTOM_CORE_DIR.'/'.$owner.'/lib/'.str_replace('_','/',$class_name).'.php')){
                    $path = CUSTOM_CORE_DIR.'/'.$owner.'/lib/'.str_replace('_','/',$class_name).'.php';
                }else{
                    $path = APP_DIR.'/'.$owner.'/lib/'.str_replace('_','/',$class_name).'.php';
                }
            }
            if(file_exists($path)){
                return true;
            }else{
                return false;
            }
        }
    }

    /*
    * 获取随机数
    * 99%不会重复
    * @access public
    * @return String 32位的md5
    */
    static public function uniqid(){
        $microtime = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    public static function compare_params($ocs_params,$tg_params=''){
        if(empty($ocs_params) || empty($tg_params)) return NULL;
        
        $diff_arr = array();
        foreach ($ocs_params as $ocs_k=>$ocs_v){
            if((empty($ocs_v) && $ocs_v != '0') || !isset($tg_params[$ocs_k]) || $ocs_v != $tg_params[$ocs_k]){
                $diff_arr[$ocs_k] = $ocs_v;
            }
        }

        //$diff_arr = array_diff_assoc($ocs_params,$tg_params);
        $diff_arr_item = array();
        foreach ($diff_arr as $k=>$v){
            $ocs_json_content = json_decode($v,1);
            if(is_array($ocs_json_content) && isset($tg_params[$k])){
                $tg_json_content = json_decode($tg_params[$k],1);
                foreach ($ocs_json_content['item'] as $i_k=>$i_v){
                    $tmp = array_diff_assoc($ocs_json_content['item'][$i_k],$tg_json_content['item'][$i_k]);
                    foreach ($tmp as $sk=>$sv){
                        $diff_arr_item['ocs:'.$k][$i_k]['ocs:'.$sk.'=>'.$sv] = '[tg:'.$sk.'=>'.$tg_json_content['item'][$i_k][$sk].']';
                    }
                }
            }else{
                $tg_v = isset($tg_params[$k]) ? '[tg:'.$k.'=>'.$tg_params[$k].']' : '';
                $diff_arr_item['ocs:'.$k.'=>'.$v] = $tg_v;
            }
        }

        return $diff_arr_item;
    }
     /*
    * 获取随机数
    * 99%不会重复
    * @access public
    * @return String 25位的数字串
    */
    static public function gen_batchId(){
        $microtime = time();
        $numbers = range (1,100);
        shuffle ($numbers);
        $number = array_slice($numbers,0,7);
        $number = implode('',$number).rand(1,10);
        $batchid =$microtime.$number;
        return $batchid;
    }
}