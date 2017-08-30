<?php
class omedlyexport_mdl_ome_delivery extends ome_mdl_delivery{

    //是否有导出配置
    var $has_export_cnf = true;

    public $export_name = '发货单';

    function __construct() 
    {
        parent::__construct(app::get('ome'));
    }

    /**
     * 数据导出
     */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();        #第三方发货,选定全部，导出的过滤条件
        if($filter['ctl'] == 'admin_receipts_outer' && $filter['isSelectedAll'] == '_ALL_'){
            #已发货
            if($filter['view'] == 1){
                $filter['status'] = array(0 =>'succ');
            }
            #未发货
            if($filter['view'] == 2){
                $filter['status'] = array (0 => 'ready',1 => 'progress');
            }
            //$oBranch = &app::get('ome')->model('branch');
            $outerBranch = array();
            #第三方仓库
            $tmpBranchList = $oBranch->getList('branch_id',array('owner'=>'2'));
            #获取操作员管辖仓库
            foreach ($tmpBranchList as $key => $value) {
                $outerBranch[] = $value['branch_id'];
            }
            //$is_super = kernel::single('desktop_user')->is_super();
            if (!$is_super) {
                $branch_ids = $oBranch->getBranchByUser(true);
                if ($branch_ids) {
                    $filter['branch_id'] = $filter['branch_id'] ? $filter['branch_id'] : $branch_ids;
                    $filter['branch_id'] = array_intersect($filter['branch_id'], $outerBranch); //取管辖仓与第三方仓的交集
                } else {
                   $filter['branch_id'] = 'false';
                }
            } else {
                if($filter['branch_id']){
                    $filter['branch_id'] = $filter['branch_id'];
                 }else{
                    $filter['branch_id'] =  $outerBranch;
                 }
            }
         }
		#获取所有仓库名称
         $all_branch_info = $oBranch->getList('branch_id,name',array());
         $all_branch_name = array();
         foreach($all_branch_info as $v){
             $all_branch_name[$v['branch_id']] = $v['name'];
         }
         unset($all_branch_info);

        foreach($filter as $key=>$val){
            if(($filter[$key] == '' || empty($filter[$key])) && $key != 'parent_id'){
                unset($filter[$key]);
            }
        }
        $deliveryObj = &app::get('ome')->model('delivery');
        $proObj   = &app::get('ome')->model('products');
        $goodsObj = &app::get('ome')->model('goods');
        $obj_queue_items   = &app::get('ome')->model('print_queue_items');
        $deliveryObj->filter_use_like = true;
        $filterSql = $deliveryObj->_filter($filter,$tableAlias,$baseWhere);
        
        $deliveryColumns = array_keys($deliveryObj->_columns($filter,$tableAlias,$baseWhere));
        foreach($deliveryColumns as $col){
            if($col == 'delivery'){
                continue;
            }
            $filterSql = str_replace('.'.$col,'D.'.$col,str_replace('`sdb_ome_delivery`','',$filterSql));
            $filterSql = str_replace('AND delivery_id','AND D.delivery_id',$filterSql);
        }
        if($filterSql){
            $whereSql = ' WHERE '.$filterSql;
        }
        /*
        $sql = 'SELECT DI.item_id,O.order_bn,O.shop_id,O.tax_no,D.delivery_bn,D.member_id,D.logi_name,D.logi_no,D.ship_addr,D.ship_area,D.ship_name,D.ship_tel,D.ship_mobile,D.delivery_time,D.ship_zip,DI.bn,DI.product_name,OI.nums as number,OI.price,
            ROUND((O.cost_freight/OI.nums)*DI.number,3) AS freight,
            ROUND((O.cost_freight/OI.nums)*DI.number,3)+ROUND((OI.price)*OI.nums,3) as total
            FROM sdb_ome_delivery_items AS DI
            LEFT JOIN sdb_ome_delivery AS D
                    ON D.delivery_id = DI.delivery_id
            LEFT JOIN sdb_ome_delivery_order AS DO
                    ON DO.delivery_id = D.delivery_id
            LEFT JOIN sdb_ome_orders AS O
                    ON O.order_id = DO.order_id
            INNER JOIN sdb_ome_order_items AS OI
                    ON OI.order_id = O.order_id AND DI.bn=OI.bn '.$whereSql.' AND OI.delete=\'false\' group by OI.item_id ORDER BY D.delivery_id DESC';
		*/

