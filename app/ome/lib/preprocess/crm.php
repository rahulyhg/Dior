<?php
class ome_preprocess_crm{
    public $fenxiaowang = 'shopex_b2b';//分销王的店铺类型,需要检测订单类型
    private static  $__crm_gifts = array();
    
    #$type是重新强制请求CRM赠品的标示
    public function process($order_id,&$msg,$type=false){
        #获取crm基本配置
        $crm_cfg = app::get('crm')->getConf('crm.setting.cfg');
        #如果没有开启crm应用，程序返回
        if(empty($crm_cfg)){
            return false;
        }
        $operationLogObj = &app::get('ome')->model('operation_log');
        $Obj_preprocess = &app::get('ome')->model('order_preprocess');
        $opinfo = kernel::single('ome_func')->getDesktopUser();
        if(!$order_id){
            return false;
        }

        #检测是否开启赠品
        if($crm_cfg['gift'] != 'on'){
            $msg = '订单没有开启CRM赠品';
            $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
            return false;
        }
        $_rs = $Obj_preprocess->getList('preprocess_order_id',array('preprocess_order_id'=>$order_id,'preprocess_status'=>'1','preprocess_type'=>'crm'));
        #不为空，说明已经获取过CRM赠品
        if(!empty($_rs)){
            return true;
        }
        $orderObj = &app::get('ome')->model('orders');
        $obj_channel = &app::get('channel')->model('channel');
        
        #根据订单号，找到当前订单的相关信息
        $order_info = $obj_channel->getOrderInfo($order_id);
        #获取订单明细
        $order_item_info = $obj_channel->getOrderItemInfo($order_id);
       
        #打标的状态
        $status = $order_info['abnormal_status'] | ome_preprocess_const::__HASCRMGIFT_CODE;
        
        $order_bn    = $order_info['order_bn'];#单号
        $shop_type    = $order_info['shop_type'];#店铺类型
        $shop_id       = $order_info['shop_id'];#店铺节点
        $receiver_name = $order_info['ship_name'];#收件人姓名
        $buyer_nick    = $order_info['uname'];#会员名
        $mobile        = $order_info['ship_mobile'] ? $order_info['ship_mobile'] : '';
        $tel           = $order_info['ship_tel'] ? $order_info['ship_tel'] : '';
        $ship_area     = $order_info['ship_area'];
        $ship_area_arr = '';
        if ($ship_area && is_string($ship_area)) {
            kernel::single('ome_func')->split_area($order_info['ship_area']);
            $ship_area_arr = $order_info['ship_area'];
        }
        $payed = $order_info['payed'] ?  $order_info['payed'] : 0;#付款金额
        $pay_time = $order_info['paytime'] ? $order_info['paytime'] : 0;#付款时间
        $isCod = $order_info['is_cod'] == 'true' ? 1 : 0;

        #检测当前订单的来源店铺否开启赠品
        if(empty($crm_cfg['name'][$shop_id])){
           return false;
        }
        $shop_shop_type = $this->shopex_shop_type();
        if($shop_shop_type[$shop_type]){
            $msg = 'shopex类型店铺，不支持CRM赠品处理';
            $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
            return false;
        }

       #检测完毕,调用赠品接口,获取赠品规则
       $params = array(
           'buyer_nick' => $buyer_nick,
           'receiver_name'=> $receiver_name,
           'mobile' => $mobile,
           'tel' => $tel,
           'shop_id' => $shop_id,
           'order_bn' => $order_bn,
           'province' => $ship_area_arr[0],
           'city' => $ship_area_arr[1],
           'district' => $ship_area_arr[2],
           'total_amount' => $order_info['total_amount'],
           'payed' => $payed,
           'createtime' => $order_info['createtime'],
           'pay_time' => $pay_time,
           'is_cod' => $isCod,
           'items' => $order_item_info
       );
       #强制重新请求的标示
       if($type){
           $params['is_send_gift'] = 1;
       }else{
           $params['is_send_gift'] = 0;
       }
       $obj_crm_rpc = kernel::single('crm_rpc_gift'); 
       #根据店铺节点、收件人、会员名、手机，获取赠品规则
       $gift_rule = $obj_crm_rpc->getGiftRule($params);
       if($gift_rule['result'] == 'succ'){
           #赠品数据为空
           if(empty($gift_rule['data'])){
               $msg = '订单CRM赠品数据为空';
               $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
               return false;
           }
           #CRM返回订单与发送赠品请求的订单号不一致
           if($gift_rule['data']['order_bn'] != $order_bn){
               $msg = 'CRM返回订单号与请求单号不一致';
               $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
               return false;
           }
           #订单天生没有赠品，则程序返回
           if(empty($gift_rule['data']['gifts'])){
               $msg = '订单CRM赠品为空';
               $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
               return false;
           }
       }elseif($gift_rule['result'] == 'fail'){
           $msg = '订单CRM预处理:'.$gift_rule['msg'];
           #不添加赠品，继续审单发货
           if($crm_cfg['error'] == 'off' ){
               $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
               return false;
           }elseif($crm_cfg['error'] == 'on'){
               #打标提醒，人工处理
               $orderObj->update(array('abnormal_status'=>$status),array('order_id'=>$order_id));
               $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
               #打标与记日志完成后，程序返回
               return false;
           }
       }else{
           $msg = '订单CRM预处理出错';
           #打标提醒，人工处理
           $orderObj->update(array('abnormal_status'=>$status),array('order_id'=>$order_id));
           $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
           #打标与记日志完成后，程序返回
           return false;
       } 
       
       #获取crm请求返回的所有gift_id
       $crm_gift_bn = array();
       $crm_gifts = $gift_rule['data']['gifts'];  #CRM这层结构：'gifts'=> array('bn1'=>1,'bn2'=>2,....)
       $crm_gift_bn = array_keys($crm_gifts);
       #根据赠品货号，找到赠品对应的货品信息
       $obj_product = &app::get('ome')->model('products');
       $product_info = $obj_product->getList('product_id,goods_id,bn,name',array('bn'=>$crm_gift_bn));
       if(empty($product_info)){
           $msg = 'CRM赠品在ERP出错!';
           $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
           return false;
       }

       #当赠品数据存在,继续检测赠品设置
       if($product_info){
           $obj_crm_gift = &app::get('crm')->model('gift');
           #根据crm请求返回的gift_id，到淘管赠品数据库中检测赠品货号是否对应
           $_gift_bn = $obj_crm_gift->getList('gift_bn',array('gift_bn'=>$crm_gift_bn));
           #检测ERP正常赠品
           $erp_gift_bn = array_map('current',$_gift_bn);
           #获取无法在淘管中对应的赠品货号
           $diff_bn = array_diff($crm_gift_bn, $erp_gift_bn);
           #赠品不对应(如ERP已把赠品删除,而CRM没及时同步)
           if(!empty($diff_bn)){
               $is_fail = true;
           }
       }
       
       #库存不足的,放在审单的地方处理,这里不再验证库存   
       

       

       #以上验证处理完毕，开始处理相关订单流程
       $orderItemObj  = &app::get('ome')->model("order_items");
       $orderObjectObj  = &app::get('ome')->model("order_objects");


       
      kernel::database()->exec('begin');
      foreach($product_info as $info){
          $is_update = true;
              $tmp_obj = array(
                      'order_id' => $order_id,
                      'obj_type' => 'gift',
                      'shop_goods_id' => '-1',#CRM赠品类型标示
                      'goods_id' => $info['goods_id'],
                      'bn' => $info['bn'],
                      'name' => $info['name'],
                      'price' => 0.00,
                      'sale_price' => 0.00,
                      'pmt_price' => 0.00,
                      'amount' => 0.00,
                      'quantity' => $crm_gifts[$info['bn']]#CRM返回的赠品数量
              );
              if($orderObjectObj->save($tmp_obj)){
                      $tmp_items = array(
                              'order_id' => $order_id,
                              'obj_id' => $tmp_obj['obj_id'],
                              'shop_goods_id' => '-1',#CRM赠品类型标示
                              'product_id' => $info['product_id'],
                              'shop_product_id' => '-1',#CRM赠品类型标示
                              'bn' => $info['bn'],
                              'name' => $info['name'],
                              'cost' => 0.00,
                              'price' => 0.00,
                              'amount' => 0.00,
                              'sale_price'=> 0.00,
                              'pmt_price' => 0.00,
                              'quantity' =>  $crm_gifts[$info['bn']],#CRM返回的赠品数量
                              'sendnum' => 0,
                              'item_type' => 'gift',
                      );
                      if($orderItemObj->save($tmp_items)){
                          $obj_product->chg_product_store_freeze($info['product_id'],$crm_gifts[$info['bn']],"+");
                      }else{
                          $is_update = false;
                          kernel::database()->rollBack();
                          break;
                      }
              }else{
                  $is_update = false;
                  kernel::database()->rollBack();
                  break;
              }
              $tmp_obj = array();
       }
      if($is_update){
		 #赠品不对应的	
          if($is_fail){
              $str_bn = implode($diff_bn,',');
              $msg = 'CRM赠品'.$str_bn.'与ERP的赠品货号不对应,订单置为失败类型';
              $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
              #将订单状态改为失败订单
              $orderObj->update(array('is_fail'=>'true'),array('order_id'=>$order_id)); 
              $data_preprocess = array('preprocess_order_id'=>$order_id,'preprocess_type'=>'crm','preprocess_status'=>'1');
              $Obj_preprocess->save($data_preprocess);
              kernel::database()->commit();
              return true ;
          }
          $msg = '订单预处理CRM赠品完成';
          #记录CRM预处理完成状态
          $data_preprocess = array('preprocess_order_id'=>$order_id,'preprocess_type'=>'crm','preprocess_status'=>'1');
          $Obj_preprocess->save($data_preprocess);
          
          $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
          kernel::database()->commit();
          return true ;
      }else{
          kernel::database()->rollBack();

          $msg = '订单添加CRM赠品数据出错';
          $status = $order_info['abnormal_status'] | ome_preprocess_const::__HASCRMGIFT_CODE;
          #添加赠品出错的时候，继续审单发货
          if( $crm_cfg['error'] == 'off'){
              $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
              return false;
          }
          #添加赠品出错的时候，打标提醒，人工处理
          elseif( $crm_cfg['error'] == 'on'){
              $orderObj->update(array('abnormal_status'=>$status),array('order_id'=>$order_id));
              $operationLogObj->write_log('order_preprocess@ome',$order_id, $msg,time(),$opinfo);
              #打标与记日志完成后，程序返回
              return false;
          }
      }
    }
    #shopex前端店铺列表
    function shopex_shop_type(){
        $shop = array(
                'shopex_b2b'=>'shopex_b2b',
                'shopex_b2c'=>'shopex_b2c',
                'ecos.b2c'=>'ecos.b2c',
                'ecshop_b2c'=>'ecshop_b2c',
                'ecos.dzg'=>'ecos.dzg'
        );
        return $shop;
    }
}