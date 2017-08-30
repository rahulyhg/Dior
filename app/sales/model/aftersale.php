<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @date 2012-08-22
	* 售后单
*/
class sales_mdl_aftersale extends ome_mdl_sales{

	  var $export_name = '售后单';

    var $defaultOrder = array('aftersale_time DESC');

    var $has_many = array(
       'aftersale_items' => 'aftersale_items'
    );

    var $return_type = array(
                            'return' => '退货',
                            'change' => '换货',
                            'refund' => '退款',
                            'refuse' => '拒绝收货',
                            'refunded' => '退款',
                        );

    var $pay_type = array(
                            'online' => '在线支付',
                            'offline' => '线下支付',
                            'deposit' => '预存款支付',
                        );

    var $common_type = array(
                            '0'=>'common',
                            '1'=>'return',
                            '2'=>'change',
                            '3'=>'refund',
                        );

    public function searchOptions(){

        $ext_columns = array(
          'order_bn'=>$this->app->_('订单号'),
          'reship_bn'=>$this->app->_('退换货单号'),
          'return_apply_bn'=>$this->app->_('退款申请单号'),
        );
        
        return $ext_columns;
    }

    public function io_title( $filter=null,$ioType='csv',$return_type ){
      switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['aftersale'] = $this->get_return_type('aftersale',$return_type);
                $this->oSchema['csv']['aftersale_items'] = $this->get_return_type('aftersale_items',$return_type);
                break;
        }
        $this->ioTitle[$ioType]['aftersale'] = array_keys( $this->oSchema[$ioType]['aftersale'] );
        $this->ioTitle[$ioType]['aftersale_items'] = array_keys( $this->oSchema[$ioType]['aftersale_items'] );
        return $this->ioTitle[$ioType][$filter];

    }

    /**
     * 导出title方法 根据传入type类型,显示相应的title信息
     * @return void
     * @param main aftersale 主表字段 aftersale_items 明细字段
     * @param type 1 退货单(return) 2 换货单(change) 3 拒收退货单(refuse) 4 退款单(refund)     
     * @author 
     **/
    public function get_return_type($main,$type){
      
      return kernel::single('sales_export_aftersale')->io_title($main,$this->common_type[$type]);

    }

     //csv导出
    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){

        $type = $_GET['view']?$_GET['view']:'0';

        if( !$data['title']['aftersale'] ){
          $title = array();
          foreach( $this->io_title('aftersale','csv',$type) as $k => $v ){
              $title[] = $this->charset->utf2local($v);
          }
          $data['title']['aftersale'] = '"'.implode('","',$title).'"';
        }

        $limit = 100;

        if(!$list = $this->getList('*',$filter,$offset*$limit,$limit)) return false;

        foreach ($list as $v) {
          $aftersaleIds[] = $v['aftersale_id'];
          $orderIds[] = $v['order_id'];
          $memberIds[] = $v['member_id']; 
          $shopIds[] = $v['shop_id'];  
          $returnIds[] = $v['return_id'];
          $reshipIds[] = $v['reship_id'];  
          $returnapplyIds[] = $v['return_apply_id'];  
                                     
        }

        $Oshop = app::get('ome')->model('shop');
        $Oorder = app::get('ome')->model('orders');     
        $Oreturn_products = app::get('ome')->model('return_product');           
        $Oreship = app::get('ome')->model('reship');  
        $Orefund_apply = app::get('ome')->model('refund_apply');        
        $Oaccount = app::get('pam')->model('account'); 
        $Oaftersale_items = $this->app->model('aftersale_items');
        $Omembers = app::get('ome')->model('members'); 
        $payment_cfgObj = app::get('ome')->model('payment_cfg');
        $Obranch = app::get('ome')->model('branch'); 

        #店铺信息
        $shop = $Oshop->getList('name,shop_id',array('shop_id'=>$shopIds)); 

        foreach ($shop as $v) {
          $shops[$v['shop_id']] = $v['name'];
        }

        #仓库信息
        $branch = $Obranch->getList('name,branch_id'); 

        foreach ($branch as $v) {
          $branchs[$v['branch_id']] = $v['name'];
        }

        #订单信息
        $order = $Oorder->getList('order_bn,order_id',array('order_id'=>$orderIds));  
        
        foreach ($order as $v) {
          $orders[$v['order_id']] = $v['order_bn'];
        }

        #售后申请信息
        $return_product = $Oreturn_products->getList('return_bn,return_id',array('return_id'=>$returnIds));   

        foreach ($return_product as $v) {
          $return_products[$v['return_id']] = $v['return_bn'];
        }

        #退换货信息
        $reship = $Oreship->getList('reship_bn,reship_id',array('reship_id'=>$reshipIds));   

        foreach ($reship as $v) {
          $reships[$v['reship_id']] = $v['reship_bn'];
        }

        #退款申请信息
        $refund_apply = $Orefund_apply->getList('refund_apply_bn,apply_id',array('apply_id'=>$returnapplyIds)); 

        foreach ($refund_apply as $v) {
          $refund_applys[$v['apply_id']] = $v['refund_apply_bn'];
        }

        #操作员信息
        $account = $Oaccount->getList('login_name,account_id'); 
        
        foreach ($account as $v) {
          $accounts[$v['account_id']] = $v['login_name'];
        }

        #会员信息
        $member = $Omembers->getList('uname,member_id',array('member_id'=>$memberIds));  
        
        foreach ($member as $v) {
          $members[$v['member_id']] = $v['uname'];
        }
        
        #支付方式信息
        $payment_cfg = $payment_cfgObj->getList('id,custom_name');
        
        foreach ($payment_cfg as $v) {
          $payment_cfgs[$v['id']] = $v['custom_name'];
        }

        //所有的子售后单据数据
        $rs = $Oaftersale_items->getList('*',array('aftersale_id'=>$aftersaleIds));

        foreach($rs as $v) {
            $sales_items[$v['aftersale_id']][] = $v;
        }

        foreach( $list as $aFilter ){

          $aOrderRow = array();
          $check_op_id = $accounts[$aFilter['check_op_id']];
          $op_id = $accounts[$aFilter['op_id']];
          $refund_op_id = $accounts[$aFilter['refund_op_id']];

          $rows = array(
              'shop_id'               => $shops[$aFilter['shop_id']],
              'order_id'              => "=\"\"".$orders[$aFilter['order_id']]."\"\"",
              'aftersale_bn'          => $aFilter['aftersale_bn']."\t",
              'return_id'             => $return_products[$aFilter['return_id']]."\t",
              'reship_id'             => "=\"\"".$reships[$aFilter['reship_id']]."\"\"",
              'diff_order_bn'         => $aFilter['diff_order_bn']."\t",
              'change_order_bn'       => $aFilter['change_order_bn']."\t",                    
              'return_apply_id'       => $refund_applys[$aFilter['return_apply_id']]."\t",
              'return_type'           => $this->return_type[$aFilter['return_type']],
              'refundmoney'           => $aFilter['refundmoney']?$aFilter['refundmoney']:'-',
              'paymethod'             => $aFilter['paymethod']?$aFilter['paymethod']:'-',
              'refund_apply_money'    => $aFilter['refund_apply_money']?$aFilter['refund_apply_money']:'-',
              'member_id'             => $members[$aFilter['member_id']],
              'ship_mobile'           => $aFilter['ship_mobile']?$aFilter['ship_mobile']:'-',
              'pay_type'              => $aFilter['pay_type']?$this->pay_type[$aFilter['pay_type']]:'-',
              'account'               => $aFilter['account']?$aFilter['account']:'-',
              'bank'                  => $aFilter['bank']?$aFilter['bank']:'-',
              'pay_account'           => $aFilter['pay_account']?$aFilter['pay_account']:'-',
              'refund_apply_time'     => !empty($aFilter['refund_apply_time'])?date('Y-m-d H:i:s',$aFilter['refund_apply_time']):'-',
              'check_op_id'           => $check_op_id?$check_op_id:'-',
              'op_id'                 => $op_id?$op_id:'-',
              'refund_op_id'          => $refund_op_id?$refund_op_id:'-',
              'add_time'              => !empty($aFilter['add_time'])?date('Y-m-d H:i:s',$aFilter['add_time']):'-',
              'check_time'            => !empty($aFilter['check_time'])?date('Y-m-d H:i:s',$aFilter['check_time']):'-',
              'acttime'               => !empty($aFilter['acttime'])?date('Y-m-d H:i:s',$aFilter['acttime']):'-',
              'refundtime'            => !empty($aFilter['refundtime'])?date('Y-m-d H:i:s',$aFilter['refundtime']):'-',
              'aftersale_time'        => !empty($aFilter['aftersale_time'])?date('Y-m-d H:i:s',$aFilter['aftersale_time']):'-',
          );

          $aOrderRow = kernel::single('sales_export_aftersale')->io_contents('aftersale',$this->common_type[$type],$rows);

          $data['content']['aftersale'][]  = $this->charset->utf2local('"'.implode( '","', $aOrderRow ).'"');

          $objects = $sales_items[$aFilter['aftersale_id']];

          if ($objects){

			if( !$data['title']['aftersale_items'] ){
			  $title = array();
			  foreach( $this->io_title('aftersale_items','csv',$type) as $k => $v ){
				  $title[] = $this->charset->utf2local($v);
			  }
			  $data['title']['aftersale_items'] = '"'.implode('","',$title).'"';
			}

            foreach ($objects as $obj){
              $orderObjRow = array();
              if($obj['return_type'] == 'refunded'){
                 $pay_type = $this->pay_type[$obj['pay_type']];
                 $num = $price = '-';
              }else{
                 $num = $obj['num']?$obj['num']:'-';
                 $price = $obj['price']?$obj['price']:'-';
                 $pay_type = '-';
              }

              $branch_id = $obj['branch_id'];

              $rowsobj = array(
                  'aftersale_bn'         => $aFilter['aftersale_bn']."\t",
                  'pay_type'             => $pay_type,
                  'account'              => $obj['account']?$obj['account']:'-',
                  'bank'                 => $obj['bank']?$obj['bank']:'-',
                  'pay_account'          => $obj['pay_account']?$obj['pay_account']:'-',
                  'money'                => $obj['money']?$obj['money']:'-',
                  'refunded'             => $obj['refunded']?$obj['refunded']:'-',
                  'payment'              => $obj['payment']?$payment_cfgs[$obj['payment']]:'-',
                  'create_time'          => !empty($obj['create_time'])?date('Y-m-d H:i:s',$obj['create_time']):'-',
                  'last_modified'        => !empty($obj['last_modified'])?date('Y-m-d H:i:s',$obj['last_modified']):'-',                
                  'bn'                   => $obj['bn']?$obj['bn']:'-',
                  'product_name'         => $obj['product_name']?$obj['product_name']:'-',
                  'num'                  => $num,
                  'price'                => $price,
                  'branch_id'            => $branchs[$obj['branch_id']],
                  'return_type'          => $this->return_type[$obj['return_type']],
              );

              $orderObjRow = kernel::single('sales_export_aftersale')->io_contents('aftersale_items',$this->common_type[$type],$rowsobj);
              $data['content']['aftersale_items'][] = $this->charset->utf2local('"'.implode( '","', $orderObjRow ).'"');
            }
          }
        }
        $data['name'] = 'aftersale'.date("YmdHis");
        return true;
    }

    public function export_csv($data,$exportType = 1 ){
        $output = array();
         foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        echo implode("\n",$output);
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
       $Obj = app::get('ome');
       $where = '1 ';
       if(isset($filter['order_bn'])){
       	  $orders = array(0);
       	  $Oorder = $Obj->model("orders");
          $order = $Oorder->getList('order_id',array('order_bn|head'=>$filter['order_bn']));
          foreach ($order as $v) {
          	 $orders[] = $v['order_id'];
          }
          $where .= 'and order_id in ('.implode(',',$orders).')';
          unset($filter['order_bn']);
       }

       if(isset($filter['return_apply_bn'])){
       	  $return_applys = array(0);
       	  $Oreturn_apply = $Obj->model("refund_apply");
          $return_apply = $Oreturn_apply->getList('apply_id',array('apply_id|head'=>$filter['return_apply_bn']));
          foreach ($reship as $v) {
          	 $return_applys[] = $v['apply_id'];
          }
          $where .= ' and return_apply_id in ('.implode(',',$return_applys).')';
          unset($filter['return_apply_bn']);
       }

       if(isset($filter['reship_bn'])){
       	  $reships = array(0);
       	  $Oreship = $Obj->model("reship");
          $reship = $Oreship->getList('reship_id',array('reship_bn|head'=>$filter['reship_bn']));
          foreach ($reship as $v) {
          	 $reships[] = $v['reship_id'];
          }
          $where .= ' and reship_id in ('.implode(',',$reships).')';
          unset($filter['reship_bn']);
       }

       if(isset($filter['member_uname'])){
       	  $members = array(0);
       	  $Omember = $Obj->model("members");
          $member = $Omember->getList('member_id',array('uname|head'=>$filter['member_uname']));
          foreach ($member as $v) {
          	 $members[] = $v['member_id'];
          }
          $where .= ' and member_id in ('.implode(',',$members).')';
          unset($filter['member_uname']);
       }

       if(isset($filter['ship_mobile'])){
       	  $reships = array(0);
       	  $Oreship = $Obj->model("reship");
          $reship = $Oreship->getList('reship_id',array('ship_mobile|head'=>$filter['ship_mobile']));
          foreach ($reship as $v) {
          	 $reships[] = $v['member_id'];
          }
          $where .= ' and reship_id in ('.implode(',',$reships).')';
          unset($filter['ship_mobile']);
       }

       if(isset($filter['return_bn'])){
          $return_products = array(0);
          $Oreturn = $Obj->model("return_product");
          $return_product = $Oreturn->getList('return_id',array('return_bn|head'=>$filter['return_bn']));

          foreach ($return_product as $v) {
          	 $return_products[] = $v['return_id'];
          }
          $where .= ' and return_id in ('.implode(',',$return_products).')';
          unset($filter['return_bn']);
       }

       if(isset($filter['payment'])){
		  $payment_cfgObj = app::get('ome')->model('payment_cfg');
		  $payment_cfg = $payment_cfgObj->dump(array('id'=>$filter['payment']), 'custom_name');
		  $where .= ' and paymethod = "'.$payment_cfg['custom_name'].'"';
		  unset($filter['payment']);
       }

       if(isset($filter['problem_id'])){
		  $problemObj = app::get('ome')->model('return_product_problem');
		  $problemdata = $problemObj->dump(array('problem_id'=>$filter['problem_id']), 'problem_name');
		  $where .= ' and problem_name = "'.$problemdata['problem_name'].'"';
		  unset($filter['problem_id']);
       }

	   

       return $where.' and '.parent::_filter($filter,$tableAlias,$baseWhere);
    }

    /**
     * 得到唯一的aftersale id
     * @params null
     * @return string aftersale id
     */
    public function get_aftersale_bn(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $aftersale_bn = 'A'.time().str_pad($i,4,'0',STR_PAD_LEFT);
            $row = $this->dump($aftersale_bn, 'aftersale_bn');
        }while($row);
        return $aftersale_bn;
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
        $type = 'bill';
        if ($logParams['app'] == 'sales' && $logParams['ctl'] == 'admin_aftersale') {
            $type .= '_salesBill_afterSales';
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
        $type = 'bill';
        if ($logParams['app'] == 'sales' && $logParams['ctl'] == 'admin_aftersale') {
            $type .= '_salesBill_afterSales';
        }
        $type .= '_import';
        return $type;
    }

    /**
     * 来源
     * @param   
     * @return  string
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_archive($row)
    {
        
        if($row == '1'){
           $row = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', '归档', '归档', '归档');
        }else{
            $row = '-';
        }
        return $row;
    }
}