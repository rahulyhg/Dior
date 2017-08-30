<?php
/****更新发货单的订单下单时间字段数据****/
function _update_delivery_order_createtime(){
    $oOrder = &app::get('ome')->model('orders');
    $oDeliveryOrder = &app::get('ome')->model('delivery_order');
    $delivery_order_list = $oDeliveryOrder->getList('*');
    
    if(is_array($delivery_order_list) && count($delivery_order_list)>0){
        $aOrderByCreateTime = array();
        $aOrderByDelivery = array();
        foreach($delivery_order_list as $delivery_order){
            if(!isset($aOrderByDelivery[$delivery_order['order_id']])){
                $aOrderByDelivery[$delivery_order['order_id']] = array();
            }
            
            $aOrderByDelivery[$delivery_order['order_id']][] = $delivery_order['delivery_id'];
        }
        
        $order_list = $oOrder->getList('order_id,createtime',array('order_id'=>array_keys($aOrderByDelivery)));
        foreach($order_list as $order){
            $aOrderByCreateTime[$order['order_id']] = $order['createtime'];
        }
        
        foreach($aOrderByDelivery as $order_id=>$delivery_ids){
            kernel::database()->exec('UPDATE sdb_ome_delivery SET order_createtime='.$aOrderByCreateTime[$order_id].' WHERE delivery_id IN('.implode(',',$delivery_ids).')');
        }
    }
     
    $oDelivery = &app::get('ome')->model('delivery');
    $delivery_list = $oDelivery->getList('delivery_id',array('parent_id'=>0));
    foreach($delivery_list as $delivery){
        _updateMergeDelivery($delivery['delivery_id']);
    }
}

function _updateMergeDelivery($delivery_id){
    $oDelivery = &app::get('ome')->model('delivery');
    $sub_delivery_list = $oDelivery->getList('order_createtime',array('parent_id'=>$delivery_id));
    if(is_array($sub_delivery_list) && count($sub_delivery_list) > 0){
        $lenOrder = count($sub_delivery_list);
        $order_createtime = $sub_delivery_list[0]['order_createtime'];
        for($i=1;$i<$lenOrder;$i++){
            if(isset($sub_delivery_list[$i])){
                if($order_createtime > $sub_delivery_list[$i]['order_createtime']){
                    $order_createtime = $sub_delivery_list[$i]['order_createtime'];
                }
            }
        }
        
        kernel::database()->exec('UPDATE sdb_ome_delivery SET order_createtime='.$order_createtime.' WHERE delivery_id='.$delivery_id);
        
        return true;
    }else{
        return false;
    }
}

_update_delivery_order_createtime();

/***************************************************************************************************************************/

/**
 * 更新店铺类型
 * 条件 ：所有存在shop_type字段的表，将shopex.b2c改为ecos.b2c、shopex.b2b改为shopex_b2b
 *        将存在config字段的所有表，字段值键名为shop_type的序列值shopex.b2c改为ecos.b2c、shopex.b2b改为shopex_b2b
 */
function update_shop_type(){
	
	$orderObj = &app::get('ome')->model('orders');// 订单表
	$shopObj = &app::get('ome')->model('shop');// 前端店铺表
	
	//订单表(orders)
	$orderObj->update(array('shop_type'=>'ecos.b2c'), array('shop_type'=>'shopex.b2c'));
	$orderObj->update(array('shop_type'=>'shopex_b2b'), array('shop_type'=>'shopex.b2b'));
    //前端店铺表(shop)
	$shopObj->update(array('shop_type'=>'ecos.b2c'), array('shop_type'=>'shopex.b2c'));
    $shopObj->update(array('shop_type'=>'shopex_b2b'), array('shop_type'=>'shopex.b2b'));
    $shopObj->update(array('node_type'=>'ecos.b2c'), array('node_type'=>'shopex.b2c'));
    $shopObj->update(array('node_type'=>'shopex_b2b'), array('node_type'=>'shopex.b2b'));
    
    //omeauto修改
    //存在config字段表
    $tables = array('autobind','autoconfirm','autodispatch');
    
    foreach ($tables as $name){
       $models = &app::get('omeauto')->model($name);
       $rows = $models->getList('config,oid', '', 0, -1);
       if ($rows)
       foreach ($rows as $configs){
          $shop_type = '';
          //读取
          $config_detail = $configs['config'];
          if ($config_detail){
              foreach ($config_detail as $keys=>$items){
                 if ($keys != 'omeauto_condition_salesplatform') continue;
                 foreach ($items as $shop_id=>$shop_detail){
                    if ($shop_detail['shop_type'] == 'shopex.b2c') $shop_type = 'ecos.b2c';
                    elseif ($shop_detail['shop_type'] == 'shopex.b2b') $shop_type = 'shopex_b2b';
                    else break;
                    $config_detail[$keys][$shop_id]['shop_type'] = $shop_type;
                }
              }
          }
          //更新
          if ($shop_type){
              $update_data = array('config'=>$config_detail);
              $filter = array('oid'=>$configs['oid']);
              $models->update($update_data, $filter);
          }
       }
    }
	
}
update_shop_type();

/***************************************************************************************************************************/

//安装报表工具
$shell->exec_command("install omeanalysts");