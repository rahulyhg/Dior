<?php

class ome_mdl_reship extends dbeav_model{
    //是否有导出配置
    var $has_export_cnf = true;

    var $export_name = '退换货单';

    var $has_many = array(
       'reship_items' => 'reship_items'
    );
    //所用户信息
    static $__USERS = null;
    var $defaultOrder = array('t_begin DESC,reship_id DESC');

    var $is_check = array(
        0 => '未审核',
        1 => '审核成功',
        2 => '审核失败',
        3 => '收货成功',
        4 => '拒绝收货',
        5 => '拒绝',
        6 => '补差价',
        7 => '完成',
        8 => '质检通过',
        9 => '拒绝质检',
        10 => '质检异常',
      );
    private $expert_flag= false;
    #售后类型
    private $return_type = array (
            'return' => '退货',
            'change' => '换货',
            'refuse' => '拒收退货'
    );

    public function _filter($filter,$tableAlias=null,$baseWhere=null){

        if (isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn|head'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $archorderObj = app::get('archive')->model('orders');
            $archorder = $archorderObj->getList('order_id',array('order_bn'=>$filter['order_bn']));
            foreach ($archorder as $arc ) {
                $orderId[] = $arc['order_id'];
            }
            $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['order_bn']);

        }

        if (isset($filter['bn'])) {
            $reshipItemModel = $this->app->model('reship_items');
            $rows = $reshipItemModel->getList('DISTINCT reship_id',array('bn|head'=>$filter['bn']));
            $reship_ids = array(0);
            foreach ($rows as $row) {
                $reship_ids[] = $row['reship_id'];
            }
            $where .=' AND reship_id IN('.implode(',',$reship_ids).')';
            unset($filter['bn']);
        }

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    /*
     * 获取退货单明细列表
     *
     * @param int $order_id 订单id
     *
     * @return array
     */
    function getItemList($reship_id){
        $reship_items = array();
        $items = $this->dump($reship_id,"reship_id",array("reship_items"=>array("*")));
        if($items['reship_items']){
            $reship_items = $items['reship_items'];
        }

        return $reship_items;
    }

