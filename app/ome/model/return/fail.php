<?php
class ome_mdl_return_fail extends dbeav_model{
    public function table_name($real=false){
        $table_name = 'return_product';
        if($real){
            return kernel::database()->prefix.'ome_'.$table_name;
        }else{
            return $table_name;
        }
    }
    
    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'return_bn'=>app::get('base')->_('退货记录流水号'),
            'order_bn'=>app::get('base')->_('订单号'),
            'ship_name'=>app::get('base')->_('收货人'),
            'member_uname'=>app::get('base')->_('用户名'),
            'product_bn'=>app::get('base')->_('货号'),
        );
        return array_merge($parentOptions,$childOptions);
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        if(isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn|head'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            //
            $archorderObj = app::get('archive')->model('orders');
            $archorder = $archorderObj->getList('order_id',array('order_bn'=>$filter['order_bn']));
            foreach ($archorder as $arc ) {
                $orderId[] = $arc['order_id'];
            }

            $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['order_bn']);
        }
        if(isset($filter['ship_name'])){
            $deliveryObj = &$this->app->model("delivery");
            $rows = $deliveryObj->getList('delivery_id',array('ship_name'=>$filter['ship_name']));
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['ship_name']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = &$this->app->model("members");
            $rows = $memberObj->getList('member_id',array('uname|has'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }
            $where .= '  AND member_id IN ('.implode(',', $memberId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['product_bn'])){
            $returnItemObj = &$this->app->model("return_product_items");
            $rows = $returnItemObj->getList('return_id',array('bn'=>$filter['product_bn']));
            $returnId[] = 0;
            foreach($rows as $row){
                $returnId[] = $row['return_id'];
            }
            $where .= '  AND return_id IN ('.implode(',', $returnId).')';
            unset($filter['product_bn']);
        }

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }
}
?>