<?php
class logisticsmanager_mdl_express_template extends dbeav_model{    
    function getElements(){
        $elements = array();
        //获取快递单打印项的配置列表
        foreach(kernel::servicelist('ome.service.template') as $object=>$instance){
            if (method_exists($instance, 'getElements')){
                $tmp = $instance->getElements();
            }
            $elements = array_merge($elements, $tmp);
        }
        return $elements;
    }

    /**
     * 获得单据项
     * @param String $type 类型
     */
    public function getElementsItem($type) {
        $elements = array();
        $class = 'ome_service_template_' . $type;
        $instance = kernel::single($class);
        $elements = $instance->getElements();
        return $elements;
    }
}
?>