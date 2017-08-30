<?php
class sales_mdl_sales extends ome_mdl_sales{

    //是否有导出配置
    var $has_export_cnf = true;

    //所用户信息
    static $__USERS = null;

    var $export_name = '销售单';

    public $filter_use_like = true;

    public $appendCols = 'order_id';

    function __construct($app){
        parent::__construct(app::get('ome'));
    }

    public function table_name($real=false){
        $table_name = "sales";
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'bn'=>app::get('base')->_('商品货号'),
            'order_bn'=>app::get('base')->_('订单号'),
        );
        return $Options = array_merge($parentOptions,$childOptions);
     }

     function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){

        return parent::getList($cols, $filter, $offset, $limit, $orderby);

     }

     function _filter($filter,$tableAlias=null,$baseWhere=null){
        @ini_set('memory_limit','512M');
        $where = '1';
         //订单号查询
        if (isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn|head'=>$filter['order_bn']));
            $orderId[] = -1;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['order_bn']);

        }


        //货号查询
        if(isset($filter['bn'])){

            $sql = 'SELECT sale_id FROM sdb_ome_sales_items WHERE bn like \''.$filter['bn'].'\'';
            $rows = $this->db->select($sql);
            $saleId[] = 0;
            foreach($rows as $row){
                $saleId[] = $row['sale_id'];
            }

            $where .= ' AND sale_id IN ('. implode(',', $saleId).')';
            unset($filter['bn']);

        }

        //货品名称
        if(isset($filter['product_name'])){
            $sql = 'SELECT bn FROM sdb_ome_products WHERE name like \''.$filter['product_name'].'\'';
            $name = $this->db->select($sql);
            $sql2 = 'SELECT sale_id FROM sdb_ome_sales_items WHERE bn like \''.$name[0]['bn'].'\'';
            $rows = $this->db->select($sql2);
            $saleId[] = 0;
            foreach($rows as $row){
                $saleId[] = $row['sale_id'];
            }

            $where .= ' AND sale_id IN ('. implode(',', $saleId).')';
            unset($filter['product_name']);
        }

        if(isset($filter['ship_area'])){
            $sql = 'SELECT delivery_id FROM sdb_ome_delivery WHERE ship_area = "'.$filter['ship_area'].'"';
            $rows = $this->db->select($sql);
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }

            $where .= ' AND delivery_id IN ('. implode(',', $deliveryId).')';
            unset($filter['ship_area']);
        }
        if (isset($filter['original_bn'])) {
            $sql = 'SELECT delivery_id FROM sdb_ome_delivery WHERE delivery_bn = "'.$filter['original_bn'].'"';
            $rows = $this->db->select($sql);
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }

            $where .= ' AND delivery_id IN ('. implode(',', $deliveryId).')';
            unset($filter['original_bn']);
        }
        
        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".$where;
    }

    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

     function io_title( $filter=null,$ioType='csv' ){
            switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['sales'] = array(
                    '*:店铺名称'       => '',
                    '*:仓库名称'       => '',
                    '*:销售单号'       => '',
                    '*:订单号'         => '',
                    '*:发货单号'       => '',
                    '*:用户名称'       => '',
                    '*:支付方式'       => '',
                    '*:销售金额'       => '',
                    '*:优惠金额'       => '',
                    '*:商品总额'       => '',
                    '*:物流单号'       => '',
                    '*:预收物流费'     => '',
                    '*:预估物流费'     => '',
                    '*:配送费用'       => '',
                    '*:附加费'         => '',
                    '*:预存款'         => '',
                    '*:是否开发票'     => '',
                    '*:订单审核人'     => '',
                    '*:销售时间'       => '',
                    '*:下单时间'       => '',
                    '*:付款时间'       => '',
                    '*:订单审核时间'   => '',
                    '*:发货时间'       => '',
                    '*:收货人姓名'     => '',
                    '*:收货人地区'     => '',
                    '*:收货人地址'     => '',
                    '*:收货人邮编'     => '',
                    '*:收货人固定电话' => '',
                    '*:收货人Email'    => '',
                    '*:收货人手机'     => '',
                );
                $this->oSchema['csv']['sales_items'] = array(
                    '*:销售单号'   => '',
                    '*:订单号'     => '',
                    '*:货号'       => '',
                    '*:商品名称'   => '',
                    '*:商品规格'   => '',
                    '*:吊牌价'   => '',
                    '*:数量'   => '',
                    '*:货品优惠'   => '',
                    '*:销售总价'   => '',
                    '*:平摊优惠'   => '',
                    '*:销售金额'   => '',
                );
                break;
        }
        $this->ioTitle[$ioType]['sales'] = array_keys( $this->oSchema[$ioType]['sales'] );
        $this->ioTitle[$ioType]['sales_items'] = array_keys( $this->oSchema[$ioType]['sales_items'] );
        return $this->ioTitle[$ioType][$filter];

     }
     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
       //print_r($data);//$filter是选中sales表中的记录id
       //$data是sales数据表
       //$offset 是偏移值
       //
         
         #[发货配置]是否启动拆单 ExBOY
         $dlyObj         = &app::get('ome')->model('delivery');
         $split_seting   = $dlyObj->get_delivery_seting();
         
         if( !$data['title']['sales'] ){
             $title = array();
             foreach( $this->io_title('sales') as $k => $v ){
                 $title[] = $this->charset->utf2local($v);
                //$title[] = $v;
             }
             $data['title']['sales'] = '"'.implode('","',$title).'"';
         }
         if( !$data['title']['sales_items'] ){
             $title = array();
              foreach( $this->io_title('sales_items') as $k => $v ){
                  $title[] = $this->charset->utf2local($v);
                  //$title[] = $v;
             }
             $data['title']['sales_items'] = '"'.implode('","',$title).'"';
         }

         if( !$data['title']['sales_items'] ){
             $title = array();
              foreach( $this->io_title('sales_items') as $k => $v ){
                  $title[] = $this->charset->utf2local($v);
                  //$title[] = $v;
             }
             $data['title']['sales_items'] = '"'.implode('","',$title).'"';
         }

         $limit = 100;
         //if( $filter[''] )获取的sales的id
         //$list=$this->getList('id',$filter,$offset*$limit,$limit);获取的sales的列表

        if(!$list = $this->getList('*',$filter,$offset*$limit,$limit)){
            return false;
        }

        //优化代码
        $oShop = &app::get('ome')->model('shop');
        $oMembers = &app::get('ome')->model('members');
        $oPam = &app::get('pam')->model('account');
        $oBranch = &app::get('ome')->model('branch');
        $oDelivery = &app::get('ome')->model('delivery');
        $oOrder = &app::get('ome')->model('orders');
        $archive_order = &app::get('archive')->model('orders');
        $archive_delivery = &app::get('archive')->model('delivery');
        $oDeliveryOrder = &app::get('ome')->model('delivery_order');
        $oSalesItems = &app::get('ome')->model('sales_items');

        // 所有的店铺信息
        $rs = $oShop->getList('shop_id,shop_bn,name');
        foreach($rs as $v) {
            $shops[$v['shop_id']] = $v;
        }

        // 所有的仓库
        $rs = $oBranch->getList('branch_id,branch_bn,name');
        foreach($rs as $v) {
            $branchs[$v['branch_id']] = $v;
        }

        foreach($list as $v) {
            $order_ids[] = $v['order_id'];
            $member_ids[] = $v['member_id'];
            $iostock_bns[] = $v['iostock_bn'];
            $sale_ids[] = $v['sale_id'];
            $delivery_ids[] = $v['delivery_id'];
            $order_check_ids[] = $v['order_check_id'];
        }

        // 所有的会员
        $rs = $oMembers->getList('member_id,uname',array('member_id'=>$member_ids));
        foreach($rs as $v) {
            $members[$v['member_id']] = $v;
        }


        // 所有的发货单信息
        $rs = $oDelivery->getList('delivery_id,delivery_bn,logi_name,logi_no,ship_name,ship_addr,ship_zip,ship_tel,ship_email,ship_mobile,ship_area',array('delivery_id'=>$delivery_ids));
        foreach($rs as $v) {
            $deliverys[$v['delivery_id']] = $v;
        }
        unset($rs);
        $rs = $archive_delivery->getList('delivery_id,delivery_bn,logi_name,logi_no,ship_name,ship_addr,ship_zip,ship_tel,ship_email,ship_mobile,ship_area',array('delivery_id'=>$delivery_ids));
        foreach ($rs as $v ) {
            $deliverys[$v['delivery_id']] = $v;
        }
        unset($rs);
        // 所有的订单信息
        $rs = $oOrder->getList('order_id,order_bn',array('order_id'=>$order_ids));
        foreach($rs as $v) {
            $orders[$v['order_id']] = $v;
        }
        unset($rs);
        $rs = $archive_order->getList('order_id,order_bn',array('order_id'=>$order_ids));
        foreach ( $rs as $v ) {
            $orders[$v['order_id']] = $v;
        }
        unset($rs);
        // 所有的操作员信息
        $rs = $oPam->getList('login_name,account_id',array('account_id'=>$order_check_ids));
        foreach($rs as $v) {
            $check_names[$v['account_id']] = $v;
        }

        //所有的子销售数据
        $rs = $oSalesItems->getList('*',array('sale_id'=>$sale_ids));

        foreach($rs as $v) {
            $sales_items[$v['sale_id']][] = $v;
        }

         foreach( $list as $aFilter ){
                $aOrder = $aFilter;

                $shop_name = $shops[$aOrder['shop_id']]['name'];

                $branch_name = $branchs[$aOrder['branch_id']];

                $member_uname = $members[$aOrder['member_id']]['uname'];

                $delivery_bn = $deliverys[$aOrder['delivery_id']]['delivery_bn'];
                $ship_name = $deliverys[$aOrder['delivery_id']]['ship_name'];
                $ship_addr = $deliverys[$aOrder['delivery_id']]['ship_addr'];
                $ship_zip = $deliverys[$aOrder['delivery_id']]['ship_zip'];
                $ship_tel = $deliverys[$aOrder['delivery_id']]['ship_tel'];
                $ship_email = $deliverys[$aOrder['delivery_id']]['ship_email'];
                $ship_mobile = $deliverys[$aOrder['delivery_id']]['ship_mobile'];

                $rd = explode(':', $deliverys[$aOrder['delivery_id']]['ship_area']);
                if($rd[1]){
                  $ship_area = str_replace('/', '-', $rd[1]);
                }
                
                /*------------------------------------------------------ */
                //-- [拆单]获取订单对应多个发货单 ExBOY
                /*------------------------------------------------------ */
                if($split_seting)
                {
                    $dly_sql    = "SELECT dord.delivery_id, d.delivery_bn, d.logi_no FROM sdb_ome_delivery_order AS dord 
                                LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id) 
                                WHERE dord.order_id='".$aOrder['order_id']."' AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                                AND d.status NOT IN('failed','cancel','back','return_back')";
                    $delivery_list    = kernel::database()->select($dly_sql);
                    
                    #获取订单对应所有发货单
                    if($delivery_list && count($delivery_list) > 1)
                    {
                        $delivery_bn    = '';
                        $deliverys[$aOrder['delivery_id']]['logi_no']    = '';
                        
                        foreach($delivery_list as $key_i => $dly_val)
                        {
                            $delivery_bn            .= ' | '.$dly_val['delivery_bn'];
                            $deliverys[$aOrder['delivery_id']]['logi_no']    .= ' | '.$dly_val['logi_no'];
                        }
                        
                        $delivery_bn        = substr($delivery_bn, 2);
                        $deliverys[$aOrder['delivery_id']]['logi_no']    = substr($deliverys[$aOrder['delivery_id']]['logi_no'], 2);
                    }
                }

                $order_bn = $orders[$aOrder['order_id']]['order_bn'];

                $order_check_id = $check_names[$aOrder['order_check_id']]['login_name'];

                if($aOrder['sale_time']){
                    $sale_time = date("Y-m-d/H:i:s",$aOrder['sale_time']);
                }else{
                    $sale_time = '';
                }

                if($aOrder['order_check_time']){
                    $order_check_time = date("Y-m-d/H:i:s",$aOrder['order_check_time']);
                }else{
                    $order_check_time = '';
                }

                if($aOrder['order_create_time']){
                    $order_create_time = date("Y-m-d/H:i:s",$aOrder['order_create_time']);
                }else{
                    $order_create_time = '';
                }

                if($aOrder['paytime']){
                    $paytime = date("Y-m-d/H:i:s",$aOrder['paytime']);
                }else{
                    $paytime = '';
                }

                if($aOrder['ship_time']){
                    $ship_time = date("Y-m-d/H:i:s",$aOrder['ship_time']);
                }else{
                    $ship_time = '';
                }
                $aOrderRow = array();

                $aOrderRow['*:店铺名称']       = $shop_name;
                $aOrderRow['*:仓库名称']       = $branch_name['name'];
                $aOrderRow['*:销售单号']       = "=\"\"".$aOrder['sale_bn']."\"\"";
                $aOrderRow['*:订单号']         = "=\"\"".$order_bn."\"\"";
                $aOrderRow['*:发货单号']       = "=\"\"".$delivery_bn."\"\"";
                $aOrderRow['*:用户名称']       = $member_uname."\t";
                $aOrderRow['*:支付方式']       = $aOrder['payment'];
                $aOrderRow['*:销售金额']       = $aOrder['sale_amount'];
                $aOrderRow['*:优惠金额']       = $aOrder['discount'];
                $aOrderRow['*:商品总额']       = $aOrder['total_amount'];
                $aOrderRow['*:物流单号']       = $deliverys[$aOrder['delivery_id']]['logi_no']."\t";
                $aOrderRow['*:预收物流费']     = $aOrder['delivery_cost'];
                $aOrderRow['*:预估物流费']     = $aOrder['delivery_cost_actual'];
                $aOrderRow['*:配送费用']       = $aOrder['cost_freight'];
                $aOrderRow['*:附加费']         = $aOrder['additional_costs'];
                $aOrderRow['*:预存款']         = $aOrder['deposit'];
                $aOrderRow['*:是否开发票']     = $aOrder['is_tax'] == 'true'?'是':'否';
                $aOrderRow['*:订单审核人']     = $order_check_id;
                $aOrderRow['*:销售时间']       = $sale_time;
                $aOrderRow['*:下单时间']       = $order_create_time;
                $aOrderRow['*:付款时间']       = $paytime;
                $aOrderRow['*:订单审核时间']   = $order_check_time;
                $aOrderRow['*:发货时间']       = $ship_time;
                $aOrderRow['*:收货人姓名']     = $ship_name;
                $aOrderRow['*:收货人地区']     = $ship_area;
                $aOrderRow['*:收货人地址']     = $ship_addr;
                $aOrderRow['*:收货人邮编']     = $ship_zip;
                $aOrderRow['*:收货人固定电话'] = $ship_tel;
                $aOrderRow['*:收货人Email']    = $ship_email;
                $aOrderRow['*:收货人手机']     = $ship_mobile;

                $data['content']['sales'][]  = $this->charset->utf2local('"'.implode( '","', $aOrderRow ).'"');

                $objects = $sales_items[$aOrder['sale_id']];
                if ($objects){
                     foreach ($objects as $obj){
                        $orderObjRow = array();
                        $orderObjRow['*:销售单号']   ="=\"\"".$aOrder['sale_bn']."\"\"";
                        $orderObjRow['*:订单号']     = "=\"\"".$order_bn."\"\"";
                        $orderObjRow['*:货号']       = $obj['bn'];
                        $orderObjRow['*:商品名称']   = $obj['name'];
                        $orderObjRow['*:商品规格']   = $obj['spec_name'];
                        $orderObjRow['*:吊牌价']   = $obj['price'];
                        $orderObjRow['*:数量']   = $obj['nums'];
                        $orderObjRow['*:货品优惠']   = $obj['pmt_price'];
                        $orderObjRow['*:数量']   = $obj['nums'];
                        $orderObjRow['*:销售总价']   = $obj['sale_price'];
                        $orderObjRow['*:平摊优惠']   = $obj['apportion_pmt'];
                        $orderObjRow['*:销售金额']   = $obj['sales_amount'];
                        $data['content']['sales_items'][] = $this->charset->utf2local('"'.implode( '","', $orderObjRow ).'"');
                     }
                }
        }

        $data['name'] = 'sales'.date("YmdHis");
        return true;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();
         foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        echo implode("\n",$output);
    }

    public function modifier_order_check_id($row){

        switch($row){
            case 0:
                $ret = '无';
                break;
            case 16777215:
                $ret = '系统';
                break;
            default:
                $ret = $this->_getUserName($row);
                break;
        }

        return $ret;

    }

    private function _getUserName($uid) {
        if (self::$__USERS === null) {
            self::$__USERS = array();
            $rows = app::get('desktop')->model('users')->getList('*');
            foreach($rows as $row) {
                self::$__USERS[$row['user_id']] = $row['name'];
            }
        }

        if (isset(self::$__USERS[$uid])) {
            return self::$__USERS[$uid];
        } else {
            return '无';
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
        $type = 'bill';
        if ($logParams['app'] == 'sales' && $logParams['ctl'] == 'admin_sales') {
            $type .= '_salesBill_sales';
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
        if ($logParams['app'] == 'sales' && $logParams['ctl'] == 'admin_sales') {
            $type .= '_salesBill_sales';
        }
        $type .= '_import';
        return $type;
    }
    public function getexportdetail($fields,$filter,$offset=0,$limit=1,$has_title=false)
    {
        
        $sales_arr = $this->getList('sale_bn,order_id,sale_id', array('sale_id' => $filter['sale_id']), 0, -1);
        foreach ($sales_arr as $sale) {
            $sales_bn[$sale['sale_id']] = $sale['sale_bn'];
            $sales_order_ids[$sale['order_id']] = $sale['sale_id'];
            $order_ids[] = $sale['order_id'];
        }

        // 所有的订单信息
        $ordersObj = app::get('ome')->model('orders');
        $orders_arr = $ordersObj->getList('order_id,order_bn',array('order_id'=>$order_ids));
        foreach($orders_arr as $order) {
            if(isset($sales_order_ids[$order['order_id']])){
                $orders_bn[$sales_order_ids[$order['order_id']]] = $order['order_bn'];
            }
        }
        $archiveordObj = app::get('archive')->model('orders');
        $orders_arr = $archiveordObj->getList('order_id,order_bn',array('order_id'=>$order_ids));
        foreach($orders_arr as $order) {
            if(isset($sales_order_ids[$order['order_id']])){
                $orders_bn[$sales_order_ids[$order['order_id']]] = $order['order_bn'];
            }
        }
        $saleItemsObj = app::get('ome')->model('sales_items');
        $sale_items_arr = $saleItemsObj->getList('*',array('sale_id'=>$filter['sale_id']));
        $row_num = 1;
        if($sale_items_arr){
            foreach ($sale_items_arr as $key => $sale_item) {
                $saleItemRow['*:销售单号']   = isset($sales_bn[$sale_item['sale_id']]) ? mb_convert_encoding($sales_bn[$sale_item['sale_id']], 'GBK', 'UTF-8') : '-';
                $saleItemRow['*:订单号']     = isset($orders_bn[$sale_item['sale_id']]) ? mb_convert_encoding($orders_bn[$sale_item['sale_id']], 'GBK', 'UTF-8') : '-';
                $saleItemRow['*:货号']       = mb_convert_encoding($sale_item['bn'], 'GBK', 'UTF-8');
                $saleItemRow['*:商品名称']   = mb_convert_encoding($sale_item['name'], 'GBK', 'UTF-8');
                $saleItemRow['*:商品规格']   = mb_convert_encoding($sale_item['spec_name'], 'GBK', 'UTF-8');
                $saleItemRow['*:吊牌价']   = $sale_item['price'];
                $saleItemRow['*:货品优惠']   = $sale_item['pmt_price'];
                $saleItemRow['*:数量']   = $sale_item['nums'];
                $saleItemRow['*:销售总价']   = $sale_item['sale_price'];
                $saleItemRow['*:平摊优惠']   = $sale_item['apportion_pmt'];
                $saleItemRow['*:销售金额']   = $sale_item['sales_amount'];

                $data[$row_num] = implode(',', $saleItemRow);
                $row_num++;
            }
        }

        //明细标题处理
        if($data && $has_title){
            $title = array(
                '*:销售单号',
                '*:订单号',
                '*:货号',
                '*:商品名称',
                '*:商品规格',
                '*:吊牌价',
                '*:货品优惠',
                '*:数量',
                '*:销售总价',
                '*:平摊优惠',
                '*:销售金额',
            );

            foreach ((array)$title as $key => $value) {
                $title[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }

            $data[0] = implode(',', $title);
        }

        ksort($data);
        return $data;
    }

    /**
     * 订单导出列表扩展字段
     */
    function export_extra_cols(){
        return array(
            'column_ship_name' => array('label'=>'收货人姓名','width'=>'100','func_suffix'=>'ship_name'),
            'column_ship_area' => array('label'=>'收货人地区','width'=>'100','func_suffix'=>'ship_area'),
            'column_ship_addr' => array('label'=>'收货人地址','width'=>'150','func_suffix'=>'ship_addr'),
            'column_ship_zip' => array('label'=>'收货人邮编','width'=>'100','func_suffix'=>'ship_zip'),
            'column_ship_tel' => array('label'=>'收货人固定电话','width'=>'100','func_suffix'=>'ship_tel'),
            'column_ship_email' => array('label'=>'收货人Email','width'=>'100','func_suffix'=>'ship_email'),
            'column_ship_mobile' => array('label'=>'收货人手机','width'=>'100','func_suffix'=>'ship_mobile'),
            'column_delivery_bn' => array('label'=>'发货单号','width'=>'100','func_suffix'=>'delivery_bn'),
        );
    }

    /**
     * 收货人姓名扩展导出字段
     */
    function export_extra_ship_name($rows){
        return kernel::single('sales_exportextracolumn_sales_shipname')->process($rows);
    }

    /**
     * 收货人地区扩展导出字段
     */
    function export_extra_ship_area($rows){
        return kernel::single('sales_exportextracolumn_sales_shiparea')->process($rows);
    }

    /**
     * 收货人地址扩展导出字段
     */
    function export_extra_ship_addr($rows){
        return kernel::single('sales_exportextracolumn_sales_shipaddr')->process($rows);
    }

    /**
     * 收货人邮编扩展导出字段
     */
    function export_extra_ship_zip($rows){
        return kernel::single('sales_exportextracolumn_sales_shipzip')->process($rows);
    }

    /**
     * 收货人固定电话扩展导出字段
     */
    function export_extra_ship_tel($rows){
        return kernel::single('sales_exportextracolumn_sales_shiptel')->process($rows);
    }

    /**
     * 收货人Email扩展导出字段
     */
    function export_extra_ship_email($rows){
        return kernel::single('sales_exportextracolumn_sales_shipemail')->process($rows);
    }

    /**
     * 收货人手机扩展导出字段
     */
    function export_extra_ship_mobile($rows){
        return kernel::single('sales_exportextracolumn_sales_shipmobile')->process($rows);
    }

    /**
     * 发货单号扩展导出字段
     */
    function export_extra_delivery_bn($rows){
        return kernel::single('sales_exportextracolumn_sales_deliverybn')->process($rows);
    }

    /**
     * 单据来源.
     * @param   type    $varname    description
     * @return  type    description
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

?>