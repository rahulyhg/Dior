<?php

class ome_sales_data{

    function generate($original_data,$delivery_id){
        if(!$original_data || !$delivery_id){
            return false;
        }

        $tmp_sales_data = array();

        $tmp_sales_data = $this->_generate_basic($original_data,$delivery_id);

        //生成销售明细信息
        $tmp_sales_data['sales_items'] = array();
        $sales_data_typeObj = kernel::single('ome_sales_data_type');
        foreach ($original_data['order_objects'] as $key => $obj){
            //传入发货主单号取对应信息，不然会对应不上出库入单id
            $obj['delivery_id'] = $delivery_id;

            $tmp_sale_items = $sales_data_typeObj->trans($obj['obj_type'],$obj);
            $tmp_sales_data['sales_items'] = array_merge($tmp_sales_data['sales_items'],$tmp_sale_items);
        }

        //补全销售明细中的平摊优惠，货品优惠，平摊优惠后的成交价
        $ome_sales_priceLib = kernel::single('ome_sales_price');
        if($ome_sales_priceLib->calculate($original_data,$tmp_sales_data)){
            return $tmp_sales_data;
        }else{
            return false;
        }
    }

    private function _generate_basic($original_data,$delivery_id){
        $deliveryObj = &app::get('ome')->model('delivery');
        $delivery_detail = $deliveryObj->dump(array('delivery_id'=>$delivery_id),'*');

        $delivery_billObj = &app::get('ome')->model('delivery_bill');
        $delivery_bill_infos = $delivery_billObj->getList('delivery_cost_actual',array('delivery_id'=>$delivery_id));

        //配送费用
        $tmp_sales_data['cost_freight'] = $original_data['shipping']['cost_shipping'];

        //预收物流费用
        $tmp_sales_data['delivery_cost'] = $original_data['shipping']['cost_shipping'];

        //发货时输入重量淘管预估物流费用
        $tmp_sales_data['delivery_cost_actual'] = $delivery_detail['delivery_cost_actual'];

        //追加多包裹单的物流费用
        if($delivery_bill_infos){
            foreach($delivery_bill_infos as $k=>$delivery_bill_info){
                $tmp_sales_data['delivery_cost_actual'] += $delivery_bill_info['delivery_cost_actual'];
            }
        }

        //附加费：保价费+税金+支付费用
        $tmp_sales_data['additional_costs'] = $original_data['shipping']['cost_protect'] + $original_data['cost_tax'] + $original_data['payinfo']['cost_payment'];

        //追加订单手工加价
        if ($original_data['discount'] > 0){
            $tmp_sales_data['additional_costs'] += $original_data['discount'];
        }

        //预付款:所有为预付款支付方式的支付单总额
        $sql = 'SELECT sum(money) AS deposit FROM `sdb_ome_payments` WHERE pay_type=\'deposit\' AND order_id=\''.$original_data['order_id'].'\'';
        $payments = $deliveryObj->db->selectrow($sql);
        $tmp_sales_data['deposit'] = $payments['deposit'] ? $payments['deposit'] : 0.00;

        //订单折扣费用:订单促销优惠+订单折扣+商品促销优惠
        $tmp_sales_data['discount'] = $original_data['pmt_goods'] + $original_data['pmt_order'];
        if ($original_data['discount'] < 0){
            $tmp_sales_data['discount'] += abs($original_data['discount']);
        }

        $tmp_sales_data['member_id'] = $original_data['member_id'];
        $tmp_sales_data['shop_id'] = $original_data['shop_id'];
        $tmp_sales_data['total_amount'] = $original_data['cost_item'];//商品金额

        $tmp_sales_data['payment'] = $original_data['payinfo']['pay_name'];//支付方式
        $tmp_sales_data['order_check_id'] = $original_data['op_id'];
        $tmp_sales_data['order_create_time'] = $original_data['createtime'];
        $tmp_sales_data['paytime'] = $original_data['paytime'];
        $tmp_sales_data['is_tax'] = $original_data['is_tax'];
        $tmp_sales_data['sale_amount'] = $original_data['total_amount'];//销售金额

        $tmp_sales_data['memo'] = '';
        $tmp_sales_data['order_id'] = $original_data['order_id'];
        $tmp_sales_data['branch_id'] = $delivery_detail['branch_id'];
        $tmp_sales_data['pay_status'] = 1;
        $tmp_sales_data['payed'] = $original_data['payed'];
        $operator = kernel::single('desktop_user')->get_name();
        $tmp_sales_data['operator'] = $operator ? $operator : 'system';
        $tmp_sales_data['sale_time'] = time();
        $tmp_sales_data['shopping_guide'] = '';
        $tmp_sales_data['logi_id'] = $delivery_detail['logi_id'];
        $tmp_sales_data['logi_name'] = $delivery_detail['logi_name'];
        $tmp_sales_data['logi_no'] = $delivery_detail['logi_no'];
        $tmp_sales_data['delivery_id'] = $delivery_id;
        $tmp_sales_data['order_check_time'] = $delivery_detail['create_time'];
        $tmp_sales_data['ship_time'] = $delivery_detail['delivery_time']?$delivery_detail['delivery_time']:time();
        
        return $tmp_sales_data;
    }
}