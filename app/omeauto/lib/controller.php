<?php
class omeauto_controller extends desktop_controller{
    
    function show_condition_detail($condition,$data=array()){
        echo kernel::single('omeauto_auto')->get_condition_detail($condition,$data);
    }
    
    /*
     * 所有的条件各自的特殊的异步都走这边，第一个参数是所属的条件的类名，第二个参数是该类的某个方法（获取html界面），后面所有的参数都作为第二个参数方法的参数
     */
    function ajax_data(){
        $params = func_get_args();
        $condition = array_shift($params);
        $method = array_shift($params);
        
        $p = "";
        
        if($params){
            $p = "'".implode("','",$params)."'";
        }
        
        eval('$data=kernel::single("'.$condition.'")->'.$method.'('.$p.');');
        echo $data;
    }
}