        #[拆单]增加获取发货单D.branch_id对应仓库 ExBOY
        $delivery_list  = array();
        $sql = 'select D.delivery_id, D.branch_id from sdb_ome_delivery AS D '.$whereSql.' ORDER BY D.delivery_id DESC';
                $rows = $this->db->selectLimit($sql,$limit,$offset);
        if (!$rows) {
            return false;
        }
        $ids = array();
        foreach ($rows as $k => $row){
            $ids[] = $row['delivery_id'];
            
            $delivery_list[$row['delivery_id']] = $row;//仓库 ExBOY
        }
        unset($rows);

        $sql = 'SELECT DI.item_id, D.delivery_bn, D.delivery_id, D.member_id, D.logi_name, D.logi_no, D.ship_addr, D.ship_area, D.ship_name, D.ship_tel, D.ship_mobile, D.delivery_time, D.ship_zip, DI.bn, product.name product_name, DI.number as dn
            FROM sdb_ome_delivery_items AS DI
            LEFT JOIN sdb_ome_delivery AS D  ON D.delivery_id = DI.delivery_id 
            LEFT JOIN sdb_ome_products as product on DI.product_id=product.product_id
            where D.delivery_id in ('.implode(',',$ids).') ORDER BY D.delivery_id DESC';

        $rows = $this->db->select($sql);
        $tmp_delivery_info = array();
        foreach ($rows as $k => $row){
            $tmp_delivery_info[$row['delivery_id'].$row['bn']] = $row;
        }
        unset($rows);
        
        #[拆单]获取多个发货单对应订单信息 ExBOY
        $sql    = "SELECT DI.delivery_id, O.order_bn, O.custom_mark, O.mark_text, O.shop_id, O.tax_no, O.cost_freight, 
                    DI.bn, DI.price, DI.amount, DI.number, DI.product_id 
                    FROM sdb_ome_orders AS O 
                    LEFT JOIN sdb_ome_delivery_items_detail AS DI
                            ON DI.order_id = O.order_id 
                    WHERE DI.delivery_id in(".implode(',',$ids).") ORDER BY DI.delivery_id DESC";
        
        /*
        $sql = 'SELECT D.branch_id,O.order_bn,O.custom_mark,O.mark_text,O.shop_id,O.tax_no,O.cost_freight,OI.nums as number,ROUND((OI.sale_price/OI.nums),3) as price,OI.sale_price,DO.delivery_id,OI.bn,OI.product_id,D.branch_id
            FROM sdb_ome_order_items AS OI
            LEFT JOIN sdb_ome_orders AS O
                    ON O.order_id = OI.order_id
            LEFT JOIN sdb_ome_delivery_order AS DO
                    ON DO.order_id = O.order_id
			LEFT JOIN sdb_ome_delivery AS D
                    ON D.delivery_id = DO.delivery_id
            where D.delivery_id in ('.implode(',',$ids).') AND OI.delete=\'false\' ORDER BY D.delivery_id DESC, OI.bn ASC';
        */
        $rows = $this->db->select($sql);
        //备注显示方式
        $markShowMethod = &app::get('ome')->getConf('ome.order.mark');
        $tmp_order = array();
        foreach ($rows as $k => $row){
            
            #[拆单]独立获取branch_id值 ExBOY
            $row['branch_id']           = $delivery_list[$row['delivery_id']]['branch_id'];
            $rows[$k]['branch_id']      = $row['branch_id'];
            
             #同一订单运费只显示一次
             
             if(!isset($tmp_order[$row['order_bn']])){
                $tmp_order[$row['order_bn']] = $row['order_bn'];
                $cost_freight = round(($row['cost_freight']/$row['number'])*$tmp_delivery_info[$row['delivery_id'].$row['bn']]['dn'],3);
            }else{
                
                $cost_freight = 0;
            }
            if(isset($tmp_delivery_info[$row['delivery_id'].$row['bn']])){
                $rows[$k] = array_merge($row,$tmp_delivery_info[$row['delivery_id'].$row['bn']]);
                $rows[$k]['freight'] = $cost_freight;
                //$rows[$k]['total'] = $cost_freight+ROUND($row['sale_price'],3);
                
                $rows[$k]['total']  = $cost_freight + (ROUND($row['price'], 3) * $row['number']);//总价=单价*发货数量 ExBOY
            }
            $rows[$k]['branch_id'] = $all_branch_name[$row['branch_id']]?$all_branch_name[$row['branch_id']]:'-';
            #获取所有货位
            $_sql = 'select store_position from sdb_ome_branch_pos bpos left join sdb_ome_branch_product_pos  ppos on bpos. pos_id=ppos.pos_id where bpos.branch_id='.$row['branch_id'].' and product_id='.$row['product_id'];
            $_rows = $this->db->select($_sql);
            $_store_position = null;
            if(!empty($_rows[0])){
                #一个货品有多个货位时，中间要隔开
                foreach($_rows as $v){
                     $_store_position .= $v['store_position'].'|';
                }
            }
            #切掉尾部符号
            $_store_position  = substr_replace($_store_position,'',-1,1);
            $rows[$k]['store_position'] = $_store_position;
            $product_info = $proObj->dump(array('product_id'=>$row['product_id']),'goods_id,spec_desc');
            #处理货品多规格值
            $spec_value = '';
            if(is_array($product_info['spec_desc']['spec_value']) && !empty($product_info['spec_desc']['spec_value'])){
                $spec_value = implode('|',$product_info['spec_desc']['spec_value']);
            }
            $rows[$k]['spec_value']  = $spec_value;
            //计量单位
            if($product_info){
                $goodsInfo = $goodsObj->getList('unit',array('goods_id'=>$product_info['goods_id']));
            }
            $rows[$k]['unit']  = isset($goodsInfo[0]['unit']) ? $goodsInfo[0]['unit'] : '';
            $queue_items = $obj_queue_items->getlist('ident,ident_dly',array('delivery_id'=>$rows[$k]['delivery_id']));
            if($queue_items[0]['ident'] && $queue_items[0]['ident_dly']){
                $rows[$k]['ident'] = $queue_items[0]['ident'].'_'.$queue_items[0]['ident_dly'];
            }
            /* if($row['custom_mark']) {
                $custom_mark = unserialize($row['custom_mark']);
                if (is_array($custom_mark) || !empty($custom_mark)){
                    if($markShowMethod == 'all'){
                        foreach ($custom_mark as $_custom_mark ) {
                            $str_custom_mark .= $_custom_mark['op_content'];
                        }
                    }else{
                        $_memo = array_pop($custom_mark);
                        $str_custom_mark = $_memo['op_content'];
                    }
                }
                $rows[$k]['custom_mark'] = $str_custom_mark;
            }
            if($row['mark_text']) {
                $mark_text = unserialize($row['mark_text']);
                if (is_array($mark_text) || !empty($mark_text)){
                    if($markShowMethod == 'all'){
                        foreach ($mark_text as $im) {
                            $str_mark_text .= $im['op_content'];
                        }
                    }else{
                        $_memo = array_pop($mark_text);
                        $str_mark_text = $_memo['op_content'];
                    }
                }
                $rows[$k]['mark_text'] = $str_mark_text;
            } */
            unset($row,$_rows,$product_info);
        }
        $rows = $this->convert($rows);
        
