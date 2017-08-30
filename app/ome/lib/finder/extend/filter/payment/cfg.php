<?php
class ome_finder_extend_filter_payment_cfg{
    function get_extend_colums(){
        $pay_type = ome_payment_type::pay_type();
        $shopObj = &app::get('ome')->model('shop');
        $shopList = $shopObj->getList('*');
        $shop_id = array();
        foreach($shopList as $shop){
            $shop_id[$shop['shop_id']] = $shop['name'];
        }

        $db['payment_cfg']=array (
            'columns' => array (
                'pay_type' => array (
                    'type' => $pay_type,
                    'width' => 75,
                    'editable' => false,
                    'label' => '支付类型',
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'shop_id' => array (
                    'type' => $shop_id,
                    'label' => '关联店铺',
                    'width' => 75,
                    'editable' => false,
                    'in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
            )
        );
        return $db;
    }
}
