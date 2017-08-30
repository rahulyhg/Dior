<?php
class wms_operation_log{

    /**
     * 定义当前APP下的操作日志的所有操作名称列表
     * type键值由表名@APP名称组成
     * @access public
     * @return Array
     */
    function get_operations(){
        $operations = array(
           //发货单
           'delivery_modify' => array('name'=> '发货单详情修改','type' => 'delivery@wms'),
           'delivery_position' => array('name'=> '发货单货位 录入','type' => 'delivery@wms'),
           'delivery_merge' => array('name'=> '发货单合并','type' => 'delivery@wms'),
           'delivery_split' => array('name'=> '发货单拆分','type' => 'delivery@wms'),
           'delivery_stock' => array('name'=> '发货单备货单打印','type' => 'delivery@wms'),
           'delivery_deliv' => array('name'=> '发货单商品信息打印','type' => 'delivery@wms'),
           'delivery_expre' => array('name'=> '发货单快递单打印','type' => 'delivery@wms'),
           'delivery_logi_no' => array('name'=> '发货单快递单 录入','type' => 'delivery@wms'),
           'delivery_check' => array('name'=> '发货单校验','type' => 'delivery@wms'),
           'delivery_process' => array('name'=> '发货单发货处理','type' => 'delivery@wms'),
           'delivery_back' => array('name'=> '发货单打回','type' => 'delivery@wms'),
           'delivery_logi' => array('name'=> '发货单物流公司修改','type' => 'delivery@wms'),
           'delivery_pick' => array('name'=> '发货单拣货','type' => 'delivery@wms'),
            //新增发货称重报警处理
            'delivery_weightwarn' => array('name'=> '发货称重报警处理','type' => 'delivery@wms'),
           //子物流单操作日志
           'delivery_bill_print' => array('name'=> '多包裹物流单 打印','type' => 'delivery@wms'),
           'delivery_bill_delete' => array('name'=> '多包裹物流单 删除','type' => 'delivery@wms'),
           'delivery_bill_add' => array('name'=> '多包裹物流单 录入','type' => 'delivery@wms'),
           'delivery_bill_modify' => array('name'=> '多包裹物流单 修改','type' => 'delivery@wms'),
           'delivery_bill_express' => array('name'=> '多包裹物流单 发货','type' => 'delivery@wms'),
           'delivery_checkdelivery'=>array('name'=>'发货单发货处理','type' => 'delivery@wms'),
           
        );
        return array('wms'=>$operations);
    }
}
?>