    /*
     * 生成退货单号
     *
     *
     * @return 退货单号
     */
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $reship_bn = date("YmdH").'13'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('select reship_bn from sdb_ome_reship where reship_bn =\''.$reship_bn.'\'');
        }while($row);
        return $reship_bn;
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'order_bn'=>app::get('base')->_('订单号'),
            'bn' => '货号',
        );
        return $Options = array_merge($childOptions,$parentOptions);
    }

  /**
   * 创建退发货单
   *
   * @return void
   * @author
   **/
  function create_treship($adata,&$msg)
  {
      
      if($adata['delivery_id']) {
        if ($adata['source'] == 'archive'){
                $oDelivery = app::get('archive')->model('delivery');
            }else{
                $oDelivery = &$this->app->model('delivery');
            }
            $delivery = $oDelivery->dump($adata['delivery_id']);
      }
      $opInfo = kernel::single('ome_func')->getDesktopUser();
       if ($adata['branch_id']) {
          $branch_id = $adata['branch_id'];
       }else{
          if ($adata['return']['branch_id']) {
              $return_branch = $adata['return']['branch_id'];
              $branch_id = current($return_branch);
          }
      }
      $sdf_data = array(
        'return_id'        => $adata['return_id'],
        'reship_id'        => $adata['reship_id'],
        'order_id'         => $adata['order_id'],
        'member_id'        => $adata['member_id'],
        'return_logi_name' => $adata['return_logi_name'],
        'return_type'      => $adata['return_type'],
        'return_logi_no'   => $adata['return_logi_no'],
        'logi_name'        => $adata['logi_name'],
        'logi_no'          => $adata['logi_no'],
        'logi_id'          => $adata['logi_id'],
        'ship_name'        => $adata['ship_name'],
        'ship_area'        => $adata['ship_area'],
        'delivery'         => $adata['delivery'],
        'ship_addr'        => $adata['ship_addr'],
        'ship_zip'         => $adata['ship_zip'],
        'ship_tel'         => $adata['ship_tel'],
        'ship_email'       => $adata['ship_email'],
        'ship_mobile'      => $adata['ship_mobile'],
        'memo'             => $adata['memo'],
        'status'           => 'ready',
        'op_id'            => $opInfo['op_id'],
        't_end'            =>0,
        'logi_id'          => $adata['logi_id'],                                                        //物流ID
        'is_protect'       => ($adata['is_protect'] ? $adata['is_protect'] : $delivery['is_protect']),  //是否报价
        'return'           => $adata['return'],
        'change'           => $adata['change'],
        'reship_bn'        => ( $adata['reship_bn'] ? $adata['reship_bn'] : $this->gen_id() ),
        'shop_id'          => ( $adata['shop_id'] ? $adata['shop_id'] : $delivery['shop_id'] ),
        'problem_id'       => $adata['problem_type'][0],
        'branch_id'=>$branch_id,
		'return_reason'    => $adata['return_reason'],
      );
	
     if ($adata['source'] == 'archive') {
          $sdf_data['archive'] = '1';
          $sdf_data['source'] = 'archive';
      }
      if(empty($sdf_data['shop_id'])) {
        $msg = '店铺信息为空!'; return false;
      }
      $oShop = &$this->app->model ( 'shop' );
      $sdf_data['shop_type'] = $oShop->getShoptype($sdf_data['shop_id']);
      if($sdf_data['reship_id'] == ''){
          $sdf_data['t_begin'] = time();
      }
      if($sdf_data['reship_id']) {
          $reshipObj =&$this->app->model ( 'reship' );
          //判断是否为待确认，换货改为退货类型
          $reship_detail = $reshipObj->dump($sdf_data['reship_id'],'return_type,is_check,need_sv');
          if ($reship_detail['return_type'] =='change' && $reship_detail['is_check']=='11' && $reship_detail['need_sv']=='true') {
               if($sdf_data['return_type']=='return'){
                   //释放冻结库存
                   kernel::single('console_reship')->change_freezeproduct($sdf_data['reship_id'],'-');
               }
              
          }
      }
      $return = $sdf_data['return'];
      if ($branch_id) {
          unset($return['branch_id']);
        $return['branch_id'] = $branch_id;
      }
      
      $change = $sdf_data['change'];
      unset($sdf_data['return'],$sdf_data['change']);

      if($this->save($sdf_data)){
        # 保存退换货单明细
        $oReship_items = $this->app->model('reship_items');
        $SQL = 'DELETE FROM sdb_ome_reship_items WHERE reship_id='.$sdf_data['reship_id'].' AND (defective_num=0 AND normal_num=0)';
        $oReship_items->db->exec($SQL);

        $result = $this->save_product_items($return,$sdf_data['reship_id'],$oReship_items,'return',$sdf_data['return_id']);
        if ($result['status'] != 'succ') {
          $msg = $result['msg']; return false;
        }
        if ($sdf_data['return_type'] == 'change') {
          $result = $this->save_product_items($change,$sdf_data['reship_id'],$oReship_items,'change');
          if ($result['status'] != 'succ') {
            $msg = $result['msg']; return false;
          }
        }

        # 操作日志
        $oOperation_log = &$this->app->model('operation_log');
        $add_operation = $adata['reship_bn'] ? '编辑' : '新建';
        $memo = $add_operation.'退换货单,单号为:'.$sdf_data['reship_bn'];
        $oOperation_log->write_log('reship@ome',$sdf_data['reship_id'],$memo);

        /*
        $reship_items = $oReship_items->getList('price,num',array('reship_id'=>$sdf_data['reship_id'],'return_type'=>'return'));
        $tmoney = 0;
        if($adata['tmoney']){
          $tmoney = $adata['tmoney'];
        }else{
          foreach($return['goods_bn'] as $key => $bn){
              $tmoney += ($return['num'][$bn] * $return['price'][$bn]);
          }
        }*/

        //为了售后问题类型的统计添加的字段(problem_id)，并且给该字段赋值
        //begin
        if($sdf_data['return_id']){
            $oProduct = &$this->app->model('return_product');
            $oProduct_problem_id = array(
                'return_id'  => $sdf_data['return_id'],
                'tmoney'     => $tmoney,
                'problem_id' => $adata['problem_type'][0],
            );
            $oProduct->save($oProduct_problem_id);

            //售后申请状态API
            /*
            $product_info = $oProduct->dump(array('return_id'=>$sdf_data['return_id']), 'status');
            $data = array ('return_id' => $sdf_data['return_id'], 'status' => $product_info['status']);
            $oProduct->save($data);*/
        }

        /*
        $oReship_problem_id = array(
            'reship_id'=>$sdf_data['reship_id'],
            'tmoney'=>$tmoney,
            'problem_id'=>$adata['problem_type'][0],
        );
        $this->save($oReship_problem_id);
        */
		// 同步退货单信息到AX
		/*$objDeliveryOrder = app::get('ome')->model('delivery_order');
		$delivery_id = $objDeliveryOrder->getList('*',array('order_id'=>$sdf_data['order_id']));
		//echo "<pre>";print_r($delivery_id);exit;
		$delivery_id = array_reverse($delivery_id);
		kernel::single('omeftp_service_reship')->delivery($delivery_id[0]['delivery_id'],$sdf_data['reship_id']);*/
        $msg = '新建退换货单成功，请等待审核!';
        return $sdf_data['reship_bn'];
      }else{
        $msg = '新建退换货单失败.';
        return false;
      }
  }

  /**
   * 保存退货明细
   * 将退入，换出商品分别存入reship_items表中
   * @param array $param ,$type
   * @return void
   * @author
   **/
  function save_product_items($param,$reship_id,$object,$type = 'return',$return_id='')
  {
    $rs = array('status'=>'fail','msg'=>'保存失败！');

    $opInfo = kernel::single('ome_func')->getDesktopUser();
    $oReturn_items = $this->app->model('return_product_items');

    # 保存退货及已有的换货明细
    if ($param['goods_bn'] && is_array($param['goods_bn']) ) {
      $reship_orginal_items = array();
      foreach ($param['goods_bn'] as $key => $bn) {
        $item = array(
          'reship_id'    => $reship_id,
          'product_name' => $param['goods_name'][$bn],
          'bn'           => $bn,
          'num'          => $param['num'][$bn],
          'product_id'   => $param['product_id'][$bn],
          'price'        => $param['price'][$bn],
          'return_type'  => $type,
          //'branch_id'    => $param['branch_id'],
          'op_id'        => $opInfo['op_id'],
          'item_id'      => $param['item_id'][$bn],
        );
        if ($type == 'return') {
            $item['branch_id'] = $param['branch_id'];
        }else{
           $item['branch_id'] = $param['branch_id'][$bn];
        }
        $result = $object->save($item);
        if (!$result) {
          return array('status'=>'fail','msg'=>'插入退货商品【'.$bn.'】时失败！');
        }

        if ($type == 'return' && $return_id) {
          $updateData = array('num' => $item['num']);
          $updateFilter = array('return_id'=>$return_id,'product_id'=>$item['product_id']);
          $oReturn_items->update($updateData,$updateFilter);
        }

        $rs = array('status'=>'succ','msg'=>'保存成功！');
      }
    }

    # 换货新增商品
    $productModel = $this->app->model('products');
    if ($param['product']['bn'] && is_array($param['product']['bn'])) {
      $reship_new_items = array();
      foreach ($param['product']['bn'] as $key => $bn) {
        $item = array(
          'bn'          => $bn,
          'price'       => $param['product']['price'][$bn],
          'num'         => $param['product']['num'][$bn],
          'branch_id'   => $param['product']['branch_id'][$bn],
          'product_id'  => $param['product']['product_id'][$bn],
          'return_type' => $type,
          'op_id'       => $opInfo['op_id'],
          'reship_id'   => $reship_id,
        );

        $product = $productModel->getList('bn,name',array('product_id'=>$item['product_id']),0,1);
        if(!$product){
          return array('status'=>'fail','msg'=>'插入换货商品【'.$bn.'】时失败：商品不存在！');
        }
        $item['product_name'] = $product[0]['name'];

        $result = $object->save($item);
        if ( !$result ) {
          return array('status'=>'fail','msg'=>'插入换货商品【'.$bn.'】时失败！');
        }

        $rs = array('status'=>'succ','msg'=>'保存成功');
      }
    }

    return $rs;
  }


    /**
     * 获取售后明细
     *
     * @return void
     * @author
     **/
    function getReshipItems($reship_id)
    {
      $Oreships = &$this->app->model('reship_items');
      $oOrders = $this->app->model('orders');
      $Oreship_items = $Oreships->getList('*',array('reship_id'=>$reship_id),0,1);
      $reshipitems = $this->dump(array('reship_id'=>$reship_id),'*');
      $orders = $oOrders->dump($reshipitems['order_id'],'order_bn');
      if (!$orders['order_bn']) {
          $archiveObj = app::get('archive')->model('orders');
          $orders = $archiveObj->dump($reshipitems['order_id'],'order_bn');
      }
      $reshipitems['order_bn'] =$orders['order_bn'];
      $reshipitems['items'][] = $Oreship_items[0];
      return $reshipitems;
    }

   /**
    * 获取审核发货单信息
    *
    * @return void
    * @author
    **/
   function getCheckinfo($reship_id,$transform=true)
   {

        $oOrders = &$this->app->model ('orders');
        $oProducts = &$this->app->model ('products');
        $oMember = $this->app->model('members');
        $oDc = $this->app->model('dly_corp');
        $oReship_item = &$this->app->model ( 'reship_items' );
        $Oreturn_products = $this->app->model('return_product');
        $reship_data = $this->dump(array('reship_id'=>$reship_id));
        $reship_data['return_logi_id'] = $reship_data['return_logi_name'];
        $dc_data = $oDc->dump($reship_data['return_logi_name']);
        $order_data = $oOrders->dump($reship_data['order_id']);
        if ($reship_data['archive']=='1' || ($reship_data['source'] && in_array($reship_data['source'],array('archive')))) {
            $oReship_item = $oOrders = app::get('archive')->model ('orders');
            
            $order_data = $oOrders->dump(array('order_id'=>$reship_data['order_id'],'flag'=>1));
            unset($order_data['source']);
        }
        
        $member = $oMember->dump(array('member_id'=>$order_data['member_id']));
        $oBranch=&$this->app->model('branch');
        if($transform){
           $reship_data['return_logi_name'] = $dc_data['name'];
           $rd = explode(':', $reship_data['ship_area']);
           if($rd[1]){
             $reship_data['ship_area'] = str_replace('/', '-', $rd[1]);
           }
        }

        $reship_item = $this->getItemList($reship_id);
        $reship_data = array_merge($reship_data,$order_data);
        $rp = $Oreturn_products->dump(array('return_id'=>$reship_data['return_id']));
        $reship_data['title'] = $rp['title'];
        $reship_data['member_id'] = $member['account']['uname'];
        $reship_data['content'] = $rp['content'];
        $reship_data['return_memo'] = $rp['memo'];
        if ($reship_data['branch_id']) {
            $branchs = $oBranch->db->selectrow("SELECT name,branch_id FROM sdb_ome_branch WHERE branch_id=".$reship_data['branch_id']."");
            $reship_data['branch_name'] = $branchs['name'];
            unset($branchs);
        }
        if($reship_item){
          $recover = array(); $tmoney = 0;
          
          foreach ($reship_item as $key => $value) {
            $branchs = $oBranch->db->selectrow("SELECT name,branch_id FROM sdb_ome_branch WHERE branch_id=".$value['branch_id']."");
            $reship_item[$key]['branch_id'] = $branchs['branch_id'];
            $reship_item[$key]['branch_name'] = $branchs['name'];

            $product = $oProducts->dump($value['product_id'],'product_id,spec_info');
            $reship_item[$key]['spec_info'] = $product['spec_info'];

            if($value['return_type'] == 'return'){
                 $refund = $oReship_item->Get_refund_count( $reship_data['order_id'], $value['bn'] ,$reship_id);
                 $reship_item[$key]['effective'] = $refund;
                 $recover['return'][] = $reship_item[$key];
                 $recover['total_return_filter'][] = $product['product_id'];

                 # 计算应退金额
                 $tmoney += $value['price'] * $value['num'];
            }else{
                //作判断如果是待确认时,审核剩余数量不减冻结
                $refund=0;
                 $refund = $oProducts->get_product_store( $value['branch_id'],$value['product_id'] );
                 if ($reship_data['is_check'] == '11' && $value['return_type'] == 'change') {
                    $refund+=$value['num'];
                }
                 $reship_item[$key]['effective'] = $refund;
                 $recover['change'][] = $reship_item[$key];
                 $recover['total_change_filter'][] = $product['product_id'];
            }
          }

          $reship_data = array_merge($reship_data,$recover);
          //$reship_data['tmoney'] = ($reship_data['tmoney']!='0.000')?$reship_data['tmoney']:$reship_data['total_amount'];
          $reship_data['tmoney'] = kernel::single('eccommon_math')->getOperationNumber($tmoney);

          $reship_data['total_return_filter'] = implode(',', $reship_data['total_return_filter']);
          $reship_data['total_change_filter'] = implode(',', $reship_data['total_change_filter']);
        }

        return $reship_data;
   }


    public function modifier_return_logi_name( $val ) {
        $oDc = $this->app->model('dly_corp');
        $dc_data = $oDc->dump($val);
        if($dc_data['name']){;
          return $dc_data['name'];
        }else{
          return $val;
        }
    }


    /**
     * 保存入库单信息
     * status 状态：
     *       5: 拒绝 生成一张发货单 商品明细为退入商品中的商品信息
     *       6：补差价，生成一张未付款的支付单
     *       8: 操作完成
     * @return void
     * @author
     **/
    public function saveinfo($reship_id,$data,$status,$api=false){

        $memo = '';
        $reship_num=0;//退货单数量
        $order_num=0;//退货单数量
        $oDc = $this->app->model('dly_corp');
        $Oreturn_products = $this->app->model('return_product');
        $reshipinfo = $this->dump(array('reship_id'=>$reship_id),'*');
        $oReship_items = $this->app->model('reship_items');
        $reship_items = $oReship_items->getList('*',array('reship_id'=>$reship_id));
        $reshipinfo['return_logi_id'] = $reshipinfo['return_logi_id'];
        $dc_data = $oDc->dump($reshipinfo['return_logi_name']);
        $reshipinfo['return_logi_name'] = $dc_data['name'];

        switch ($status){
          case '6':
            $aData['return_id'] = $data['return_id'];
            $aData['reship_id'] = $reship_id;
            $aData['memo'] = $data['dealmemo'];
            $aData['money'] = $data['totalmoney'];
            $aData['tmoney']=$data['tmoney'];
            $aData['bmoney']=$data['bmoney'];
            $aData['is_check']='6';
            //补差价
            //增加售后日志
            $memo.= '售后服务：补差价(￥'.-(float)$data['totalmoney'].')';
            $this->update($aData,array('reship_id'=>$reship_id));
            if($reshipinfo['return_id']){
                unset($aData['reship_id'],$aData['is_check']);
                $aData['status']='8';
                $Oreturn_products->update($aData,array('return_id'=>$reshipinfo['return_id']));
            }

          break;
        }



        /*日志描述start*/
        if($reship_num!=0){
            $memo.='   生成了'.$reship_num.'张退货单,';
        }
        if($order_num!=0){
            $memo.='   生成了'.$order_num.'张订单'.$order_memo;
        }
        $oOperation_log = &$this->app->model('operation_log');//写日志
        if($data['return_id']){
           $oOperation_log->write_log('return@ome',$data['return_id'],$memo);
        }
        $oOperation_log->write_log('reship@ome',$reship_id,$memo);
        return true;
    }

  /*
   * 生成发货单
   * @param array $adata
   * return int
   */
   function create_delivery($adata)
   {

       $oDelivery = &$this->app->model('delivery');
       $oProducts = &$this->app->model('products');
       $product_info = $oProducts->dump(array('bn'=>$adata['bn']),'product_id');
       $delivery_sdf=array(
           'branch_id'=>$adata['branch_id'],
           'is_protect'=>$adata['is_protect'],
           'delivery' => $adata['delivery'],
           'logi_id'=>$adata['logi_id'],
           'logi_name'=>$adata['logi_name'],
           'op_id'=>kernel::single('desktop_user')->get_id(),
           'create_time'=>time(),
           'delivery_cost_actual' => $adata['delivery_cost_actual'] ? $adata['delivery_cost_actual'] : 0,
           'type'=>'reject',
           'delivery_items' =>$adata['delivery_items'],
        );
       $adata['ship_area'] = str_replace('-', '/', $adata['ship_area']);
       kernel::single('eccommon_regions')->region_validate($adata['ship_area']);
        $ship_info=array(
           'name' => $adata['ship_name'],
           'area' => $adata['ship_area'],
           'addr' => $adata['ship_addr'],
           'zip' => $adata['ship_zip'],
           'telephone' =>$adata['ship_tel'],
           'mobile' =>$adata['ship_mobile'],
           'email' => $adata['ship_email']
          );

        $result=$oDelivery->addDelivery($adata['order_id'],$delivery_sdf,$ship_info);
        $delivery_bn = $oDelivery->dump(array('delivery_id'=>$result),'delivery_bn');
        $delivery_bn = $delivery_bn['delivery_bn'];

        return $delivery_bn;
   }

   /*
    *  售后服务生成订单
    *
    * @param $reshipinfo 退换货单信息,退换货单商品信息
    *
    * return $new_order_id
    */
   function create_order($reshipinfo,$reship_items)
   {

        $oOrder = &$this->app->model('orders');
        $oitem = &$this->app->model('order_items');
        $oGoods = &$this->app->model('goods');
        $oProducts = &$this->app->model('products');
        $ret=array();
        $i=0;
        foreach($reship_items as $k=>$v){
            if($v['return_type'] == 'change'){
                $ret[$i]['bn'] = $v['bn'];
                $ret[$i]['name'] = $v['product_name'];
                $ret[$i]['num'] = $v['num'];
                $ret[$i]['price'] = $v['price'];
                $i++;
            }
        }
        $tostr='';
        $itemnum=0;
        foreach($ret as $k=>$v){
            $tostr.=$v['bn'].''.$v['name'].'('.$v['num'].')';
            $itemnum+=$v['num'];
        }//reshipinfo

        $Order_detail = $oOrder->dump($reshipinfo['order_id']);

        if($reshipinfo['ship_area']!=''){
           $reshipinfo['ship_area'] = str_replace('-', '/', $reshipinfo['ship_area']);
           kernel::single('eccommon_regions')->region_validate($reshipinfo['ship_area']);
           $ship_area = $reshipinfo['ship_area'];
        }else{
           $ship_area = $Order_detail['consignee']['area'];
        }

        $order_bn = $oOrder->gen_id();
        
        
        if ($reshipinfo['source'] == 'archive') {
            $oOrder = app::get('archive')->model('orders');
            $oitem = app::get('archive')->model('order_items');
        }
        $Order_detail = $oOrder->dump($reshipinfo['order_id']);
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
            'is_delivery'=>'N',
            'shop_id'=>$reshipinfo['shop_id'],
            'itemnum'=>$itemnum,
            'shipping'=>array(
                'shipping_id'=>$Order_detail['shipping']['shipping_id'],
                'is_cod'=>'false',
                'shipping_name'=>$Order_detail['shipping']['shipping_name'],
                'cost_shipping'=>$reshipinfo['cost_freight_money'],
                'is_protect'=>$Order_detail['shipping']['is_protect'],
                'cost_protect'=>0,
            ),
           'consignee'=>array(
               'name'=>$reshipinfo['ship_name']  ? $reshipinfo['ship_name'] :$Order_detail['consignee']['name'],
               'addr'=>($reshipinfo['ship_addr']!='')?$reshipinfo['ship_addr']:$Order_detail['consignee']['addr'],
               'zip'=>($reshipinfo['ship_zip']!='')?$reshipinfo['ship_zip']:$Order_detail['consignee']['zip'],
               'telephone'=>($reshipinfo['ship_tel']!='')?$reshipinfo['ship_tel']:$Order_detail['consignee']['telephone'],
               'mobile'=>($reshipinfo['ship_mobile']!='')?$reshipinfo['ship_mobile']:$Order_detail['consignee']['mobile'],
               'email'=>($reshipinfo['ship_email']!='')?$reshipinfo['ship_email']:$Order_detail['consignee']['email'],
               'area'=>$ship_area,
               'r_time'=>$Order_detail['consignee']['r_time'],
            ),
            'mark_type' => 'b1',
            'source' => 'local',
            'createway' => 'after',
        );
        $mark_text = array(
          array(
            'op_name' => 'system',
            'op_time' => time(),
            'op_content' => '售后换货，创建的换出订单。要求换货的订单('.$Order_detail['order_bn'].')',
          ),
        );
        if ($reshipinfo['memo']) {
          $user = app::get('desktop')->model('users')->getList('name',array('user_id' => $reshipinfo['op_id']),0,1);
          $mark_text[] = array(
            'op_name' => $user[0]['name'],
            'op_time' => time(),
            'op_content' => $reshipinfo['memo'],
          );
        }
        $order_sdf['mark_text'] = $mark_text;

            foreach($ret as $k1=>$v1){
                $goods = $oGoods->dump(array('bn'=>$v1['bn']),'goods_id');
                $item = $oProducts->dump(array('bn'=>$v1['bn']),'price,weight,product_id');
                $item['price'] = $v1['price']?$v1['price']:$item['price'];
                $order_sdf['order_objects'][]=array(
                    'obj_type'=> 'goods',  //goods,gift,taobao, api...
                    'obj_alias'=> 'goods',  //goods,gift,taobao, api...
                    'goods_id'=>$goods['goods_id']=='' ? 0:$goods['goods_id'],
                    'bn'=>$v1['bn'],
                    'name'=>$v1['name'],
                    'price'=>$item['price'],
                    'quantity'=>$v1['num'],
                    'pmt_price'=>0,
                    'sale_price'=>$v1['num']*$item['price'],
                    'amount'=>$v1['num']*$item['price'],
                    'weight'=>$item['weight']*$v1['num'],
                    'order_items'=>array(
                            array(
                               'product_id'=>$item['product_id'],
                                'goods_id'=>$goods['goods_id']=='' ? 0:$goods['goods_id'],
                                'item_type'=>'product',
                                'bn'=>$v1['bn'],
                                'name'=>$v1['name'],
                                'quantity'=>$v1['num'],
                                'pmt_price'=>0,
                                'sale_price'=>$v1['num']*$item['price'],
                                'sendnum'=>0,
                                'amount'=>$v1['num']*$item['price'],
                                'price'=>$item['price'],
                                'weight'=>$item['weight']*$v1['num'],
                            ),
                    ),
                );
              $item_cost += $v1['num']*$item['price'];
            }
       $order_sdf['total_amount'] = $item_cost+$order_sdf['shipping']['cost_shipping']+$order_sdf['shipping']['cost_protect'];
       $order_sdf['final_amount'] = $order_sdf['total_amount'];

       $order_sdf['cost_item']    = $item_cost;

       $result =  &$this->app->model('orders')->create_order($order_sdf);

       return  $result ? $order_sdf : false;
   }

    /*
     * 数据验证
     * param $data 需校验的参数
     * param $v_msg 返回信息
     */
    function validate($data,&$v_msg){
        $v_msg = '';
        $type_return = $data['return'];
        $type_change = $data['change'];
        $return_c = count($type_return['goods_bn']);
        $change_c = count($type_change['goods_bn']);

        if( $return_c == 0 ){
          $v_msg = '请选择至少一个退入商品。';
          return false;
        }

        if($data['return_type'] == 'change'&& ($change_c == 0 && ($type_change['product']['product_id']=='')) ){
          $v_msg = '请选择至少一个换出商品。';
          return false;
        }

        if($type_return['goods_bn']){
           foreach ($type_return['goods_bn'] as $key => $value) {
              if($type_return['effective'][$value] < 1){
                  $v_msg = '退入商品中货号为:'.$value.'商品申请数量小于0，申请被拒绝!';
                  return false;
              }
              if ($data['is_check'] == '11') {
                  if ($type_return['normal_num'][$value] > $type_return['effective'][$value]) {
                  $v_msg = '货号【'.$value.'】的入库数量超出可退入数量，申请被人拒绝!';
                  return false;
                  }
              }else{
                  if ($type_return['num'][$value] > $type_return['effective'][$value]) {
                  $v_msg = '货号【'.$value.'】的申请数量超出可退入数量，申请被人拒绝!';
                  return false;
                  }
              }
              

              if ($type_return['num'][$value]<=0) {
                  $v_msg = '货号【'.$value.'】的申请数量必须大于0!';
                  return false;
              }
           }
        }

        if($data['return_type'] == 'change'&&$type_change['goods_bn']){
           foreach ($type_change['goods_bn'] as $key => $value) {
              if($type_change['effective'][$value] < 1){
                  $v_msg = '换出商品中货号为:'.$value.'商品申请数量小于0，申请被拒绝';
                  return false;
              }
           }
        }


        if($data['return_type'] == 'change' && is_array($type_change['product']['bn'])){
          foreach($type_change['product']['bn'] as $k=>$v){

            if($type_change['product']['num'][$v] < 1){
                $v_msg = '换出商品中,货号为:['.$v.']申请数量为0，申请被拒绝。';
                return false;
            }

            if($type_change['product']['sale_store'][$v] < 1){
                $v_msg = '换出商品中,货号为:['.$v.']实际的库存为0，申请被拒绝。';
                return false;
            }

            if( ($type_change['product']['sale_store'][$v] > 0) && ($type_change['product']['num'][$v] > $type_change['product']['sale_store'][$v])){
                $v_msg = '换出商品中,货号为:['.$v.']申请数量大于实际的库存。申请被拒绝。';
                return false;
            }
          }
        }

        #  判断补差价 chenping
        if ($data['diff_order_bn']) {
          $order = $this->app->model('orders')->select()->columns('order_id')
                    ->where('order_bn=?',$data['diff_order_bn'])
                    ->where('pay_status=?','1')
                    ->where('ship_status=?','0')
                    ->where('status=?','active')
                    ->instance()->fetch_row();
          if (empty($order)) {
            $v_msg = '补差价订单有误!';
            return false;
          }
        }
        return true;
    }

    //质检成功后执行相应的操作
    function finish_aftersale($reship_id){
      $oDc = $this->app->model('dly_corp');
      $Oreturn_products = $this->app->model('return_product');
      $Oreship = &$this->app->model('reship');
      $oOrder = $this->app->model('orders');
      $oItemModel = $this->app->model('order_items');

      $oOperation_log = &$this->app->model('operation_log');
      $oRefund_apply = &$this->app->model('refund_apply');

      $reshipinfo = $this->dump(array('reship_id'=>$reship_id),'*');
      
      $order_id = $reshipinfo['order_id'];
      $shop_id = $reshipinfo['shop_id'];
      $is_archive = kernel::single('archive_order')->is_archive($reshipinfo['source']);
      if ($is_archive) {
          $oOrder = app::get('archive')->model('orders');
          $oItemModel = app::get('archive')->model('order_items');
      }
      #是否生成售后单
      $is_generate_aftersale = true;
      #避免并发加判断
      $reship_detail = $Oreship->dump($reship_id,'status');
      if ($reship_detail['status'] == 'succ') {
          return false;
      }
      #
      # 退货单,收货成功
      $this->update(array('status'=>'succ'),array('reship_id'=>$reship_id));

      $oShop = $this->app->model('shop');
      $shop_type = $oShop->getRow(array('shop_id'=>$shop_id),'node_type');

      $c2c_shop_type = ome_shop_type::shop_list();
      if(!empty($shop_type['node_type']) && !in_array($shop_type['node_type'],$c2c_shop_type)){
          //退货单创建 API
          foreach(kernel::servicelist('service.reship') as $object=>$instance){
              if(method_exists($instance,'reship')){
                  $instance->reship($reship_id);
              }
          }
      }

      # 订单明细退货处理
      $oReship_items = $this->app->model('reship_items');
      $Reshipitem = $oReship_items->getList('bn,num,normal_num,defective_num',array('reship_id'=>$reship_id,'return_type'=>'return'));
      foreach($Reshipitem as $k=>$v){
            //$orderItems = $oItemModel->getList('sendnum,bn,item_id',array('order_id'=>$order_id,'bn'=>$v['bn'],'sendnum|than'=>'0'));
            if ($is_archive) { 
                $itemsql = "SELECT sendnum,bn,item_id, return_num FROM sdb_archive_order_items 
                                                WHERE order_id='".$order_id."' AND bn='".$v['bn']."' AND sendnum != return_num";
            }else{
                $itemsql = "SELECT sendnum,bn,item_id, return_num FROM sdb_ome_order_items 
                                                WHERE order_id='".$order_id."' AND bn='".$v['bn']."' AND sendnum != return_num";
            }
            $orderItems = $this->db->select($itemsql);
            
            $num = intval($v['normal_num']+$v['defective_num']);
            
            $residue_num    = 0;//剩余退货量 ExBOY
            
            foreach ($orderItems as $ivalue) {
                if($num <= 0) break;

                $residue_num    = intval($ivalue['sendnum'] - $ivalue['return_num']);//剩余数量=已发货量-已退货量 ExBOY
                
                if ($num > $residue_num) {
                    $num -= $residue_num;
                    //$oItemModel->update(array('sendnum'=>'0'),array('item_id'=>$ivalue['item_id']));
                    #更新_已退货量 = 已发货量 ExBOY
                    $oItemModel->update(array('return_num' => $ivalue['sendnum']),array('item_id'=>$ivalue['item_id']));
                    
                } else {
                    //$oItemModel->update(array('sendnum'=>($ivalue['sendnum']-$num)),array('item_id'=>$ivalue['item_id']));
                    #更新_已退货量 = 已退货量 + 本次退货量 ExBOY
                    $oItemModel->update(array('return_num' => ($ivalue['return_num'] + $num)),array('item_id'=>$ivalue['item_id']));
                    
                    $num = 0;
                }
            }
      }

      # 更新订单发货状态[return_num排除_已退完商品 ExBOY]
      if ($is_archive) {
          $order_sum = $this->db->selectrow('SELECT sum(sendnum) as count FROM sdb_archive_order_items WHERE order_id='.$order_id.' AND sendnum != return_num');
      }else{
            $order_sum = $this->db->selectrow('SELECT sum(sendnum) as count FROM sdb_ome_order_items WHERE order_id='.$order_id.' AND sendnum != return_num');
      }
      $ship_status = ($order_sum['count'] == 0) ? '4' : '3';
      $oOrder->update(array('ship_status'=>$ship_status),array('order_id'=>$order_id));

      $addon = array('reship_id'=>$reship_id,'return_id'=>$reshipinfo['return_id']);
      if ($is_archive) {
        $orders = $oOrder->dump(array('order_id'=>$order_id));
      }else{
        $orders = $oOrder->dump(array('order_id'=>$order_id));
      }
      
	  //zjr
	  $z_bn=$orders['pay_bn'];
	  $z_order_bn=$orders['order_bn'];
	  $arrPay_id= $this->db->selectrow("SELECT id FROM sdb_ome_payment_cfg WHERE pay_bn='$z_bn'");
	  //echo "<pre>";print_r($arrPay_id);
	  $arrPay_id=$arrPay_id['id'];
	  if($arrPay_id=="3"){
	      $arrPay_id=4;
	  }
      $z_r_apply_bn=$this->db->selectrow("SELECT payment_bn FROM sdb_ome_payments WHERE order_id='$order_id' AND status='succ'");
	  $refund_apply_bn=$z_r_apply_bn['payment_bn'];
	  $refund_apply_bn=$oRefund_apply->checkRefundApplyBn($refund_apply_bn);
      //生成退款申请单
      //$refund_apply_bn = $oRefund_apply->gen_id();
      $money = (float)$reshipinfo['tmoney']+(float)$reshipinfo['diff_money']+(float)$reshipinfo['bcmoney']-(float)$reshipinfo['bmoney'];
	  //zjr
	  $z_money=$money;
	  
      $refund_sdf = array(
            'refund_refer'       => '1',
            'apply_op_id'        =>kernel::single('desktop_user')->get_id(),
            'verify_op_id'        =>kernel::single('desktop_user')->get_id(),
            'order_id'             => $order_id,
			'reship_id'				=>$reship_id,
			'payment'               => $arrPay_id,
            'refund_apply_bn' => $refund_apply_bn,
            'pay_type'            => 'online',
            'money'               => (float)$money,
            'bcmoney'=>(float)$reshipinfo['bcmoney'],
            'refunded'            => 0,
            'memo'                => '退换货生成的退款申请单，退换货单号为:'.$reshipinfo['reship_bn'].'。',
            'create_time'        => time(),
            'status'                => 0,
            'shop_id'             => $shop_id,
            'addon'               => serialize($addon),
            'return_id'           => $reshipinfo['return_id'],
            'shop_type'           =>$oShop->getShoptype($shop_id),

      );
      if ($is_archive) {
          $refund_sdf['archive'] = '1';
           $refund_sdf['source'] = 'archive';
      }
      $oRefund_apply->create_refund_apply($refund_sdf);

      $reshipLib = kernel::single('ome_reship');
      if ($is_archive) {
        $reshipLib = kernel::single('archive_reship');
      }
        #  判断是否要生成一张支付单
        $refundMoney  = (float)$reshipinfo['tmoney']; # 退款金额
        $depreciation = (float)$reshipinfo['bmoney']; # 折旧费
        $diffMoney    = 0;                            # 补差价
        if ($reshipinfo['diff_order_bn']) {
          #$diffOrder = $oOrder->select()->columns('total_amount,order_id,shop_id')
          #              ->where('order_bn=?',$reshipinfo['diff_order_bn'])
          #              ->instance()->fetch_row();
          #$diffMoney = $diffOrder['total_amount'];

          //新增补差订单 发货状态改为已发货 并把状态回打给前端。
        kernel::single('ome_reship')->updatediffOrder($reshipinfo['diff_order_bn']);        }
        $totalmoney = (float)$reshipinfo['totalmoney']; # 实际需要退款的金额

      $memo = '';
      # 换货处理
      if($reshipinfo['return_type'] =='change'){
        $reship_items = $oReship_items->getList('*',array('reship_id'=>$reship_id));

        $dc_data = $oDc->dump($reshipinfo['return_logi_name']);
        $reshipinfo['return_logi_name'] = $dc_data['name'];

        define('FRST_TRIGGER_OBJECT_TYPE','订单：售后申请换货生成新订单');
        define('FRST_TRIGGER_ACTION_TYPE','ome_mdl_return_product：saveinfo');
        $change_order_sdf=$this->create_order($reshipinfo,$reship_items);
        
        if ($change_order_sdf) {
          $memo .=' 生成了1张换货订单【'.$change_order_sdf['order_bn'].'】';
            kernel::single('console_reship')->change_freezeproduct($reship_id,'-');//生成订单后释放库存
        # 源始订单记录关系订单号
        //$oOrder->update(array('relate_order_bn'=>$change_order_sdf['order_bn']),array('order_id'=>$order_id));
        //修改关联订单号
        app::get('ome')->model('orders')->update(array('relate_order_bn'=>$orders['order_bn']),array('order_bn'=>$change_order_sdf['order_bn']));
       
          # 换出的商品订单总额
          /*
          $neworder = $oOrder->select()->columns('total_amount,order_id')
                                  ->where('order_bn=?',$new_order_bn)
                                  ->instance()->fetch_row();*/
          # 换出的订单金额
          $change_total_amount = $change_order_sdf['total_amount'];
          # 换出的订单ID
          $neworderid = $change_order_sdf['order_id'];
        }

        # 如果实际退款金额为零,无需退款与支付
        if ($totalmoney == 0) {
          # 取消退款申请
          //$reshipLib->cancelRefundApply($refund_sdf['apply_id']);

          # 退款申请完成，并产生退款单
            #退款金额=申请+补偿
          $refund_sdf['money'] = $money;
          $reshipLib->createRefund($refund_sdf,$orders);

          $pay_money = $money;
          $pay_status = '1';
          # 不对新订单做操作
          //--
        }elseif ($totalmoney<0) {
          # 需要用户补钱的
          //$reshipLib->cancelRefundApply($refund_sdf['apply_id']);

          # 退款申请完成，并产生退款单
          $reshipLib->createRefund($refund_sdf,$orders);

          $pay_money = $money;
          $pay_status = '3';

        }elseif ($totalmoney>0) {
         
          $is_generate_aftersale = false;

          # 需要退款
          $memo .= $refund_sdf['memo'].'总退款金额大于换货订单总额，进行多余费用退款!';
          $refundApplyUpdate = array(
            'money' => (float)$totalmoney,
            'memo' => $memo,
          );
          $oRefund_apply->update($refundApplyUpdate,array('refund_apply_bn'=>$refund_apply_bn));

            # 系统完成部分退款 生成退款申请单
          $refund_apply_bn = $oRefund_apply->gen_id();
          $refund_sdf = array(
                'refund_refer'    => '1',
                'apply_op_id'     => kernel::single('desktop_user')->get_id(),
                'order_id'        => $order_id,
                'refund_apply_bn' => $refund_apply_bn,
                'verify_op_id'    => kernel::single('desktop_user')->get_id(),
                'pay_type'        => 'online',
                'money'           => (float)$change_total_amount,
                'refunded'        => 0,
                'memo'            => '退换货生成的退款申请单，退换货单号为:'.$reshipinfo['reship_bn'].'。',
                'create_time'     => time(),
                'status'          => 0,
                'shop_id'         => $shop_id,
                'addon'           => serialize($addon),
                'return_id'       => $reshipinfo['return_id'],
                'shop_type'           =>$oShop->getShoptype($shop_id),
                
          );
          if ($is_archive) {
              $refund_sdf['archive'] = '1';
               $refund_sdf['source'] = 'archive';
          }
          $oRefund_apply->create_refund_apply($refund_sdf);

          # 退款申请完成，并产生退款单
          $reshipLib->createRefund($refund_sdf,$orders);

          $pay_money = $change_total_amount;
          $pay_status = '1';
        }

        # 新订单改为全部支付
        if ($neworderid) {
          $order = array(
            'order_id'        => $neworderid,
            'shop_id'         => $reshipinfo['shop_id'],
            'pay_status'      => $pay_status,
            'pay_money'       => $pay_money,
            'currency'        => 'CNY',
            'reship_order_bn' => $orders['order_bn'],
          );
          if ($is_archive) {
            $order['archive'] = '1';
          }
          $reshipLib->payChangeOrder($order);
        }

      }elseif($reshipinfo['return_type'] =='return'){
        # 退货
        if($totalmoney == 0) {
            $memo = $refund_sdf['memo'].'应退商品金额扣除折旧费邮费后，实际应退金额为0。';

            $reshipLib->cancelRefundApply($refund_sdf['apply_id'],$memo);
        }elseif($refundMoney>$totalmoney) {
            # 多退

            #second
            //退换货生成的退款申请单，退换货单号为:201301251613000368。应退金额(12)扣除折旧费邮费后，实际应退金额为(2)

          $memo = $refund_sdf['memo'].'应退金额('.$refundMoney.')扣除折旧费邮费后，实际应退金额为('.$totalmoney.')';
          $refundApplyUpdate = array(
            'money' => (float)$totalmoney,
            'memo' => $memo,
          );
          $oRefund_apply->update($refundApplyUpdate,array('refund_apply_bn'=>$refund_apply_bn));

          $is_generate_aftersale = false;

        } elseif ($totalmoney>0 && $totalmoney>$refundMoney){
            # 少退的
            $memo = $refund_sdf['memo'].'应退商品金额('.$refundMoney.'),';

            if ($reshipinfo['cost_freight_money'] < 0) {
                $memo .= '加上相应的邮费,';
            }
            $memo .= '实际应退金额为('.$totalmoney.')';
            $refundApplyUpdate = array(
                'money' => (float)$totalmoney,
                'memo' => $memo,
            );
            $oRefund_apply->update($refundApplyUpdate,array('refund_apply_bn'=>$refund_apply_bn));
            $is_generate_aftersale = false;
            
        }
		//echo "<pre>";print_r($arrPay_id);
		//zjr发给买尽头
		$z_refund_id=$refund_sdf['apply_id'];
		$z_order_bn=$z_order_bn;
		
		$z_refund_info[] = array('oms_rma_id'=>$reship_id);
		if($arrPay_id=="4"){
			kernel::single('omemagento_service_order')->update_status($z_order_bn,'refund_required','',time(),$z_refund_info);
		}else{
			kernel::single('omemagento_service_order')->update_status($z_order_bn,'refunding','',time(),$z_refund_info);
		}
		app::get('ome')->model('refund_apply')->sendRefundToM($z_refund_id,$z_order_bn,$z_money,$reship_id);
      }


      $this->update(array('is_check'=>'7','t_end'=>time()),array('reship_id'=>$reship_id));
      $memo .= '操作完成。';
      if($reshipinfo['return_id']){
        $Oreturn_products->update(array('status'=>'4','money'=>$totalmoney),array('return_id'=>$reshipinfo['return_id']));
        $oOperation_log->write_log('return@ome',$reshipinfo['return_id'],$memo);
        //退货完成回写
        kernel::single('ome_service_aftersale')->update_status($reshipinfo['return_id']);
      }
      $oOperation_log->write_log('reship@ome',$reship_id,$memo);
    
        //var_dump($is_generate_aftersale);
        //生成售后单
      if($is_generate_aftersale){
         kernel::single('sales_aftersale')->generate_aftersale($reship_id,$reshipinfo['return_type']);
      }
      return true;
      
    }

    function io_title( $filter=null,$ioType='csv' ){
       
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['reship'] = array(
                    'col:退换货单号' => 'reship_bn',
                    'col:售后申请单号' => 'return_id',
                    'col:售后申请标题' => 'return_title',
                    'col:问题类型'=>'problem_id',
                    'col:订单号' => 'order_id',
                    'col:配送费用' => 'money',
                    'col:是否保价' => 'is_protect',
                    'col:配送方式' => 'delivery',
                    'col:物流公司名称' => 'logi_name',
                    'col:物流单号' => 'logi_no',
                    'col:退回物流公司名称' => 'return_logi_name',
                    'col:退回物流单号' => 'return_logi_no',
                    'col:收货人姓名' => 'ship_name',
                    'col:收货人地区' => 'ship_area',
                    'col:收货人地址' => 'ship_addr',
                    'col:收货人邮编' => 'ship_zip',
                    'col:收货人电话' => 'ship_tel',
                    'col:收货人手机' => 'ship_mobile',
                    'col:收货人Email' => 'ship_email',
                    'col:当前状态' => 'is_check',
                    'col:备注' => 'memo',
                    'col:退款的金额' => 'tmoney',
                    'col:补差的金额' => 'bmoney',
                    'col:补偿费用' => 'bcmoney',
                    'col:最后合计金额' => 'totalmoney',
                    'col:收货时间'=>'receive_time',
                    'col:单据结束时间'=>'t_end',
                );
                
                
                    $this->oSchema['csv']['items'] = array(
                    'col:退货单号' => 'reship_bn',
                    'col:商品货号' => 'bn',
                    'col:仓库名称' => 'branch_name',
                    'col:类型' => 'return_type',
                    'col:商品名称' => 'product_name',
                    'col:申请数量' => 'num',
                    'col:良品' => 'normal_num',
                    'col:不良品' =>'defective_num',
                   
                );
                    break;
        }
        if($this->expert_flag){
           $_title = array(
                    'col: 售后类型' => 'return_type',
                    'col:单据创建时间' => 't_begin',
                   );
           $this->oSchema[$ioType]['reship'] = array_merge($this->oSchema[$ioType]['reship'],$_title);
        }
        
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        $this->expert_flag =true;

        if( !$data['title']['reship'] ){
            $title = array();
            foreach( $this->io_title('reship') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['reship'] = '"'.implode('","',$title).'"';
        }
        if( !$data['title']['items'] ){
            $title = array();
            foreach( $this->io_title('items') as $k => $v )
                $title[] = $this->charset->utf2local($v);
            $data['title']['items'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;

        $itemsObj = $this->app->model('reship_items');
        if( !$list=$this->getList('reship_id',$filter,$offset*$limit,$limit) )return false;
        $oProduct_pro   = $this->app->model('return_process');
        foreach( $list as $aFilter ){
            $aReship = $this->dump($aFilter['reship_id'],'reship_bn,return_id,problem_id,order_id,money,is_protect,delivery,logi_name,logi_no,return_logi_name,return_logi_no,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,is_check,memo,tmoney,bmoney,totalmoney,return_type,bcmoney,t_end,t_begin');
            
            $reship_id = $aFilter['reship_id'];
            $return_process = $oProduct_pro->dump(array('reship_id'=>$reship_id),'process_data');

            $aReship['return_logi_no'] = "=\"\"".$aReship['return_logi_no']."\"\"";
            $aReship['logi_no'] = "=\"\"".$aReship['logi_no']."\"\"";
            $aReship['reship_bn'] =  "=\"\"".$aReship['reship_bn']."\"\"";//$aReship['reship_bn']."\t";
            //处理售后信息
            $rp = $this->app->model('return_product')->dump($aReship['return_id'],'return_bn,title');
            $aReship['return_id'] = "=\"\"".strval($rp['return_bn'])."\"\"";//strval($rp['return_bn'])."\t";
            $aReship['return_title'] = $rp['title'];

            #售后类型
            if($aReship['return_type']){
                $aReship['return_type'] = $this->return_type[$aReship['return_type']];
            }
            //售后问题
            $rpp = $this->app->model('return_product_problem')->dump($aReship['problem_id'],'problem_name');
            $aReship['problem_id'] = $rpp['problem_name'];
            if ($aReship['archive'] == '1') {
                //处理订单号
                $oOrder = app::get('archive')->model('orders')->dump($aReship['order_id'],'order_bn');
            }else{
                //处理订单号
                $oOrder = $this->app->model('orders')->dump($aReship['order_id']);
            }
            $aReship['order_id'] = "=\"\"".$oOrder['order_bn']."\"\"";//$oOrder['order_bn']."\t";

            //处理物流信息
            $dc = $this->app->model('dly_corp')->dump($aReship['return_logi_name'],'name');
            $aReship['return_logi_name'] = $dc['name'];
            //
            $process_data = $return_process['process_data'];
            $aReship['receive_time'] = '';
            $aReship['t_begin'] = $aReship['t_begin'] ? date('Y-m-d H:i:s',$aReship['t_begin']) : '';
            $aReship['t_end'] = $aReship['t_end'] ? date('Y-m-d H:i:s',$aReship['t_end']) : '';
            if ($process_data) {
                $process_data = unserialize($process_data);
                $aReship['receive_time'] = date('Y-m-d H:i:s',$process_data['shiptime']);
            }

            //处理收货地区
            $rd = explode(':', $aReship['ship_area']);
            if($rd[1]){
             $aReship['ship_area'] = str_replace('/', '-', $rd[1]);
            }

            //处理当前状态
            $aReship['is_check'] = $this->is_check[$aReship['is_check']];

            $aReship['is_protect'] = $aReship['is_protect']=='false'?'否':'是';


            $oreship = array_values($this->oSchema['csv']['reship']);
            //items
            $_items = $itemsObj->getlist('*',array('reship_id'=>$reship_id));
           
            foreach ( $_items as $_k=>$_v ) {
                $itemcsv =array_values($this->oSchema['csv']['items']);
                switch ($_v['return_type']) {
                    case 'return':
                         $return_type = '退货';
                        break;
                    case 'change':
                        $return_type = '换货';
                        break;
                        case 'refuse':
                            $return_type = '拒收退货';
                            break;
                        
                }
                $branch = $itemsObj->db->selectrow("SELECT name FROM sdb_ome_branch WHERE branch_id=".$_v['branch_id']);
                $item = array(
                    'reship_bn'=>$aReship['reship_bn'],
                    'bn'=>$_v['bn'],
                    'product_name'=>$_v['product_name'],
                    'num'=>$_v['num'],
                    'normal_num'=>$_v['normal_num'],
                    'defective_num'=>$_v['defective_num'],
                    'return_type'=>$return_type,
                    'branch_name'=>$branch['name'],
                );

               foreach ($itemcsv as $ik=>$iv ) {
                   $itemRow[$ik] = $this->charset->utf2local($item[$iv]);
               } 
               $data['content']['items'][] = '"'.implode('","',$itemRow).'"';
               unset($branch);
            }
            foreach( $oreship as $k=>$v ){
                $reshipRow[$v] = $this->charset->utf2local($aReship[$v]);
            }
            $data['content']['reship'][] = '"'.implode('","',$reshipRow).'"';
        }

        $data['name'] = '退换货单'.date("Ymd");
         
        return true;
    }

    public function getexportdetail($fields,$filter,$offset=0,$limit=1,$has_title=false)
    {
        
        $reship_arr = $this->getList('reship_bn,reship_id', array('reship_id' => $filter['reship_id']), 0, -1);
        foreach ($reship_arr as $reship) {
            $reship_bn[$reship['reship_id']] = $reship['reship_bn'];
        }

        $Obranch = &app::get('ome')->model('branch');
        $branchs = $Obranch->getList('branch_id,name');
        foreach ($branchs as $v) {
            $branch[$v['branch_id']] = $v['name'];
        }
        unset($branchs);

        $reshipItemsObj = app::get('ome')->model('reship_items');
        $reship_items_arr = $reshipItemsObj->getList('*',array('reship_id'=>$filter['reship_id']));
        $row_num = 1;
        if($reship_items_arr){
            foreach ($reship_items_arr as $key => $reship_item) {
                $reshipItemRow['*:销售单号']   = isset($reship_bn[$reship_item['reship_id']]) ? mb_convert_encoding($reship_bn[$reship_item['reship_id']], 'GBK', 'UTF-8') : '-';
                $reshipItemRow['*:商品货号']       = mb_convert_encoding($reship_item['bn'], 'GBK', 'UTF-8');
                $reshipItemRow['*:仓库名称']       = isset($branch[$reship_item['branch_id']]) ? mb_convert_encoding($branch[$reship_item['branch_id']], 'GBK', 'UTF-8') : '-';

                switch ($reship_item['return_type']) {
                    case 'return':
                         $return_type = '退货';
                        break;
                    case 'change':
                        $return_type = '换货';
                        break;
                    case 'refuse':
                        $return_type = '拒收退货';
                        break;
                }

                $reshipItemRow['*:类型']   = mb_convert_encoding($return_type, 'GBK', 'UTF-8');
                $reshipItemRow['*:商品名称']   = mb_convert_encoding($reship_item['product_name'], 'GBK', 'UTF-8');
                $reshipItemRow['*:申请数量']   = $reship_item['num'];
                $reshipItemRow['*:良品']   = $reship_item['normal_num'];
                $reshipItemRow['*:不良品']   = $reship_item['defective_num'];

                $data[$row_num] = implode(',', $reshipItemRow);
                $row_num++;
            }
        }

        //明细标题处理
        if($data && $has_title){
            $title = array(
                '*:退货单号',
                '*:商品货号',
                '*:仓库名称',
                '*:类型',
                '*:商品名称',
                '*:申请数量',
                '*:良品',
                '*:不良品',
            );

            foreach ((array)$title as $key => $value) {
                $title[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }

            $data[0] = implode(',', $title);
        }

        ksort($data);
        return $data;
    }

    function export_csv($data,$exportType = 1 ){
       
        $output = array();
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        echo implode("\n",$output);
    }

    function getLogiInfo($logi_no,$branch_ids=array()){
        $sql = 'select reship_id from sdb_ome_reship where return_logi_no="'.$logi_no.'"';
        if ($branch_ids) {
            $sql.=" AND branch_id in (".implode(',',$branch_ids).")";
        }
      $loginfo = $this->db->selectrow($sql);
      if($loginfo['reship_id']){
          return $loginfo['reship_id'];
      }
        return false;
    }

    public function modifier_totalmoney($row)
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

    public function modifier_is_check($row) {
        if($row == '3') {
            return '审核成功';
        }
        return $this->schema['columns']['is_check']['type'][$row];
    }

    public function modifier_op_id($row){
        switch ($row) {
            
            case 16777215:
                $ret = '系统';
                break;
            default:
                $ret = $this->_getUserName($row);
                break;
        }

        return $ret;
    }

    /**
     * 获取用户名
     *
     * @param Integer $gid
     * @return String;
     */
    private function _getUserName($uid) {
        if (self::$__USERS === null) {

            self::$__USERS = array();
            $rows = app::get('desktop')->model('users')->getList('*');
            foreach((array) $rows as $row) {
                self::$__USERS[$row['user_id']] = $row['name'];
            }
        }

        if (isset(self::$__USERS[$uid])) {

            return self::$__USERS[$uid];
        } else {

            return '系统';
        }
    }
    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'afterSale';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_return_rchange') {
            if (isset($params['is_check'])) {
                //质检单据
                $type .= '_exchange_goods_qualityTesting';
            }
            else {
                //退换货
                $type .= '_exchange_goods_exchangeList';
            }
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'afterSale';
        $type .= '_import';
        return $type;
    }

    
    /**
     * 补偿费用显示
     * @param int
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_bcmoney($row)
    {
        if ($row>0) {
            $bcmoney = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', $row, $row, $row);
            return $bcmoney;
        }
    }

    public function disabled_export_cols(&$cols){
        unset($cols['column_edit']);
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
        
        if($row == 'archive'){
           $row = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', '归档', '归档', '归档');
        }else{
            $row = '-';
        }
        return $row;
    }
}
?>