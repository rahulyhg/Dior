<?php

class ome_mdl_return_product extends dbeav_model{

    var $defaultOrder = array('add_time DESC,return_id DESC');

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        if(isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn|head'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
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

    /* create_return_product 添加售后申请
     * @param sdf $sdf
     * @return sdf
     */
    function create_return_product(&$sdf){
        $this->save($sdf);
    }

    /*
     * 申请售后服务详情
     */
    function product_detail($return_id){
        $oProduct = &$this->app->model('products');
        $oProduct_items = &$this->app->model('return_process_items');
        $oPro_items = &$this->app->model('return_product_items');
        $oBranch = &$this->app->model('branch');
        $oMembers = &$this->app->model('members');
        $product_detail = $this->dump($return_id);//售后服务详情
        $product_detail['status_value']=$this->get_return_status($product_detail['status']);
        /*售后服务商品明细*/
        $product_detail['items']=$oPro_items->getList('*',array('return_id'=>$return_id));
        /*收货人信息*/
        foreach($product_detail['items'] as $k=>$v){
            $spec_info = $oProduct->dump($v['product_id'],'spec_info');
            $product_detail['items'][$k]['spec_info'] = $spec_info['spec_info'];
            $product_detail['items'][$k]['branch_name']=$oBranch->Get_name($v['branch_id']);
            $product_detail['items'][$k]['branch_id']=$v['branch_id'];
            $refund = $this->Get_delivery($v['branch_id'],$v['bn'],$product_detail['order_id']);
           
            $product_detail['items'][$k]['effective']=$refund['refund'];
        }
        $oProduct_delivery = &$this->app->model('delivery');
        $product_detail['delivery']=$oProduct_delivery->dump($product_detail['delivery_id'],'ship_area,ship_name,ship_addr,ship_zip,ship_tel,ship_email,ship_mobile');//收货人信息
       /*仓库信息*/
        $process_data=$product_detail['process_data']!='' ? unserialize($product_detail['process_data']):'';
        $product_detail['process_data']=$process_data;
       /**/
        /*已处理的申请商品*/

        $check_data=$oProduct_items->getList('bn,order_id,name,branch_id,product_id,item_id,is_problem,problem_type,problem_belong,store_type,memo,status,need_money,other',array('return_id'=>$product_detail['return_id'],'is_check'=>'true'));
        $Oorder_items = &$this->app->model('order_items');
        $Oproblem = &$this->app->model('return_product_problem');

        foreach($check_data as $k=>$v){
            $spec_info = $oProduct->dump($v['product_id'],'spec_info');
            $check_data[$k]['spec_info'] = $spec_info['spec_info'];
            $get_filter = array('order_id'=>$v['order_id'],'bn'=>$v['bn']);
            $Oorder_detail = $Oorder_items->dump($get_filter,'price');
            $check_data[$k]['price']=$Oorder_detail['price'];
            $problem_belong='';
            $problem_type='';

            $check_data[$k]['problem_belong']=$problem_belong;

            $problem_type = $v['problem_type'];
            $check_data[$k]['problem_type']=$problem_type;
            $refund = $this->Get_check_delivery($v['bn'],$product_detail['order_id']);
            $check_data[$k]['effective'] = $refund['refund'];
            $check_data[$k]['StoreType']=$Oproblem->get_store_type($v['store_type']);
        }

        $product_detail['check_data']=$check_data;

        /*日志列表*/
        $oOperation_log = &$this->app->model('operation_log');
        $oOperation = &$this->app->model('operations');
        $log_filter = array('obj_type'=>'return_product@ome','obj_id'=>$return_id,'obj_name'=>$product_detail['return_bn']);
        $product_detail['log']=$oOperation_log->read_log($log_filter,0,20,'log_id');
        $product_detail['member'] = $oMembers->dump($product_detail['member_id'],'uname,tel,zip,email,mobile');/*会员信息*/
        return $product_detail;
    }

    /*
     * 保存退货和成功状态
     * deal 状态1退货 2 拒绝 3 换货
     *      1.退货 生成一张退货单
     *      2.换货：生成一张退货单。和一张未付款的新订单
     *      3.拒绝是原样打回，生成一张发货单
     */
    function saveinfo($data,$api=FALSE){

        $oProduct_items = &$this->app->model('return_process_items');
        $oOperation_log = &$this->app->model('operation_log');//写日志
        $memo = '';
        $oProduct_detail = $this->dump($data['return_id'],'delivery_id,order_id');
        $bn = $data['bn'];
        $return_id=$data['return_id'];
        $reship_num=0;//退货单数量
        $order_num=0;//退货单数量
        $delivery_num=0;//发货单数量
        foreach($bn as $k => $v){

            //售后服务明细表数据
            $tmpData['item_id'] = $data['item_id'][$k];
            $tmpData['need_money'] = $data['need'][$k];
            $tmpData['other'] = isset($data['other'][$k])?$data['other'][$k]:0;
            if ($api==FALSE)
                $tmpData['status'] = $_POST['deal'.$k];
            else{
                $tmpData['status'] = $data['deal'.$k];
                $_POST['deal'.$k] = $data['deal'.$k];
            }
            $oProduct_items->save($tmpData);
            //退货单信息
            $memo='';
             $Process_data=array('bn'=>$v,'branch_id'=>$data['branch_id'][$k],'product_id'=>$data['product_id'][$k],'return_id'=> $return_id,'product_name'=>$data['name'][$k],'number'=>1,'order_id'=>$oProduct_detail['order_id'],'delivery_id'=>$oProduct_detail['delivery_id']);
             if($_POST['deal'.$k] == 1){
                    /*退货*/
                    $this->create_reship($Process_data);
                    $reship_num++;

                   //增加售后日志
            }elseif($_POST['deal'.$k] == 2){
                    $Order_data[] = array('bn'=>$v,'name'=>$data['name'][$k],'num'=>1);

                    $this->create_reship($Process_data);
                    //增加售后日志
                    $reship_num++;

            }elseif($_POST['deal'.$k] == 3){
                //danny_freeze_stock_log
                //define('FRST_OPER_ID','0');
                //define('FRST_OPER_NAME','');
                define('FRST_TRIGGER_OBJECT_TYPE','发货单：售后申请原样寄回生成发货单');
                define('FRST_TRIGGER_ACTION_TYPE','ome_mdl_return_product：saveinfo');
                 $new_delivery_bn = $this->create_delivery($Process_data);
                 $delivery_memo = '，发货单号为:'.$new_delivery_bn;
                //增加售后日志
                 $delivery_num++;
            }
       }
        /*当换货时生成一张订单,将接收过来同为换货的订单相同货号的数量累加*/
       if(!empty($Order_data)){
            //danny_freeze_stock_log
            //define('FRST_OPER_ID','0');
            //define('FRST_OPER_NAME','');
            define('FRST_TRIGGER_OBJECT_TYPE','订单：售后申请换货生成新订单');
            define('FRST_TRIGGER_ACTION_TYPE','ome_mdl_return_product：saveinfo');
           $new_order_id=$this->create_order($Order_data,$oProduct_detail['order_id']);
           $order_memo = '，订单号为:'.$new_order_id;
           $order_num++;
        }
       $aData['return_id'] = $data['return_id'];
       if($data['status']==4){
            if($data['totalmoney']==''){
                $aData['money']=$data['money'];
             }else{
                $aData['money']=$data['totalmoney'];
                $aData['tmoney']=$data['tmoney'];
                $aData['bmoney']=$data['bmoney'];
            }
            $aData['memo'] = $data['dealmemo'];
            $memo.='售后服务完成';
       }else{

            $aData['memo'] = $data['dealmemo'];
            $aData['money'] = $data['totalmoney'];
            $aData['tmoney']=$data['tmoney'];
            $aData['bmoney']=$data['bmoney'];
              //补差价
           //增加售后日志
            $memo.= '售后服务：补差价(￥'.-(float)$data['totalmoney'].')';
        }
        $aData['status'] = $data['status'];
        $aData['last_modified'] = time();
        $this->save($aData);
        /*日志描述start*/
        if($reship_num!=0){
            $memo.='   生成了'.$reship_num.'张退货单,';
        }
        if($order_num!=0){
            $memo.='   生成了'.$order_num.'张订单'.$order_memo;
        }
        if($delivery_num!=0){
            $memo.='   生成了'.$delivery_num.'张发货单'.$delivery_memo;
        }
        if($data['memo']!=''){
          $memo.='(处理备注：'.$data['memo'].')';
        }
         /*日志描述end*/
        $oOperation_log->write_log('return@ome',$return_id,$memo);

        if ($api==FALSE){
            //售后申请状态API
            foreach(kernel::servicelist('service.aftersale') as $object=>$instance){
                if(method_exists($instance,'update_status')){
                    $instance->update_status($aData['return_id']);
                }
            }
        }

        return $Process_data;
    }

    /*
    *  保存售后服务[申核中,通过申请]。
    */
    function tosave($adata,$api=FALSE){
        
        $status = $adata['status'];
        $oPro_items = &$this->app->model('return_product_items');
        $oOperation_log = &$this->app->model('operation_log');
        $choose_type_flag = $adata['choose_type_flag'];
        
        if($status==3 && $choose_type_flag){
         //接收数据，并做相应的操作类型转换
         $status_type = $adata['choose_type']?$adata['choose_type']:'1';
         

         $addmemo = $this->transform_return_type($adata['return_id'],$status_type,$adata);
        }
        $savedata = array('status'=>$adata['status'],'return_id'=>$adata['return_id'],'last_modified'=>time());
        
        $result=$this->save($savedata);
        $memo = '售后服务:'.$this->get_return_status($status).$addmemo;

        if($adata['memo']!=''){
            $memo.='(处理备注：'.$adata['memo'].')';
        }
        $oOperation_log->write_log('return@ome',$adata['return_id'],$memo);
        
        if ($api==FALSE){
            //售后申请状态API
//            foreach(kernel::servicelist('service.aftersale') as $object=>$instance){
//                if(method_exists($instance,'update_status')){
//                    $instance->update_status($adata['return_id']);
//                }
//            }
        }
        return $result;
     }


     /**
      * 接收数据，并做相应的操作类型转换
      *     status 1 退货单 2 换货单 3 退款申请单
      * @return void
      * @author
      **/
     function transform_return_type($return_id,$status,$post)
     {
        
        switch ($status) {
            case '1':
            case '2':
                $result = $this->create_treship($return_id,$status);
                break;
            case '3':
                $result = $this->create_refund_apply($post);
                break;
        }

        return $result;
     }

     /**
      * undocumented function
      *
      * @return void
      * @author
      **/
     function create_treship($return_id,$status)
     {
        $Oreship = $this->app->model('reship');
        $Oproduct = $this->app->model('return_product');
        
        $Oorder = $this->app->model('orders');
        $Oorderitems = $this->app->model('order_items');
        $Odelivery = $this->app->model('delivery');
        $returninfo = $Oproduct->dump(array('return_id'=>$return_id));
        //判断来源
        $source = $returninfo['source'];
        $archiveLib = kernel::single('archive_order');
        if ($archiveLib->is_archive($source)) {
            $Oorder = app::get('archive')->model('orders');
            $Oorderitems = app::get('archive')->model('order_items');
        }
        $orderinfo = $Oorder->dump(array('order_id'=>$returninfo['order_id']));
        $deliveryinfo = $Odelivery->dump(array('delivery_id'=>$returninfo['delivery_id']));
        $oDc = $this->app->model('dly_corp');
        $dc_data = $oDc->dump($orderinfo['logi_id']);
        $sdf = array(
            'order_id'=> $returninfo['order_id'],
            'delivery_id'=> $returninfo['delivery_id'],
            'member_id'=> $orderinfo['member_id'],
            'logi_name'=> $dc_data['name'],
            'logi_no'=> $orderinfo['logi_no'],
            'logi_id'=> $orderinfo['logi_id'],
            'ship_name'=> $orderinfo['consignee']['name'],
            'ship_area'=> $orderinfo['consignee']['area'],
            'delivery'=> $orderinfo['shipping']['shipping_name'],
            'ship_addr'=> $orderinfo['consignee']['addr'],
            'ship_zip'=> $orderinfo['consignee']['zip'],
            'ship_tel'=> $orderinfo['consignee']['telephone'],
            'ship_email'=> $orderinfo['consignee']['email'],
            'ship_mobile'=> $orderinfo['consignee']['mobile'],
            'is_protect'=> $orderinfo['shipping']['is_protect'],
            'memo'=> '',
            'return_id'=> $return_id,
            'source'=>$source,
        );
        $order_items = $Oorderitems->getList('product_id,price,sale_price,nums',array('order_id'=>$returninfo['order_id']));
       
        $tmp_items = array();
        foreach ($order_items as $key => $value) {
            $sale_price = 0;
            $sale_price = round($value['sale_price']/$value['nums'],2);
            $tmp_items[$value['product_id']] = $sale_price;
            unset($value);
        }

        $tmoney = 0;
        $Oproduct_items = $this->app->model('return_product_items');
        $pro_items = $Oproduct_items->getList('*',array('return_id'=>$return_id,'disabled'=>'false'));
        foreach($pro_items as $k=>$v){
            //$sdf['return']['item_id'][$v['bn']] = $v['item_id'];
            $sdf['return']['goods_bn'][$k] = $v['bn'];
            $sdf['return']['goods_name'][$v['bn']] = $v['name'];
            $sdf['return']['price'][$v['bn']] = $tmp_items[$v['product_id']];
            $sdf['return']['num'][$v['bn']] = $v['num'];
            $sdf['return']['branch_id'][$v['bn']] = $v['branch_id'];
            $sdf['return']['product_id'][$v['bn']] = $v['product_id'];

            $tmoney += ($v['num']*$tmp_items[$v['product_id']]);
        }

        $sdf['tmoney'] = $tmoney;

        $addmemo = '并生成一张';
        if($status == '1'){
            $sdf['return_type'] = 'return';
            $addmemo .='退货单';
        }else{
            $sdf['return_type'] = 'change';
            $addmemo .='换货单';
        }
       

        $reship_bn = $Oreship->create_treship($sdf,$msg);
        $addmemo .='. 单号为: '.$reship_bn;
        return $addmemo;
     }


      /**
       * 生成退款申请单
       *
       * @return void
       * @author
       **/
      function create_refund_apply($post)
      {

           $return = kernel::single('ome_refund_apply')->refund_apply_add($post,'1');
           if ($return['result'] == true){
                $result  = true;
           }else{
                $result = false;
           }

           return $return['msg'];
      }

     /*
      * 更新售后申请状态
      */
     function update_status($sdf){
           $this->save($sdf);
           //售后申请状态API
            foreach(kernel::servicelist('service.aftersale') as $object=>$instance){
                if(method_exists($instance,'update_status')){
                    $instance->update_status($sdf['return_id']);
                }
           }
     }


     /*
     * 获得售后服务状态
     * $param int $status
     */
     function get_return_status($status){
        $status_value=array (
            1 => '申请中',
            2 => '审核中',
            3 => '接受申请',
            4 => '完成',
            5 => '拒绝',
            6 => '已收货',
            7 => '已质检',
            8 => '补差价',
            9 => '已拒绝退款',
        );

         return $status_value[$status];
     }

  /*
   * 生成发货单
   * @param array $adata
   * return int
   */
   function create_delivery($adata)
   {

       $oDelivery = &$this->app->model('delivery');
       $odelivery_order = &$this->app->model('delivery_order');
       $odelivery_return = &$this->app->model('delivery_return');
       $delivery_id = $adata['delivery_id'];
       $delivery = $oDelivery->dump($delivery_id);

       $delivery_sdf=array(
           'branch_id'=>$adata['branch_id'],
           'is_protect'=>$delivery['is_protect'],
           'delivery' => $delivery['delivery'],
           'logi_id'=>$delivery['logi_id'],
           'logi_name'=>$delivery['logi_name'],
           'op_id'=>kernel::single('desktop_user')->get_id(),
           'create_time'=>time(),
           'type'=>'reject',
           'delivery_items' =>array(
               array(
                'product_id'=>$adata['product_id'],
                'bn' =>$adata['bn'],
                'product_name' =>$adata['product_name'],
                'number' => 1,

                ),
           )
        );
        $ship_info=array(
           'name' => $delivery['consignee']['name'],
           'area' => $delivery['consignee']['area'],
           'addr' => $delivery['consignee']['addr'],
           'zip' => $delivery['consignee']['zip'],
           'telephone' =>$delivery['consignee']['telephone'],
           'mobile' =>$delivery['consignee']['mobile'],
           'email' => $delivery['consignee']['email']
          );

        $result=$oDelivery->addDelivery($adata['order_id'],$delivery_sdf,$ship_info);
        $delivery_bn = $oDelivery->dump(array('delivery_id'=>$result),'delivery_bn');
        $delivery_bn = $delivery_bn['delivery_bn'];
        $deli_order_sdf = array(
            'order_id'=>$adata['order_id'],
            'delivery_id'=>$result
        );
        $odelivery_order->save($deli_order_sdf);
        $deli_return_sdf = array(
            'return_id'=>$adata['return_id'],
            'delivery_id'=>$result
        );
       $odelivery_return->save($deli_return_sdf);
       return $delivery_bn;
   }

  /*
   *生成退货单
   *并根据退货数量修改订单退货状态
   * @param array $adata
   * return int
  */
   function create_reship($adata){
       $oReship = &$this->app->model('reship');
       $oDelivery = &$this->app->model('delivery');
       $delivery = $oDelivery->dump($adata['delivery_id']);
       $reship_bn = $oReship->gen_id();
       $oReturn = $this->dump($adata['return_id'],'process_data');
       $process_data = unserialize($oReturn['process_data']);
       $process_data = $process_data[$adata['branch_id']];
       $process_data['shipmoney'] = $process_data['shipmoney'] ? $process_data['shipmoney'] : '0';
       $Reshipdata = array(
           'status' => 'succ',
           'reship_bn'=>$reship_bn,
           'bn'=>$adata['bn'],
           'branch_id'=>$adata['branch_id'],
           'is_protect'=>$delivery['is_protect'],
           'logi_id'=>$delivery['logi_id'],
           'logi_name'=>$process_data['shipcompany'],
           'logi_no'=>$process_data['shiplogino'],
           'money'=>$process_data['shipmoney'],
           't_begin'=>time(),
           't_end'=>time(),
           'weight'=>$delivery['weight'],
           'op_id'=>kernel::single('desktop_user')->get_id(),
           'return_id'=>$adata['return_id'],
           'order_id'=>$adata['order_id'],
           'shop_id'=>$delivery['shop_id'],
           'consignee'=>array(
               'name'=>$delivery['consignee']['name'],
               'addr'=>$delivery['consignee']['addr'],
               'zip'=>$delivery['consignee']['zip'],
               'telephone'=>$delivery['consignee']['telephone'],
               'mobile'=>$delivery['consignee']['mobile'],
               'email'=>$delivery['consignee']['email'],
               'area'=>$delivery['consignee']['area'],
            ),
           'reship_items'=>array(
               array(
                   'bn'=>$adata['bn'],
                   'product_name'=>$adata['product_name'],
                   'num'=>1
                   )
           )
        );
    if($oReship->save($Reshipdata)){
        //退货单创建 API

        foreach(kernel::servicelist('service.reship') as $object=>$instance){
            if(method_exists($instance,'reship')){
                $instance->reship($Reshipdata['reship_id']);
            }
        }

        //更新退货单状态
        foreach(kernel::servicelist('service.reship') as $object=>$instance){
            if(method_exists($instance,'update_status')){
                $instance->update_status($Reshipdata['reship_id']);
            }
        }

        $this->db->exec('UPDATE sdb_ome_order_items SET sendnum=sendnum-1 WHERE order_id='.$adata['order_id'].' AND bn=\''.$adata['bn'].'\'');
        $order_sum = $this->db->selectrow('SELECT sum(sendnum) as count FROM sdb_ome_order_items WHERE order_id='.$adata['order_id']);
        if($order_sum['count']==0){
            $ship_status=4;
        }else{
            $ship_status=3;
        }
       $this->db->exec('UPDATE sdb_ome_orders SET ship_status=\''.$ship_status.'\' WHERE order_id='.$adata['order_id']);

    }

   }

   /*
    *  售后服务生成订单
    *
    * @param array $adata ,int $order_id
    *
    * return $new_order_id
    */
   function create_order($adata,$order_id)
   {
        $oOrder = &$this->app->model('orders');
        $oitem = &$this->app->model('order_items');
        $oDelivery = &$this->app->model('delivery');
        $delivery = $oDelivery->dump($adata['delivery_id']);
        $oGoods = &$this->app->model('goods');
        $ret=array();
        foreach($adata as $k=>$v){
            if(isset($ret[$v['bn']])){
                $ret[$v['bn']]['num']++;
            }else{
                $ret[$v['bn']] = $v;
            }
        }
        $tostr='';
        $itemnum=0;
        foreach($ret as $k=>$v){
            $tostr.=$v['bn'].''.$v['name'].'('.$v['num'].')';
            $itemnum+=$v['num'];
        }
        $Order_detail = $oOrder->dump($order_id);
        $order_bn = $oOrder->gen_id();
        $order_sdf = array(
           'order_bn'=>$order_bn,
           'member_id'=>$Order_detail['member_id'],
            'currency'=>'CNY',
            'title'=>$tostr,
            'createtime'=>time(),
            'last_modified'=>time(),
            'confirm'=>'N',
            'status'=>'active',
            'pay_status'=>'0',
            'ship_status'=>'0',
            'is_delivery'=>'Y',
            'shop_id'=>$Order_detail['shop_id'],
            'itemnum'=>$itemnum,
            'shipping'=>array(
                'shipping_id'=>$Order_detail['shipping']['shipping_id'],
                'is_cod'=>'false',
                'shipping_name'=>$Order_detail['shipping']['shipping_name'],
                'cost_shipping'=>$Order_detail['shipping']['cost_shipping'],
                'is_protect'=>$Order_detail['shipping']['is_protect'],
                'cost_protect'=>$Order_detail['shipping']['cost_protect'],
            ),
           'consignee'=>array(
               'name'=>$Order_detail['consignee']['name'],
               'addr'=>$Order_detail['consignee']['addr'],
               'zip'=>$Order_detail['consignee']['zip'],
               'telephone'=>$Order_detail['consignee']['telephone'],
               'mobile'=>$Order_detail['consignee']['mobile'],
               'email'=>$Order_detail['consignee']['email'],
               'area'=>$Order_detail['consignee']['area'],
               'r_time'=>$Order_detail['consignee']['r_time'],
            ),
        );
            foreach($ret as $k1=>$v1){
                $goods = $oGoods->dump(array('bn'=>$v1['bn']),'goods_id');
                $item = $oitem->dump(array('order_id'=>$order_id,'bn'=>$v1['bn']),'*');
                $order_sdf['order_objects'][]=array(
                'obj_type'=> 'goods',  //goods,gift,taobao, api...
                'obj_alias'=> 'goods',  //goods,gift,taobao, api...
                'goods_id'=>$goods['goods_id']=='' ? 0:$goods['goods_id'],
                'bn'=>$v1['bn'],
                'name'=>$ret['name'],
                'price'=>$item['price'],
                'quantity'=>$ret['num'],
                'amount'=>$ret['num']*$item['price'],
                'weight'=>$item['weight']*$ret['num'],
                'order_items'=>array(
                        array(
                           'product_id'=>$item['product_id'],
                            'goods_id'=>$goods['goods_id']=='' ? 0:$goods['goods_id'],
                            'item_type'=>'product',
                            'bn'=>$v1['bn'],
                            'name'=>$v1['name'],
                            'quantity'=>$v1['num'],
                            'sendnum'=>0,
                            'amount'=>$v1['num']*$item['price'],
                            'price'=>$item['price'],
                            'weight'=>$item['weight']*$v1['num'],
                            'shop_product_id' => $item['shop_product_id'],
                            'shop_goods_id' => $item['shop_goods_id']
                          ),
                    ),
            );

             }
       $oOrder->create_order($order_sdf);

       return  $order_sdf['order_bn'];
   }

 /*
  * 获取已处理的申请商品 根据货号订单号获取发货单号以及对应收货相关信息
  * @param int $branch_id,int $order_id
  * return $array
  */
   function Get_check_delivery($bn,$order_id)
   {
        $sqlstr = "SELECT s.delivery_id,s.delivery_bn,s.ship_name,s.ship_area,s.ship_addr,sdi.bn,sum(sdi.number) as number FROM sdb_ome_delivery as s left join sdb_ome_delivery_items sdi on sdi.delivery_id = s.delivery_id left join sdb_ome_delivery_order as o on o.delivery_id=s.delivery_id WHERE o.order_id='$order_id' AND sdi.bn='$bn' AND s.type='normal' AND (s.parent_id=0 OR s.is_bind='true') AND s.status='succ' group by sdi.bn";

        $result=$this->db->selectrow($sqlstr);
        $result['refund'] = $result['number']-$this->Get_check_refund_num($bn,$order_id);

        return $result;
   }

 /*
    *获取已处理的申请商品 根据货号，订单号数量
    */
   function Get_check_refund_num($bn,$order_id)
   {
       $refund = $this->db->selectrow("SELECT sum(i.num) as count FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE r.order_id='".$order_id."' AND i.bn='".$bn."' group by i.bn");

       return $refund['count'];

   }


 /*
  * 根据仓库ID，货号订单号获取发货单号以及对应收货相关信息
  * @param int $branch_id,int $order_id
  * return $array
  */
   function Get_delivery($branch_id,$bn,$order_id)
   {
        #过滤合并发货单上总商品数量 ExBOY
        $sqlstr = "SELECT s.delivery_id,s.delivery_bn,s.ship_name,s.ship_area,s.ship_addr,sdi.bn,sum(sdi.number) as number FROM sdb_ome_delivery as s left join sdb_ome_delivery_items sdi on sdi.delivery_id = s.delivery_id left join sdb_ome_delivery_order as o on o.delivery_id=s.delivery_id WHERE o.order_id='$order_id' AND sdi.bn='$bn' AND s.branch_id='$branch_id' AND s.type='normal' AND s.is_bind='false' AND s.status='succ' group by sdi.bn";

        $result=$this->db->selectrow($sqlstr);

        $result['refund'] = $result['number']-$this->Get_refund_num($branch_id,$bn,$order_id);

        return $result;
   }
   /*
    *根据仓库，货号，订单号数量
    */
   function Get_refund_num($branch_id,$bn,$order_id)
   {
       $refund = $this->db->selectrow("SELECT sum(i.num) as count FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.is_check!='5' AND r.order_id='".$order_id."' AND i.bn='".$bn."' AND i.branch_id='".$branch_id."' group by i.bn");

       return $refund['count'];

   }
/*
 * 下载售后服务图片
 */
   function file_download($filename){
    $file = @ fopen($filename,"r");
    Header("Content-type: application/octet-stream");
    $file_name=substr($filename,strpos($filename,'upload'));
    Header("Content-Disposition: attachment; filename=" . $file_name);
    while (!feof ($file)) {
        echo fread($file,5000);
    }
    fclose ($file);
    }
    /*
    *根据订单和货号获取购买数量
    *@param int $order_id ,varchar $bn
    *return int
    */
    function get_order_count($order_id,$bn)
    {
        $sqlstr = "SELECT s.nums as count FROM sdb_ome_order_items as s WHERE s.order_id='$order_id' AND s.bn='$bn'";
        $result=$this->db->selectrow($sqlstr);
        return $result['count'];
    }
    /*外部记录流水号*/
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $return_bn = date('YmdH').'15'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT return_bn from sdb_ome_return_product where return_bn =\''.$return_bn.'\'');
        }while($row);
        return $return_bn;
    }

    /*
     * 根据订单号获取申请售后记录
     */
    function Get_aftersale_list($order_id){
        $market = $this->db->select('SELECT a.return_id,a.return_bn,a.status,l.op_name,l.operate_time from sdb_ome_return_product as a left join sdb_ome_operation_log as l on a.return_id=l.obj_id where a.order_id='.$order_id.' AND l.obj_type="return_product@ome" group by l.obj_id ORDER BY log_id DESC');
        foreach($market as $K=>$v){
           $market[$K]['status_value']=$this->get_return_status($v['status']);
        }
        return $market;
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

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function modifier_money($row)
    {
        $cur = app::get('eccommon')->model('currency');
        if ($row<0) {
            $c = $cur->changer(-1*$row);
            $row = '还需用户补款:<span style="color:#3333ff;">'.$c.'</span>';
        }else{
            $c = $cur->changer($row);
            $row = '需退还用户:<span style="color:red;">'.$c.'</span>';
        }

        return $row;
    }
    /**
     * 单据来源.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_source($row)
    {
        
        if ($row == 'local') {
            $row = '本地';
        }else if($row == 'matrix'){
           $row = '线上';
        }else if($row == 'archive'){
           $row = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', '归档', '归档', '归档');
        }else{
            $row = '-';
        }
        return $row;
    }
    
    /**
     * 售后原因
     * @param   
     * @return  string
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_content($row)
    {
        if ($row) {
            $reason = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'green', $row, $row, $row);
            return $reason;
        }
        
    }

    function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['return'] = array(
                    '*:退货记录流水号' => 'return_bn',
                    '*:订单号' => 'order_bn',
                    '*:退货记录标题' => 'title',
                    '*:最后合计金额' => 'money',
                    '*:最后更新时间' => 'last_modified',
                    '*:退款金额' => 'refundmoney',
                    '*:发货单号' => 'delivery_bn',
                   
                    '*:申请时间' => 'add_time',
                    '*:状态' => 'status',
                    '*:售后类型'=>'problem_id',
                    '*:是否收货'=>'recieved',
                    '*:是否质检'=>'verify',
                );
                
                break;
        }
        $this->ioTitle[$ioType]['return'] = array_keys( $this->oSchema[$ioType]['return'] );
        
        return $this->ioTitle[$ioType][$filter];
     }

     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        set_time_limit(0); // 30分钟
        $max_offset = 1000; // 最多一次导出10w条记录
        if( !$data['title']['return'] ){
            $title = array();
            foreach( $this->io_title('return') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['return'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;
        if( !$list=$this->getList('*',$filter,0,-1) )return false;
        $oOrders = &$this->app->model('orders');
        $oDelivery = &$this->app->model('delivery');
        $return_problem = self::return_product_problem();
        $returnRow = array();
        foreach ($list as  &$list) {
            $order_id = $list['order_id'];
            $delivery_id = $list['delivery_id'];
            $orders = $oOrders->dump($order_id,'order_bn');
            $delivery = $oDelivery->dump($delivery_id,'delivery_bn');
            $list['return_bn'] = $list['return_bn']."\t";
            $list['order_bn'] = $orders['order_bn']."\t";
            $list['delivery_bn'] = $delivery['delivery_bn']."\t";
            $list['recieved'] = $list['recieved']=='false' ? '否': '是';
            $list['verify'] = $list['verify']=='false' ? '否': '是';
            $list['add_time'] = date('Y-m-d H:i:s',$list['add_time']);
            $list['last_modified'] = date('Y-m-d H:i:s',$list['last_modified']);
            
            $list['status'] = self::return_status($list['status']);
            $list['problem_id'] = $return_problem[$list['problem_id']];
            foreach( $this->oSchema['csv']['return'] as $k => $v ){
                $returnRow[$k] = $this->charset->utf2local(utils::apath( $list,explode('/',$v) ));
            }
            $data['content']['return'][] = '"'.implode('","',$returnRow).'"';
        }
        return false;
     }

     function export_csv($data,$exportType = 1 ){
        $output = array();
        //if( $exportType == 2 ){
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        //}
        echo implode("\n",$output);
    }

    
    /**
     * 售后状态值
     * @param   status    状态值
     * @return  type    状态值
     */
    static function return_status($status)
    {
        $return_status = array (
            1 => '申请中',
            2 => '审核中',
            3 => '接受申请',
            4 => '完成',
            5 => '拒绝',
            6 => '已收货',
            7 => '已质检',
            8 => '补差价',
            9 => '已拒绝退款',
        );
        return $return_status[$status];
    } // end func

     
    /**
     * 售后问题类型
     * @param  
     * @return  
     * @access  public
     * @
     */
     function return_product_problem()
    {
        $oProblem = &$this->app->model('return_product_problem');
        $problem = $oProblem->getCatList();
        $problem_list = array();
        foreach ($problem as  $problem) {
            $problem_list[$problem['problem_id']] = $problem['problem_name'];
        }
        return $problem_list;
    } // end func

  
}
?>