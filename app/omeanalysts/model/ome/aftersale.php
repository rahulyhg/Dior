<?php
class omeanalysts_mdl_ome_aftersale extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '退货情况汇总';

    public function get_count($filter=null){
        
        $sql = 'select sum(num) as total_nums,sum(money) as total_apply_money,sum(refunded) as total_refund_money from sdb_sales_aftersale_items AI left join sdb_sales_aftersale A on AI.aftersale_id = A.aftersale_id where '.$this->_filter($filter);
        
        $rows = $this->db->select($sql);

        return $rows[0];
    }

    public function count($filter=null){

        $sql = "select count(*) as _count from sdb_sales_aftersale_items AI left join sdb_sales_aftersale A on AI.aftersale_id = A.aftersale_id where ".$this->_filter($filter);
        $rows = $this->db->select($sql);
        return $rows[0]['_count'];
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        $sql = "select A.shop_id,A.order_id,A.reship_id,A.aftersale_time,A.return_type,A.problem_name,AI.product_name,AI.bn as product_bn,AI.num as aftersale_num,AI.saleprice,AI.price as return_price,AI.money as apply_money,AI.refunded as refundmoney,A.return_apply_id as refund_apply_id,A.return_apply_bn,A.refundtime,AI.branch_id,AI.bn from sdb_sales_aftersale_items AI left join sdb_sales_aftersale A on AI.aftersale_id = A.aftersale_id where ".$this->_filter($filter);

        if($orderType) $sql .= 'order by '.(is_array($orderType) ? implode($orderType, ' ') : $orderType);

        $rows = $this->db->selectLimit($sql,$limit,$offset);

        $refundids = $orderids = $reshipids = $bns = array();

        foreach($rows as $v){
            $bns[] = $v['bn'];
            $orderids[] = $v['order_id'];
            $reshipids[] = $v['reship_id'];
            $refundids[] = $v['refund_apply_id'];
        }

        $sql = 'select p.bn,p.spec_info,p.barcode as goods_bn,gt.name as goods_type,b.brand_name from sdb_ome_products p left join sdb_ome_goods g on p.goods_id = g.goods_id left join sdb_ome_goods_type gt on g.type_id = gt.type_id left join sdb_ome_brand b on g.brand_id = b.brand_id where p.bn in ("'.implode('","',$bns).'")';
  
        $get_bns = $this->db->select($sql);
   
        foreach($get_bns as $v){
            $product_info[$v['bn']] = $v;
            $pro_bns[] = $v['bn'];
        }
        
        $Obranch = app::get('ome')->model('branch');
        $oOrder = app::get('ome')->model('orders');
        $oReship = app::get('ome')->model('reship');
        $oRefund = app::get('ome')->model('refunds');
        $ObjSale = app::get('ome')->model('sales');

        $branch = $Obranch->getList('branch_id,name');

        foreach ($branch as $v) {
            $branchs[$v['branch_id']] = $v['name'];
        }

        $orderbns = $oOrder->getList('order_id,order_bn',array('order_id|in'=>$orderids));
        $sql = 'select
                    sale.order_id,iostock.bn,iostock.nums,iostock.unit_cost
                from sdb_ome_iostock iostock
                left join sdb_ome_sales sale on iostock.iostock_bn=sale.iostock_bn where sale.order_id in ('.implode(',', $orderids).')';
        #获取单位成本、出入库数量
        $_sale_info = $this->db->select($sql);
        $sale_cost_bn_info = array();
        $sale_cost_info = array();#销售成本信息
        foreach( $_sale_info as $v){
            //$sale_cost_info[$v['order_id']] = $v;
            $sale_cost_bn_info[$v['order_id']][$v['bn']] = $v;
        }

        foreach ($orderbns as $v) {
            $orders[$v['order_id']] = $v['order_bn'];
        }

        $reshipbns = $oReship->getList('reship_id,reship_bn',array('reship_id|in'=>$reshipids));

        foreach ($reshipbns as $v) {
            $reships[$v['reship_id']] = $v['reship_bn'];
        }

        $reshipbns = $oRefund->getList('refund_bn,refund_id',array('refund_id|in'=>$refundids));

        foreach ($reshipbns as $v) {
            $refunds[$v['refund_id']] = $v['refund_bn'];
        }

        foreach($rows as $k=>$v){
            $rows[$k]['branch_id'] = $branchs[$v['branch_id']];
            $rows[$k]['refund_apply_id'] = $v['return_apply_bn'];
            $rows[$k]['order_id'] = $orders[$v['order_id']];
            $order_id = $v['order_id'];
            if(array_key_exists($order_id,$sale_cost_bn_info)){
                #计算销售成本,销售成本=出入库数量*单位成本
                $rows[$k]['sale_cost'] = $sale_cost_bn_info[$order_id][$v['bn']]['nums']*$sale_cost_bn_info[$order_id][$v['bn']]['unit_cost'];
            }
            $rows[$k]['reship_id'] = $reships[$v['reship_id']];
            if(in_array($rows[$k]['bn'],$pro_bns)){
                $rows[$k]['goods_type'] = $product_info[$v['bn']]['goods_type'];
                $rows[$k]['brand_name'] = $product_info[$v['bn']]['brand_name'];
                $rows[$k]['goods_specinfo'] = $product_info[$v['bn']]['spec_info'];
                $rows[$k]['goods_bn'] = $product_info[$v['bn']]['goods_bn'];
            }else{
                foreach(kernel::servicelist('ome.product') as $name=>$object){
                    if(method_exists($object, 'getProductByBn')){
                        $pkg_info = $object->getProductByBn($v['bn']);
                        if(!empty($product_info)){
                            $rows[$k]['goods_specinfo'] = '-';
                            $rows[$k]['goods_type'] = '捆绑商品';
                            $rows[$k]['brand_name'] = '-';
                            $rows[$k]['goods_bn'] = '-';
                        }
                    }
                }

                if(!$pkg_info || empty($pkg_info)){
                    $rows[$k]['goods_specinfo'] = '-';
                    $rows[$k]['goods_type'] = '系统不存在此货号';
                    $rows[$k]['brand_name'] = '-';
                    $rows[$k]['goods_bn'] = '-';
                }

            }


        }


        return $rows;
    }
    
    public function _filter($filter){
        
        $where = array(' AI.return_type = "return" ');

        #店铺
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $where[] = ' A.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }

        
        if(isset($filter['return_type_id']) && $filter['return_type_id']){
            $where[] = ' A.return_type =\''.addslashes($filter['return_type_id']).'\'';
        }
        
        if(isset($filter['time_from']) && $filter['time_from']){
            $time_from = ' A.aftersale_time >='.strtotime($filter['time_from']);
            $where[] = $time_from;
            $ftime = $time_from;
        }

        if(isset($filter['time_to']) && $filter['time_to']){

            $time_to = ' A.aftersale_time <'.strtotime($filter['time_to'].' 23:59:59');
            $where[] = $time_to;
            $ftime .= ' AND '.$time_to;
        }
        
        if((isset($filter['problem_id'])&& $filter['problem_id'])){
            $Oproblem = app::get('ome')->model('return_product_problem');
            $problem_data = $Oproblem->getList('problem_name',array('problem_id'=>$filter['problem_id']));
            
            $where[] = ' problem_name = "'.$problem_data[0]['problem_name'].'"';
        }

        if((isset($filter['brand_id']) && $filter['brand_id'])||(isset($filter['goods_type_id']) && $filter['goods_type_id'])){

            $sql = "select AI.item_id,goods.brand_id,goods.type_id,goods.bn as goods_bn from sdb_sales_aftersale_items as AI left join sdb_sales_aftersale as A on AI.aftersale_id = A.aftersale_id left join sdb_ome_products as products on AI.bn=products.bn left join sdb_ome_goods as goods on products.goods_id=goods.goods_id
            where ".$ftime;

            #品牌
            if(isset($filter['brand_id']) && $filter['brand_id']){

                $sql .= ' AND goods.brand_id LIKE \''.addslashes($filter['brand_id']).'%\'';
                unset($filter['brand_id']);
            }


            #商品类型
            if(isset($filter['goods_type_id']) && $filter['goods_type_id']){
                $sql .= ' AND goods.type_id =\''.addslashes($filter['goods_type_id']).'\'';
                unset($filter['goods_type_id']);
            }

            $query = $this->db->select($sql);

            if ($query) {
                foreach($query as $qu){
                    $saleitem_ids[] = $qu['item_id'];
                }
                $where[] = " AI.item_id IN (".implode(',',$saleitem_ids).")";
            }else{
                $where[] = " 1=0 ";
            }

        }

        return implode($where,' AND ');
    } 

    public function get_schema(){

        $schema = array (
            'columns' => array (
                'item_id' =>
                array(
                  'type'  => 'table:aftersale_items@sales',
                ),                
                'shop_id' =>
                array (
                  'type'  => 'table:shop@ome',
                  'label' => '店铺名称',
                  'width' => 120,
                  'order' => 1,
                ),
                'order_id' => 
                array(
                    'type'  => 'table:orders@ome',
                    'label' => '订单号',
                    'width' => 120,
                    'order' => 2,                    
                ),
                'reship_id' => 
                array(
                    'type' => 'table:reship@ome',
                    'label' => '退换货单号',
                    'width' => 120,
                    'order' => 3,
                ),
                'aftersale_time' => 
                array(
                  'type' => 'time',
                  'label' => '售后创建时间',
                  'order' => 4,
                  'width' => 130,
                ),  
                'return_type' => 
                array(
                    'type' =>
                     array(
                        'return' => '退货',
                        'change' => '换货',
                     ),
                    'label' => '售后类型',
                    'width' => 95,
                    'order'=>5,
                ),
                'problem_name' =>
                array(
                    'type' => 'varchar(200)',
                    'label' => '售后服务类型',
                    'width' => 130,
                    'order' => 6,
                ),
                'goods_type' =>
                array(
                    'type' => 'table:goods_type@ome',
                    'label' => '商品类型',
                    'width' => 130,
                    'order' => 7,
                ),
                'brand_name' =>
                array(
                    'type' => 'table:brand@ome',
                    'label' => '品牌',
                    'width' => 130,
                    'order' => 8,
                ),
                'goods_bn' =>
                array(
                    'type' => 'varchar(30)',
                    'label' => '商品编码',
                    'width' => 130,
                    'order' => 9,
                ),
                'product_name' =>
                array(
                    'type' => 'varchar(200)',
                    'label' => '退货货品名称',
                    'width' => 130,
                    'order' => 10,
                ),
                'goods_specinfo'=>
                array(
                    'type' => 'varchar(200)',
                    'label' => '规格',
                    'width' => 130,
                    'order' => 11,
                ),
                'product_bn' =>
                array(
                    'type' => 'varchar(30)',
                    'label' => '退货货品货号',
                    'width' => 130,
                    'order' => 12,
                ),                
                'aftersale_num' =>
                array(
                  'type' => 'number',
                  'label' => '退货数量',
                  'width' => 100,
                  'order' => 13,                  
                ), 
                'saleprice' =>
                array(
                  'type' => 'number',
                  'label' => '销售额',
                  'width' => 100,
                  'order' => 14,                  
                ),
                'return_price' =>
                array(
                  'type' => 'number',
                  'label' => '退货单价',
                  'width' => 100,
                  'order' => 15,                  
                ), 
                'apply_money' => 
                array(
                    'type' => 'money',
                    'label' => '申请退款金额',
                    'width' => 75,
                    'order'=>16,
                ),          
                'refundmoney' => 
                array(
                    'type' => 'money',
                    'label' => '实际退款金额',
                    'width' => 75,
                    'order'=>17,
                ),
                'refund_apply_id' => 
                array(
                    'type' => 'varchar(32)',
                    'label' => '退款单号',
                    'width' => 130,
                    'order' => 18, 
                ),
                'refundtime' => 
                array(
                  'type' => 'time',
                  'label' => '退款时间',
                  'order' => 19,
                  'width' => 130,
                ), 
                'branch_id' =>
                array(
                  'type' => 'varchar(200)',
                  'editable' => false,
                  'label'=>'仓库名称',
                  'order' => 20,
                ),
                'sale_cost' =>
                array(
                        'type' => 'number',
                        'label' => '销售成本',
                        'width' => 100,
                        'order' => 21,
                ),
            ),
            'idColumn' => 'aftersale_id',
            'in_list' => array(
                0 => 'shop_id',
                1 => 'order_id',
                2 => 'reship_id',
                3 => 'aftersale_time',
                4 => 'return_type',
                5 => 'problem_name',
                6 => 'goods_type',        
                7 => 'brand_name',
                8 => 'goods_bn',
                9 => 'product_name',
                10 => 'goods_specinfo',
                11 => 'product_bn',
                12 => 'aftersale_num',
                13 => 'saleprice',
                14 => 'return_price',
                15 => 'apply_money',
                16 => 'refundmoney',
                17 => 'refund_apply_id',
                18 => 'refundtime',
                19 => 'branch_id',
                21 => 'sale_cost'
            ),
            'default_in_list' => array(
                0 => 'shop_id',
                1 => 'order_id',
                2 => 'reship_id',
                3 => 'aftersale_time',
                4 => 'return_type',
                5 => 'problem_name',
                6 => 'goods_type',        
                7 => 'brand_name',
                8 => 'goods_bn',
                9 => 'product_name',
                10 => 'goods_specinfo',
                11 => 'product_bn',
                12 => 'aftersale_num',
                13 => 'saleprice',
                14 => 'return_price',
                15 => 'apply_money',
                16 => 'refundmoney',
                17 => 'refund_apply_id',
                18 => 'refundtime',
                19 => 'branch_id',
                21 => 'sale_cost'
            ),
        );
        return $schema;
    }


    public function io_title( $ioType='csv' ){
    
        switch( $ioType ){
            case 'csv':
                $this->oSchema['csv']['main'] = array(
                    '*:店铺名称'     => 'shop_id',
                    '*:订单号'       => 'order_id',
                    '*:退换货单号'   =>'reship_id',
                    '*:售后创建时间' => 'aftersale_time',
                    '*:售后类型'     => 'return_type',
                    '*:售后服务类型' => 'problem_name',
                    '*:商品类型'     => 'goods_type',
                    '*:品牌'         => 'brand_name',
                    '*:商品编码'     => 'goods_bn',
                    '*:退货货品名称' => 'product_name',
                    '*:规格'         => 'goods_specinfo',
                    '*:退货货品货号' => 'product_bn',
                    '*:退货数量'     => 'aftersale_num',
                    '*:销售额'       => 'saleprice',
                    '*:退货单价'     => 'return_price',
                    '*:申请退款金额' => 'apply_money',
                    '*:实际退款金额' => 'refundmoney',
                    '*:退款单号'     => 'refund_apply_id',
                    '*:退款时间'     => 'refundtime',
                    '*:仓库名称'     => 'branch_id',
                    '*:销售成本'     => 'sale_cost' 
                );
            break;
        }
        $this->ioTitle[$ioType] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType];
    }
    
    public function export_csv($data){
        $output = array();
        $output[] = $data['title']['aftersale']."\n".implode("\n",(array)$data['content']['aftersale']);
        echo implode("\n",$output);
    }

    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){

        @ini_set('memory_limit','64M');

        if( !$data['title']['aftersale']){
            $title = array();
            foreach( $this->io_title('csv') as $k => $v ){
                $title[] = $v;
            }
            $data['title']['aftersale'] = mb_convert_encoding('"'.implode('","',$title).'"', 'GBK', 'UTF-8');
        }

        $limit = 100;
        
        if( !$list=$this->getlist('*',$filter,$offset*$limit,$limit) ) return false;
        
        $aftersaleRow = array();

        $oShop = app::get('ome')->model('shop');

        // 所有的店铺信息
        $rs = $oShop->getList('shop_id,shop_bn,name');
        foreach($rs as $v) {
            $shops[$v['shop_id']] = $v['name'];
        }

        foreach( $list as $aFilter ){

              $aftersaleRow['*:店铺名称'] = $shops[$aFilter['shop_id']];
              $aftersaleRow['*:订单号'] = "=\"\"".$aFilter['order_id']."\"\"";//$aFilter['order_id']."\t";
              $aftersaleRow['*:退换货单号'] = "=\"\"".$aFilter['reship_id']."\"\"";//$aFilter['reship_id']."\t";
              $aftersaleRow['*:售后创建时间'] = $aFilter['aftersale_time']?date('Y-m-d H:i:s',$aFilter['aftersale_time']):'-';
              $aftersaleRow['*:售后类型'] = ($aFilter['return_type'] == 'return')?'退货':'换货';
              $aftersaleRow['*:售后服务类型'] = $aFilter['problem_name'];
              $aftersaleRow['*:商品类型'] = $aFilter['goods_type'];
              $aftersaleRow['*:品牌'] = $aFilter['brand_name'];
              $aftersaleRow['*:商品编码'] = $aFilter['goods_bn'];
              $aftersaleRow['*:退货货品名称'] = $aFilter['product_name'];
              $aftersaleRow['*:规格'] = $aFilter['goods_specinfo'];
              $aftersaleRow['*:退货货品货号'] = $aFilter['product_bn'];
              $aftersaleRow['*:退货数量'] = $aFilter['aftersale_num'];
              $aftersaleRow['*:销售额'] = $aFilter['saleprice'];
              $aftersaleRow['*:退货单价'] = $aFilter['return_price'];
              $aftersaleRow['*:申请退款金额'] = $aFilter['apply_money'];
              $aftersaleRow['*:实际退款金额'] = $aFilter['refundmoney'];
              $aftersaleRow['*:退款单号'] = $aFilter['refund_apply_id']."\t";
              $aftersaleRow['*:退款时间'] = $aFilter['refundtime']?date('Y-m-d H:i:s',$aFilter['refundtime']):'-';
              $aftersaleRow['*:仓库名称'] = $aFilter['branch_id'];
              $aftersaleRow['*:销售成本'] = $aFilter['sale_cost'];

            $data['content']['aftersale'][] = mb_convert_encoding('"'.implode('","',$aftersaleRow).'"', 'GBK', 'UTF-8');
        }

        $data['name'] = $this->export_name.date("YmdHis");

        return true;
    }

    public function exportName(&$data){
        $data['name'] = $_POST['time_from'].'到'.$_POST['time_to'].$this->export_name;
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
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_salesReport_refundAnalysis';
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
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_salesReport_refundAnalysis';
        }
        $type .= '_import';
        return $type;
    }

    //根据过滤条件获取导出发货单的主键数据数组
    public function getPrimaryIdsByCustom($filter){
        $rows = array();
        $sql = "select AI.item_id from sdb_sales_aftersale_items AI left join sdb_sales_aftersale A on AI.aftersale_id = A.aftersale_id where ".$this->_filter($filter);

        $rows = $this->db->select($sql);

        $ids = array();
        foreach ($rows as $k => $row){
            $ids[] = $row['item_id'];
        }

        return $ids;
    }

    //根据主键id获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){

        $ids = $filter['item_id'];
        $sql = "select A.shop_id,A.order_id,A.reship_id,A.aftersale_time,A.return_type,A.problem_name,AI.product_name,AI.bn as product_bn,AI.num as aftersale_num,AI.saleprice,AI.price as return_price,AI.money as apply_money,AI.refunded as refundmoney,A.return_apply_id as refund_apply_id,A.return_apply_bn,A.refundtime,AI.branch_id,AI.bn from sdb_sales_aftersale_items AI left join sdb_sales_aftersale A on AI.aftersale_id = A.aftersale_id where AI.item_id in (".implode(',',$ids).")";
        $rows = $this->db->select($sql);
        $refundids = $orderids = $reshipids = $bns = array();
        foreach($rows as $v){
            $bns[] = $v['bn'];
            $orderids[] = $v['order_id'];
            $reshipids[] = $v['reship_id'];
            $refundids[] = $v['refund_apply_id'];
        }

        $sql = 'select p.bn,p.spec_info,p.barcode as goods_bn,gt.name as goods_type,b.brand_name from sdb_ome_products p left join sdb_ome_goods g on p.goods_id = g.goods_id left join sdb_ome_goods_type gt on g.type_id = gt.type_id left join sdb_ome_brand b on g.brand_id = b.brand_id where p.bn in ("'.implode('","',$bns).'")';
        $get_bns = $this->db->select($sql);
        foreach($get_bns as $v){
            $product_info[$v['bn']] = $v;
            $pro_bns[] = $v['bn'];
        }
        
        $Obranch = app::get('ome')->model('branch');
        $oOrder = app::get('ome')->model('orders');
        $oReship = app::get('ome')->model('reship');
        $oRefund = app::get('ome')->model('refunds');
        $ObjSale = app::get('ome')->model('sales');

        $branch = $Obranch->getList('branch_id,name');
        foreach ($branch as $v) {
            $branchs[$v['branch_id']] = $v['name'];
        }

        $sql = 'select
                    sale.order_id,iostock.bn,iostock.nums,iostock.unit_cost
                from sdb_ome_iostock iostock
                left join sdb_ome_sales sale on iostock.iostock_bn=sale.iostock_bn where sale.order_id in ('.implode(',', $orderids).')';
        #获取单位成本、出入库数量
        $_sale_info = $this->db->select($sql);
        $sale_cost_bn_info = array();
        $sale_cost_info = array();#销售成本信息
        foreach( $_sale_info as $v){
            //$sale_cost_info[$v['order_id']] = $v;
            $sale_cost_bn_info[$v['order_id']][$v['bn']] = $v;
        }

        $orderbns = $oOrder->getList('order_id,order_bn',array('order_id|in'=>$orderids));
        foreach ($orderbns as $v) {
            $orders[$v['order_id']] = $v['order_bn'];
        }

        $reshipbns = $oReship->getList('reship_id,reship_bn',array('reship_id|in'=>$reshipids));
        foreach ($reshipbns as $v) {
            $reships[$v['reship_id']] = $v['reship_bn'];
        }

        $reshipbns = $oRefund->getList('refund_bn,refund_id',array('refund_id|in'=>$refundids));
        foreach ($reshipbns as $v) {
            $refunds[$v['refund_id']] = $v['refund_bn'];
        }

        foreach($rows as $k=>$v){
            $rows[$k]['branch_id'] = $branchs[$v['branch_id']];
            $rows[$k]['refund_apply_id'] = $v['return_apply_bn'];
            $rows[$k]['order_id'] = $orders[$v['order_id']];
            $order_id = $v['order_id'];
            if(array_key_exists($order_id,$sale_cost_bn_info)){
                #计算销售成本,销售成本=出入库数量*单位成本
                $rows[$k]['sale_cost'] = $sale_cost_bn_info[$order_id][$v['bn']]['nums']*$sale_cost_bn_info[$order_id][$v['bn']]['unit_cost'];
            }
            $rows[$k]['reship_id'] = $reships[$v['reship_id']];
            if(in_array($rows[$k]['bn'],$pro_bns)){
                $rows[$k]['goods_type'] = $product_info[$v['bn']]['goods_type'];
                $rows[$k]['brand_name'] = $product_info[$v['bn']]['brand_name'];
                $rows[$k]['goods_specinfo'] = $product_info[$v['bn']]['spec_info'];
                $rows[$k]['goods_bn'] = $product_info[$v['bn']]['goods_bn'];
            }else{
                foreach(kernel::servicelist('ome.product') as $name=>$object){
                    if(method_exists($object, 'getProductByBn')){
                        $pkg_info = $object->getProductByBn($v['bn']);
                        if(!empty($product_info)){
                            $rows[$k]['goods_specinfo'] = '-';
                            $rows[$k]['goods_type'] = '捆绑商品';
                            $rows[$k]['brand_name'] = '-';
                            $rows[$k]['goods_bn'] = '-';
                        }
                    }
                }

                if(!$pkg_info || empty($pkg_info)){
                    $rows[$k]['goods_specinfo'] = '-';
                    $rows[$k]['goods_type'] = '系统不存在此货号';
                    $rows[$k]['brand_name'] = '-';
                    $rows[$k]['goods_bn'] = '-';
                }
            }
        }

        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        $oShop = app::get('ome')->model('shop');
        $rs = $oShop->getList('shop_id,shop_bn,name');
        foreach($rs as $v) {
            $shops[$v['shop_id']] = $v['name'];
        }

        $aftersaleRow = array();
        foreach( $rows as $aFilter ){
            $aftersaleRow['shop_id'] = $shops[$aFilter['shop_id']];
            $aftersaleRow['order_id'] = $aFilter['order_id'];
            $aftersaleRow['reship_id'] = $aFilter['reship_id'];
            $aftersaleRow['aftersale_time'] = $aFilter['aftersale_time']?date('Y-m-d H:i:s',$aFilter['aftersale_time']):'-';
            $aftersaleRow['return_type'] = ($aFilter['return_type'] == 'return')?'退货':'换货';
            $aftersaleRow['problem_name'] = $aFilter['problem_name'];
            $aftersaleRow['goods_type'] = $aFilter['goods_type'];
            $aftersaleRow['brand_name'] = $aFilter['brand_name'];
            $aftersaleRow['goods_bn'] = $aFilter['goods_bn'];
            $aftersaleRow['product_name'] = $aFilter['product_name'];
            $aftersaleRow['goods_specinfo'] = $aFilter['goods_specinfo'];
            $aftersaleRow['product_bn'] = $aFilter['product_bn'];
            $aftersaleRow['aftersale_num'] = $aFilter['aftersale_num'];
            $aftersaleRow['saleprice'] = $aFilter['saleprice'];
            $aftersaleRow['return_price'] = $aFilter['return_price'];
            $aftersaleRow['apply_money'] = $aFilter['apply_money'];
            $aftersaleRow['refundmoney'] = $aFilter['refundmoney'];
            $aftersaleRow['refund_apply_id'] = $aFilter['refund_apply_id']."\t";
            $aftersaleRow['refundtime'] = $aFilter['refundtime']?date('Y-m-d H:i:s',$aFilter['refundtime']):'-';
            $aftersaleRow['branch_id'] = $aFilter['branch_id'];
            $aftersaleRow['sale_cost'] = $aFilter['sale_cost'];

            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($aftersaleRow[$col])){
                    $aftersaleRow[$col] = mb_convert_encoding($aftersaleRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $aftersaleRow[$col];
                }
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }
}