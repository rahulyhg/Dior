<?php
class console_reship{
    function reship_data($reship_id){
        $oReship = &app::get('ome')->model('reship');
        $oOrders = &app::get('ome')->model('orders');
        $reship_data = $oReship->dump(array('reship_id'=>$reship_id),'reship_bn,logi_no,logi_id,order_id,t_begin,return_type');
        $Oreship_items = &app::get('ome')->model('reship_items');
        $oProcess_items = &app::get('ome')->model('return_process_items');
        $Odly_corp = &app::get('ome')->model('dly_corp');
        $dly_corp = $Odly_corp->dump($reship_data['logi_id'],'type');
        $Oreship_items = &app::get('ome')->model('reship_items');
        $orders = $oOrders->dump($reship_data['order_id'],'order_bn');
        $reship_list = $Oreship_items->getlist('bn,product_name,product_id,num,branch_id',array('reship_id'=>$reship_id),0,-1);
        $data = array();
        foreach ($reship_list as $list) {
            $branch_id = $list['branch_id'];

            if(isset($data[$branch_id])){
                $data[$branch_id]['items'][] = $list;
            }else{
                //获取仓库详情
                $branch_detail = kernel::single('console_iostockdata')->getBranchByid($branch_id);

                $data[$branch_id]['items'][] = $list;
                $data[$branch_id]['reship_bn'] = $reship_data['reship_bn'];
                $data[$branch_id]['branch_bn'] = $branch_detail['branch_bn'];
                $data[$branch_id]['storage_code'] = $branch_detail['storage_code'];
                $data[$branch_id]['create_time'] = $reship_data['t_begin'];//storage_code
                $data[$branch_id]['memo'] = '';//storage_code
                $data[$branch_id]['return_type'] = $reship_data['return_type'];
                //memo original_delivery_bn
                $data[$branch_id]['original_delivery_bn'] = '';
                $data[$branch_id]['logi_no'] = $reship_data['logi_no'];
                $data[$branch_id]['logi_name'] = $reship_data['logi_name'];
                $data[$branch_id]['logi_code'] = $dly_corp['type'];
                $data[$branch_id]['order_bn'] = $orders['order_bn'];
                $data[$branch_id]['receiver_name'] = $reship_data['ship_name'];
                $data[$branch_id]['receiver_zip'] = $reship_data['ship_zip'];
                $data[$branch_id]['receiver_state'] = $reship_data['ship_area'];
                $data[$branch_id]['receiver_city'] = '';
                $data[$branch_id]['receiver_district'] = '';
                $data[$branch_id]['receiver_address'] = $reship_data['ship_addr'];
                $data[$branch_id]['receiver_phone'] = $reship_data['ship_tel'];
                $data[$branch_id]['receiver_mobile'] = $reship_data['ship_mobile'];
                $data[$branch_id]['receiver_email'] = $reship_data['ship_email'];
                
            }
        }
        
        return $data;
    }

    /**
    * 取消退货单
    */
    function notify_reship($type,$reship_id){
        $reship_data = kernel::single('console_reship')->reship_data($reship_id);
        if ($type == 'create'){//创建
            
            foreach ($reship_data as $rk=>$rv) {
                $wms_id = kernel::single('ome_branch')->getWmsIdById($rk);
                $tmp = $rv;
                kernel::single('console_event_trigger_reship')->create($wms_id, $tmp, false);
            }
        }else if($type == 'cancel'){//取消

            foreach ($reship_data as $rk=>$rv) {
                $wms_id = kernel::single('ome_branch')->getWmsIdById($rk);
                $tmp = $rv;
                kernel::single('console_event_trigger_reship')->cancel($wms_id, $tmp, true);
            }

        }else{
            return true;
        }

    }

    
    /**
     * 预占换货商品库存.
     * @param  reship_id
     * @return  
     * @access  public
     * @author sunjng@shopex.cn
     */
    function change_freezeproduct($reship_id,$type='+')
    {
        $oProducts = &app::get('ome')->model('products');
        $branch_productObj = &app::get('ome')->model('branch_product');
        $reship_item = $this->change_items($reship_id);
        foreach ( $reship_item as $item ) {
            //
            $branch_id = $item['branch_id'];
            $product_id = $item['product_id'];
            $num = $item['num'];
            //修改预占库存
            if ($type == '+') {
                $oProducts->freez($product_id,$num);
                $branch_productObj->freez($branch_id,$product_id,$num);
				$branch_productObj->chg_store_freeze_change($branch_id,$product_id,$num,'+');
            }elseif ($type=='-') {
                $oProducts->unfreez($branch_id,$product_id,$num);
                $branch_productObj->unfreez($branch_id,$product_id,$num);    
				$branch_productObj->chg_store_freeze_change($branch_id,$product_id,$num,'-');
            }
        }
    }

    
    /**
     * 换货商品明细
     * @param   
     * @return  array
     * @access  public
     * @author sunjng@shopex.cn
     */
    function change_items($reship_id)
    {
        $oReship_item = &app::get('ome')->model('reship_items');
        $reship_item = $oReship_item->getList('bn,product_name,num,product_id,branch_id',array('reship_id'=>$reship_id,'return_type'=>'change'),0,-1);
        return $reship_item;
    }
}
?>