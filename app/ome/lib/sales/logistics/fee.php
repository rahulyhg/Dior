<?php

class ome_sales_logistics_fee{

    function calculate($orderids,&$sales_data){
        if(!$orderids || !$sales_data){
            return false;
        }

        $this->_calByPayed($orderids,$sales_data);
    }

    //按订单支付金额贡献度分摊运费
    private function _calByPayed($orderids,&$sales_data){
        $all_order_payed = 0.00;
        $all_logistics_fee = 0.00;
        foreach($orderids as $k => $orderid){
            $all_logistics_fee = $sales_data[$orderid]['delivery_cost_actual'];
            $all_order_payed += $sales_data[$orderid]['payed'];
            if($sales_data[$orderid]['payed'] == 0.00){
                $sales_data[$orderid]['delivery_cost_actual'] = 0.00;
            }
        }

        $loop = 1;
        $order_count = count($orderids);
        $has_apportion_fee = 0.00;
        if($all_logistics_fee > 0){
            foreach ($orderids as $k => $orderid){
                if($order_count == $loop){
                    $sales_data[$orderid]['delivery_cost_actual'] = $all_logistics_fee - $has_apportion_fee;
                }else{
                    $sales_data[$orderid]['delivery_cost_actual'] = $all_order_payed*$sales_data[$orderid]['payed'] ? round($all_logistics_fee/$all_order_payed*$sales_data[$orderid]['payed'],2) : 0;
                    $has_apportion_fee += $sales_data[$orderid]['delivery_cost_actual'];
                }
                
                $loop++;//防止多算一分钱 ExBOY
                
                unset($sales_data[$orderid]['payed']);
            }
            
        }
    }

}