        $item=array();
        $i=0;
        foreach($rows as $key=>$row){
            $ship_addr_arr = explode(':', $row['ship_area']);
            $rows[$key]['ship_area'] = $ship_addr_arr[1];
            $member = array();
            $memberObj = &app::get('ome')->model('members');
            $member = $memberObj->getList('uname',array('member_id'=>$row['member_id']),0,1);
            $rows[$key]['member_id'] = $member[0]['uname'];
            $rows[$key]['order_bn'] =$row['order_bn']."\t";
			$rows[$key]['logi_no'] .= "\t";
            $item_id = $row['item_id'];
            if(isset($item[$item_id])){
                $i++;
                $rows[$key]['item_id']= $item_id.'_'.$i;
            }else{
                $item[$item_id]=$item_id;
                $rows[$key]['item_id']= $item_id;
            }
        }
        return $rows;
    }

    //格式化输出的内容字段
    public function convert($rows, &$fields='', $has_detail=1){
        //反转扩展字段
        $fields = str_replace('column_custom_mark', 'custom_mark', $fields);
        $fields = str_replace('column_mark_text', 'mark_text', $fields);
        $fields = str_replace('column_tax_no', 'tax_no', $fields);
        $fields = str_replace('column_ident', 'ident', $fields);

        $tmp_rows = array();
        $schema = $this->get_schema();
        $detail_schema = $this->get_exp_detail_schema();
        //针对大而全的数据做格式化过滤，如果包含明细
        if($has_detail == 1){
            /*
            //找出不要的字段
            foreach($schema['in_list'] as $sk => $col){
                //将需要的字段从所有字段数组里去掉
                if(strpos($fields, $col) !== false){
                    unset($schema['in_list'][$sk]);
                }
            }

            foreach($rows as $key=>$row){
                foreach ($row as $column => $value) {
                    //不要的字段去掉
                    if(!in_array($column, $schema['in_list'])){
                        $tmp_rows[$key][$column] = $value;
                    }
                }
            }
            */

            //先处理主数据的排序
            foreach (explode(',', $fields) as $k => $col) {
                foreach ($rows as $key=>$row) {
                    foreach ($row as $cl => $value) {
                        //只保留配置的主字段
                        if($col == $cl){
                            $tmp_rows[$key][$col] = $row[$col];
                        }
                    }
                }
            }

            //继续处理明细数据的排序
            foreach ($detail_schema['columns'] as $col => $arr) {
                foreach ($rows as $key=>$row) {
                    foreach ($row as $cl => $value) {
                        //只保留配置的主字段
                        if($col == $cl){
                            $tmp_rows[$key][$col] = $row[$col];
                        }
                    }
                }
            }

        }else{
            $tmp_deliverys_bn = array();
            //先将数组合并,去掉重复记录
            foreach($rows as $key=>$row){
                
                if(!$tmp_deliverys_bn[$row['delivery_bn']]){
                    $tmp_deliverys_bn[$row['delivery_bn']] = $row['delivery_bn'];
                }else{
                    unset($rows[$key]);
                }
            }

            foreach (explode(',', $fields) as $k => $col) {
                foreach ($rows as $key=>$row) {
                    foreach ($row as $cl => $value) {
                        //只保留配置的主字段
                        if($col == $cl){
                            $tmp_rows[$key][$col] = $row[$col];
                        }
                    }
                }
            }
        }

       return $tmp_rows;

    }

    public function get_schema(){
        $schema = array (
            'columns' => array (
                'order_bn' => array(
                    'type' => 'varchar(32)',
                    'required' => true,
                    'label' => '订单号',
                    'width' => 125,
                    'editable' => false,
                ),
                'shop_id' => array(
                    'type' => 'table:shop@ome',
                    'label' => '来源店铺',
                    'width' => 75,
                    'editable' => false,
                ),
                'tax_no' => array(
                    'type' => 'varchar(50)',
                    'label' => '发票号',
                    'editable' => false,
                ),
                'member_id' => array(
                    'type' => 'varchar(50)',
                    'label' => '会员用户名',
                    'comment' => '订货会员ID',
                    'editable' => false,
                    'width' =>75,
                ),
                'logi_name' => array(
                    'type' => 'varchar(100)',
                    'label' => '物流公司',
                    'comment' => '物流公司名称',
                    'editable' => false,
                    'width' =>75,
                ),
                'freight' => array(
                    'type' => 'money',
                    'default' => '0',
                    'required' => true,
                    'label' => '配送费用',
                    'width' => 70,
                    'editable' => false,
                ),
                'logi_no' => array(
                    'type' => 'varchar(50)',
                    'default' => '0',
                    'label' => '物流单号',
                    'width' => 70,
                    'editable' => false,
                ),
               'ship_addr' => array(
                  'type' => 'varchar(100)',
                  'label' => '收货地址',
                  'comment' => '收货人地址',
                  'editable' => false,
                ),
                'ship_area' => array(
                  'type' => 'region',
                  'label' => '收货地区',
                  'comment' => '收货人地区',
                  'editable' => false,
                ),
                'ship_name' => array(
                  'type' => 'varchar(50)',
                  'label' => '收货人',
                  'comment' => '收货人姓名',
                  'editable' => false,
                ),
                'ship_tel' => array(
                  'type' => 'varchar(30)',
                  'label' => '收货人电话',
                  'comment' => '收货人电话',
                  'editable' => false,
                ),
                'ship_mobile' => array(
                  'type' => 'varchar(50)',
                  'label' => '收货人手机',
                  'comment' => '收货人手机',
                  'editable' => false,
                ),
                'ship_zip' => array(
                  'type' => 'varchar(20)',
                  'label' => '收货邮编',
                  'comment' => '收货人邮编',
                  'editable' => false,
                ),
                'delivery_time' => array(
                    'type' => 'time',
                    'label' => '发货时间',
                    'comment' => '发货时间',
                    'editable' => false,
                ),
                'delivery_bn' => array(
                    'type' => 'varchar(32)',
                    'label' => '发货单号',
                    'comment' => '配送流水号',
                    'editable' => false,
                ),
                'ident' => array(
                    'type' => 'varchar(64)',
                    'label' => '批次号',
                    'width' => 70,
                    'comment' => '本次打印的批次号',
                    'editable' => false,
                ),
                'custom_mark' => array(
                    'type' => 'longtext',
                    'label' => '买家留言',
                    'editable' => false,
                ),
                'mark_text' => array(
                    'type' => 'longtext',
                    'label' => '客服备注',
                    'editable' => false,
                ),   
                'branch_id' => array(
                    'type' => 'number',
                    'editable' => false,
                    'label' => '发货仓库',
                    'width' => 110,
                ),
                'create_time'=>array (
                  'type' => 'time',
                  'label' => '创建时间',
                    'editable' => false,
     
                ),
            ),
            'idColumn' => 'bn',
            'in_list' => array(
                0 => 'order_bn',
                1 => 'shop_id',
                2 => 'tax_no',
                3 => 'member_id',
                4 => 'logi_name',
                //5 => 'bn',
                //6 => 'product_name',
                //7 => 'number',
                //8 => 'price',
                9 => 'freight',
                //10 => 'total',
                11 => 'logi_no',
                12 => 'ship_addr',
                13 => 'ship_area',
                14 => 'ship_name',
                15 => 'ship_tel',
                16 => 'ship_mobile',
                17 => 'ship_zip',
                18 => 'delivery_time',
                19 => 'delivery_bn',
                //20 => 'item_id',
                //21=> 'store_position',
                //22=> 'spec_value',
                23=> 'ident',
                24=>'custom_mark',
                25=>'mark_text',
                26=> 'branch_id',
                27=>'create_time',
            ),
            'default_in_list' => array(
                0 => 'order_bn',
                1 => 'shop_id',
                2 => 'tax_no',
                3 => 'member_id',
                4 => 'logi_name',
                //5 => 'bn',
                //6 => 'product_name',
                //7 => 'number',
                //8 => 'price',
                9 => 'freight',
                //10 => 'total',
                11 => 'logi_no',
                12 => 'ship_addr',
                13 => 'ship_area',
                14 => 'ship_name',
                15 => 'ship_tel',
                16 => 'ship_mobile',
                17 => 'ship_zip',
                18 => 'delivery_time',
                19 => 'delivery_bn',
                //20 => 'item_id',
                //21=>  'store_position',
                //22=> 'spec_value',
                23=> 'ident',
                24=>'custom_mark',
                25=>'mark_text',
                26=>'branch_id',
                27=>'create_time',
            ),
        );
        return $schema;
    }

    //定义导出明细内容的相关字段
    public function get_exp_detail_schema(){
        $schema = array (
            'columns' => array (
                'bn' => array(
                    'type' => 'varchar(30)',
                    'label' => '商品货号',
                    'width' => 85,
                    'editable' => false,
                ),
                'product_name' => array(
                    'type' => 'varchar(200)',
                    'required' => true,
                    'default' => '',
                    'label' => '商品名称',
                    'width' => 190,
                    'editable' => false,
                ),
                'number' => array(
                    'type' => 'number',
                    'required' => true,
                    'default' => 0,
                    'label' => '购买数量',
                    'editable' => false,
                ),
                'price' => array(
                    'type' => 'money',
                    'default' => '0',
                    'required' => true,
                    'label' => '商品单价',
                    'editable' => false,
                ),
                'avgprice' => array(
                    'type' => 'money',
                    'default' => '0',
                    'required' => true,
                    'label' => '商品均单价',
                    'editable' => false,
                ),
                'total' => array(
                    'type' => 'money',
                    'default' => '0',
                    'label' => '总价',
                    'width' => 70,
                    'editable' => false,
                ),
                'item_id' => array(
                    'type' => 'int unsigned',
                    'label' => '发货单明细流水号',
                    'comment' => '发货单明细流水号',
                    'editable' => false,
                ),
                'store_position' => array(
                    'type' => 'varchar(100)',
                    'label' => '货位',
                    'comment' => '货位',
                    'editable' => false,
                ),
                'spec_value' => array(
                    'type' => 'varchar(100)',
                    'label' => '规格',
                    'comment' => '规格',
                    'editable' => false,
                ),
            ),
        );
        return $schema;
    }

    public function count($filter=null){
        $deliveryObj = &app::get('ome')->model('delivery');
        $deliveryObj->filter_use_like = true;
        $filterSql = $deliveryObj->_filter($filter,$tableAlias,$baseWhere);
        $deliveryColumns = array_keys($deliveryObj->_columns($filter,$tableAlias,$baseWhere));
        foreach($deliveryColumns as $col){
            if($col == 'delivery'){
                continue;
            }
            $filterSql = str_replace('.'.$col,'D.'.$col,str_replace('`sdb_ome_delivery`','',$filterSql));
            $filterSql = str_replace('AND delivery_id','AND D.delivery_id',$filterSql);
        }
        
        $sql = 'SELECT count(D.delivery_id) as _count FROM sdb_ome_delivery as D where '.$filterSql;
        
        $count = $this->db->selectrow($sql);
        return intval($count['_count']);
        
    }
    function export_csv($data,$exportType = 1 ){
        if(!$this->is_queue_export){
            $data['title'] = $this->charset->utf2local($data['title']);
            foreach ($data['contents'] as $key => $value) {
                $data['contents'][$key] = $this->charset->utf2local($value);
            }
        }

        $output = array();
        $output[] = $data['title']."\n".implode("\n",(array)$data['contents']);

        if ($this->is_queue_export == true) {
            return implode("\n",$output);
        } else {
            echo implode("\n",$output);
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
        $type = 'delivery';
        if ($logParams['app'] == 'omedlyexport' && $logParams['ctl'] == 'ome_delivery') {
            $type .= '_orders';
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
        $type = 'delivery';
        $type .= '_import';
        return $type;
    }


    //根据过滤条件获取导出发货单的主键数据数组
    public function getPrimaryIdsByCustom($filter){
        $oBranch = &app::get('ome')->model('branch');
        //$is_super = kernel::single('desktop_user')->is_super();
        #第三方发货,选定全部，导出的过滤条件
        if($filter['ctl'] == 'admin_receipts_outer' && $filter['isSelectedAll'] == '_ALL_'){
            #过滤子单
            $filter['parent_id'] = 0;
            #已发货
            if($filter['view'] == 1){
                $filter['status'] = array(0 =>'succ');
            }
            #未发货
            if($filter['view'] == 2){
                $filter['status'] = array (0 => 'ready',1 => 'progress');
            }
            //$oBranch = &app::get('ome')->model('branch');
            $outerBranch = array();
            #第三方仓库
            $tmpBranchList = $oBranch->getList('branch_id',array('owner'=>'2'));
            #获取操作员管辖仓库
            foreach ($tmpBranchList as $key => $value) {
                $outerBranch[] = $value['branch_id'];
            }

            $curr_op_id = $filter['export_op_id'];
            $userObj = app::get('desktop')->model('users');
            $userInfo = $userObj->dump($curr_op_id,'super');
            if (!$userInfo['super']) {
                $branch_ids = $oBranch->getBranchByOpId($curr_op_id);
                if ($branch_ids) {
                    $filter['branch_id'] = $filter['branch_id'] ? $filter['branch_id'] : $branch_ids;
                    $filter['branch_id'] = array_intersect($filter['branch_id'], $outerBranch); //取管辖仓与第三方仓的交集
                } else {
                   $filter['branch_id'] = 'false';
                }
            } else {
                if($filter['branch_id']){
                    $filter['branch_id'] = $filter['branch_id'];
                 }else{
                    $filter['branch_id'] =  $outerBranch;
                 }
            }
         }

        foreach($filter as $key=>$val){
            if(($filter[$key] == '' || empty($filter[$key])) && $key != 'parent_id'){
                unset($filter[$key]);
            }
        }
        $deliveryObj = &app::get('ome')->model('delivery');
        $deliveryObj->filter_use_like = true;
        $filterSql = $deliveryObj->_filter($filter,$tableAlias,$baseWhere);

        $deliveryColumns = array_keys($deliveryObj->_columns($filter,$tableAlias,$baseWhere));
        foreach($deliveryColumns as $col){
            if($col == 'delivery'){
                continue;
            }
            $filterSql = str_replace('.'.$col,'D.'.$col,str_replace('`sdb_ome_delivery`','',$filterSql));
            $filterSql = str_replace('AND delivery_id','AND D.delivery_id',$filterSql);
        }

        if($filter['sku']=='single'){
            $filterSql .= ' AND D.skuNum=1';
        }

        if($filter['sku']=='multi'){
            $filterSql .= ' AND D.skuNum!=1';
        }

        if($filterSql){
            $whereSql = ' WHERE '.$filterSql;
        }

        $sql = 'select D.delivery_id  from sdb_ome_delivery AS D '.$whereSql.' ORDER BY D.delivery_id DESC';
        $rows = $this->db->select($sql);
        if (!$rows) {
            return false;
        }
        $ids = array();
        foreach ($rows as $k => $row){
            $ids[] = $row['delivery_id'];
        }
        //error_log(var_export($ids,true)."\n\t",3,"/www/ids.log");
        return $ids;
    }

    //根据主键id获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){
        $ids = $filter['delivery_id'];
        $proObj   = &app::get('ome')->model('products');
        $obj_queue_items   = &app::get('ome')->model('print_queue_items');
        $oBranch = &app::get('ome')->model('branch');
        $dlyObj = &app::get('ome')->model('delivery');
        $dlyorderObj = &app::get('ome')->model('delivery_order');

        //获取所有仓库名称
        $all_branch_info = $oBranch->getList('branch_id,name',array());
        $all_branch_name = array();
        foreach($all_branch_info as $v){
            $all_branch_name[$v['branch_id']] = $v['name'];
        }
        unset($all_branch_info);

        $sql = 'SELECT DI.item_id, D.branch_id, D.delivery_bn, D.delivery_id, D.member_id, D.logi_name, D.logi_no, D.ship_addr, D.ship_area, D.ship_name, D.ship_tel, D.ship_mobile, D.delivery_time, D.ship_zip, DI.bn, product.name product_name, DI.number as dn,D.create_time 
            FROM sdb_ome_delivery_items AS DI
            LEFT JOIN sdb_ome_delivery AS D  ON D.delivery_id = DI.delivery_id 
            LEFT JOIN sdb_ome_products as product on DI.product_id=product.product_id
            where D.delivery_id in ('.implode(',',$ids).') ORDER BY D.delivery_id DESC';

        $rows = $this->db->select($sql);
        $tmp_delivery_info = array();
        foreach ($rows as $k => $row){
            $tmp_delivery_info[$row['delivery_id'].$row['bn']] = $row;
        }
        unset($rows);

        //[拆单]获取多个发货单对应订单信息 ExBOY
        $sql    = "SELECT DI.delivery_id, O.order_bn, O.custom_mark, O.mark_text, O.shop_id, O.tax_no, O.cost_freight, 
                    DI.bn, DI.price, DI.amount, DI.number, DI.product_id 
                    FROM sdb_ome_delivery_items_detail AS DI 
                    LEFT JOIN sdb_ome_orders AS O 
                            ON O.order_id = DI.order_id 
                    WHERE DI.delivery_id in(".implode(',',$ids).") ORDER BY DI.delivery_id DESC";

        $rows = $this->db->select($sql);
        //备注显示方式
        $markShowMethod = &app::get('ome')->getConf('ome.order.mark');
        $tmp_order = array();
        foreach ($rows as $k => $row){
           //新增发货单创建时间
           $rows[$k]['create_time'] = date('Y-m-d H:i:s',$row['create_time']);
            //同一订单运费只显示一次
            if(!isset($tmp_order[$row['order_bn']])){
                $tmp_order[$row['order_bn']] = $row['order_bn'];
                $cost_freight = round(($row['cost_freight']/$row['number'])*$tmp_delivery_info[$row['delivery_id'].$row['bn']]['dn'],3);
            }else{          
                $cost_freight = 0;
            }
            if(isset($tmp_delivery_info[$row['delivery_id'].$row['bn']])){
                $rows[$k] = array_merge($row,$tmp_delivery_info[$row['delivery_id'].$row['bn']]);
                $rows[$k]['freight'] = $cost_freight;
                $rows[$k]['total'] = $cost_freight +(ROUND($row['price'],3) * $row['number']);
            }
            $rows[$k]['branch_id'] = $all_branch_name[$rows[$k]['branch_id']]?$all_branch_name[$rows[$k]['branch_id']]:'-';
            #获取所有货位
            $_sql = 'select store_position from sdb_ome_branch_pos bpos left join sdb_ome_branch_product_pos  ppos on bpos. pos_id=ppos.pos_id where bpos.branch_id='.$row['branch_id'].' and product_id='.$row['product_id'];
            $_rows = $this->db->select($_sql);
            $_store_position = null;
            if(!empty($_rows[0])){
                #一个货品有多个货位时，中间要隔开
                foreach($_rows as $v){
                     $_store_position .= $v['store_position'].'|';
                }
            }
            #切掉尾部符号
            $_store_position  = substr_replace($_store_position,'',-1,1);
            $rows[$k]['store_position'] = $_store_position;
            //处理商品均单价
            $dly_order = $dlyorderObj->getlist('*',array('delivery_id'=>$row['delivery_id']),0,-1);
            $sale_orders = $dlyObj->getsale_price($dly_order);
            $rows[$k]['avgprice'] = $sale_orders[$row['bn']];

            //处理货品多规格值
            $tmp_pdt_spec = array();
            if(!isset($tmp_pdt_spec[$row['product_id']])){
                $product_info = $proObj->dump(array('product_id'=>$row['product_id']),'spec_desc');
                $spec_value = '';
                if(is_array($product_info['spec_desc']['spec_value']) && !empty($product_info['spec_desc']['spec_value'])){
                    $spec_value = implode('|',$product_info['spec_desc']['spec_value']);
                }
                $tmp_pdt_spec[$row['product_id']] = $spec_value;
            }else{
                $spec_value = $tmp_pdt_spec[$row['product_id']];
            }
            $rows[$k]['spec_value']  = $spec_value;

            $queue_items = $obj_queue_items->getlist('ident,ident_dly',array('delivery_id'=>$rows[$k]['delivery_id']));
            if($queue_items[0]['ident'] && $queue_items[0]['ident_dly']){
                $rows[$k]['ident'] = $queue_items[0]['ident'].'_'.$queue_items[0]['ident_dly'];
            }else{
                $rows[$k]['ident'] = '-';
            }

            $str_custom_mark ='';
            if($row['custom_mark']) {
                $custom_mark = unserialize($row['custom_mark']);
                if (is_array($custom_mark) || !empty($custom_mark)){
                    if($markShowMethod == 'all'){
                        foreach ($custom_mark as $_custom_mark ) {
                            $str_custom_mark .= $_custom_mark['op_content'];
                        }
                    }else{
                        $_memo = array_pop($custom_mark);
                        $str_custom_mark = $_memo['op_content'];
                    }
                }
                $rows[$k]['custom_mark'] = $str_custom_mark;
            }else{
                $rows[$k]['custom_mark'] = '-';
            }

            $str_mark_text ='';
            if($row['mark_text']) {
                $mark_text = unserialize($row['mark_text']);
                if (is_array($mark_text) || !empty($mark_text)){
                    if($markShowMethod == 'all'){
                        foreach ($mark_text as $im) {
                            $str_mark_text .= $im['op_content'];
                        }
                    }else{
                        $_memo = array_pop($mark_text);
                        $str_mark_text = $_memo['op_content'];
                    }
                }
                $rows[$k]['mark_text'] = $str_mark_text;
            }else{
                $rows[$k]['mark_text'] = '-';
            }
            unset($row,$_rows,$product_info);
        }
        
        $item=array();
        $i=0;
        foreach($rows as $key=>$row){
            $ship_addr_arr = explode(':', $row['ship_area']);
            $rows[$key]['ship_area'] = $ship_addr_arr[1];
            $member = array();
            $memberObj = &app::get('ome')->model('members');
            $member = $memberObj->getList('uname',array('member_id'=>$row['member_id']),0,1);
            $rows[$key]['member_id'] = $member[0]['uname'];
            $rows[$key]['order_bn'] .= "\t";
            $rows[$key]['logi_no'] .= "\t";
            $item_id = $row['item_id'];
            if(isset($item[$item_id])){
                $i++;
                $rows[$key]['item_id']= $item_id.'_'.$i;
            }else{
                $item[$item_id]=$item_id;
                $rows[$key]['item_id']= $item_id;
            }
        }
        //error_log(var_export($rows,true)."\n\t",3,"/www/be.log");
        $crows = $this->convert($rows, $fields, $has_detail);
        //error_log(var_export($crows,true)."\n\t",3,"/www/af.log");

        //使用csv的方式格式化导出数据
        $new_rows = $this->formatCsvExport($crows);

        $export_arr['content']['main'] = array();
        //如果是第一分片那么加上标题
        if($curr_sheet == 1){

            $title = array();
            $main_schema = $this->get_schema();
            $detail_schema = $this->get_exp_detail_schema();
            //error_log(var_export($new_rows,true)."\n\t",3,"/www/new_rows.log");

            foreach (explode(',', $fields) as $key => $col) {
                if(isset($main_schema['columns'][$col])){
                    $title[] = "*:".$main_schema['columns'][$col]['label'];
                }
            }

            if($has_detail == 1){
                foreach ($detail_schema['columns'] as $key => $col) {
                    $title[] = "*:".$col['label'];
                }
            }

            foreach ((array)$title as $key => $value) {
                $title[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }

            $export_arr['content']['main'][0] = implode(',', $title);
            unset($main_schema, $detail_schema);
        }

        $new_line = 1;
        foreach($new_rows as $row => $content){
            $tmp_arr = array();
            foreach ($content as $value) {
                $tmp_arr[] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }
            $export_arr['content']['main'][$new_line] = implode(',', $tmp_arr);
            $new_line++;
        }
        return $export_arr;

    }

    //导出重写该方法，直接通过自定义schema获取字段列表
    public function extra_cols(){
        return array();
    }

    //重写字段方法，导出格式化的时候会调用到，不在名单里的字段直接剔除
    public function _columns(){
        $main_schema = $this->get_schema();
        $detail_schema = $this->get_exp_detail_schema();
        return array_merge($main_schema['columns'], $detail_schema['columns']);
    }
}