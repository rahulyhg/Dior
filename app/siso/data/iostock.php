<?php
/**
* 组织出入库数据
*/

class siso_data_iostock {
    
    /**
    *
    * 根据类型返回各种类型出入库所需的数据
    *
    * return array $data
    */
    public function iostock_data($type,$id,$type_id,$message) {
        $common_class = array('purchase','purchasereturn');//通用
        if (in_array($type,$common_class)){
            $type = 'common';
        }
        $class_name = sprintf('siso_data_iostock_%s',$type);
        $data = kernel::single($class_name)->get_iostock_data($iso_id,&$type_id,$start=0,$limit=0);
        return $data;
    }

    
}


?>