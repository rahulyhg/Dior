<?php

class wms_mdl_print_tmpl extends dbeav_model{

    function getElements(){
        $elements = array();
        //获取快递单打印项的配置列表
        foreach(kernel::servicelist('wms.service.template') as $object=>$instance){
            if (method_exists($instance, 'getElements')){
                $tmp = $instance->getElements();
            }
            $elements = array_merge($elements, $tmp);
        }
        return $elements;
    }

}
?>