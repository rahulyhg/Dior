<?php
class ome_mdl_order_lack extends dbeav_model{
    static $_shop = array();
    static $pay_status = array (
        0 => '未支付',
        1 => '已支付',
        2 => '处理中',
        3 => '部分付款',
        4 => '部分退款',
        5 => '全额退款',
        6 => '退款申请中',
        7 => '退款中',
        8 => '支付中',
      );
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'product_id' => array (
                    'type' => 'int unsigned',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'label' => 'ID',
                    'width' => 110,
                    'hidden' => true,
                    'editable' => false,
                    'orderby'=>false,
                ),
                'goods_bn' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '商品编号',
                    'width' => 120,
                     'orderby'=>false,
                    'order' => 10,
                ), 
                'goods_name' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '商品名称',
                    'width' => 120,
                   'orderby'=>false,
                    'order' => 20,
                ), 
                'bn' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '货号',
                    'width' => 120,
                    'searchtype' => 'has',
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'orderby'=>true,
                    'realtype' => 'varchar(50)',
                    'order' => 30,
                ),
                'product_name' => array (
                    'type' => 'varchar(200)',
                    'pkey' => true,
                    'label' => '货品名称',
                    'width' => 210,
                    'searchtype' => 'has',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'orderby'=>false,
                    'realtype' => 'varchar(200)',
                    'order' => 40,
                ),
                'spec_info' =>
                array (
                    'type' => 'longtext',
                    'label' => '规格',
                    'width' => 110,
                    'editable' => false,
                     'orderby'=>false,
                    'order' => 50,
                ),
               
              'supplier_name' => array (
                'type' => 'varchar(50)',
                'default' => 0,
                'label' => '供应商',
                'width' => 120,
              	'filtertype' => 'normal',
              	'filterdefault' => 'true',
                'orderby'=>false,
                'order' => 60,
                ),
                'order_freeze'=>array(
                 'type' => 'number',
                 'label' => '订单预占',
                 'default' => 0,
                 'orderby'=>true,
                 'order' => 70,
                 ),
                 'enum_store'=>array(
                    'type' => 'number', 
                    'label' => '库存可用',
                    'default' => 0,
               'orderby'=>true,
                    'order' => 77,
                 ),
                 'arrive_enum_store'=>array(
                    'type' => 'number', 
                    'label' => '库存可用(含在途)',
                    'default' => 0,
                  'orderby'=>false,
                    'order' => 78,
                 ),
                  'product_lack'=>array(
                    'type' => 'number',
                    'label' => '缺货数量',
                    'default' => 0,
                    'orderby'=>true,
                    'order' => 100,
                 ),
                'arrive_product_lack'=>array(
                     'type' => 'number',
                    'label' => '缺货数量(含在途)',
                    'default' => 0,
                    'orderby'=>false,
                    'order' => 101,
                 ),
           ),
            'idColumn' => 'product_id',
            'in_list' => array (
                0=>'goods_bn',
                1=>'goods_name',
                2 => 'bn',
                3 => 'product_name',
                4=>'spec_info',
                5=>'enum_store',
                6=>'product_lack',
            ),
            'default_in_list' => array (
                0=>'goods_bn',
                1=>'goods_name',
                2 => 'bn',
                3 => 'product_name',
                4=>'spec_info',
                5=>'enum_store',
                6=>'product_lack',
            ),
        );
        return $schema;
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $oGoods = &$this->app->model('goods');
        $branch_sql = '';
        if ($filter['branch_id']) {
            $branch_sql.=" WHERE branch_id=".$filter['branch_id'];
        }
        $sql = "SELECT i.product_id,g.bn as goods_bn,g.name as goods_name,i.bn,i.name  AS product_name,p.spec_info,p.goods_id,i.nums AS item_num,IFNULL(IF(bb.sum_store<bb.sum_store_freeze,0,bb.sum_store-bb.sum_store_freeze),0) AS enum_store,SUM(i.nums) AS order_freeze,SUM(i.nums)-IFNULL(IF(bb.sum_store<bb.sum_store_freeze,0,bb.sum_store-bb.sum_store_freeze),0) as product_lack  FROM sdb_ome_orders AS o 
LEFT JOIN sdb_ome_order_items AS i  ON o.order_id=i.order_id 
       LEFT JOIN 
(SELECT bpp. * , SUM( bpp.store ) AS sum_store, SUM( bpp.store_freeze ) AS sum_store_freeze
FROM (

SELECT branch_id, product_id, store, store_freeze
FROM sdb_ome_branch_product ".$branch_sql."
)bpp

GROUP BY bpp.product_id
)bb ON i.product_id=bb.product_id LEFT JOIN sdb_ome_products AS p ON i.product_id=p.product_id LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id
       WHERE o.ship_status IN('0','2') AND o.status='active' AND o.process_status IN ('unconfirmed','confirmed')  AND i.delete='false' AND ".$this->_filter($filter)." GROUP BY i.product_id,i.bn HAVING(order_freeze>enum_store";
        //库存可用搜索
        if(isset($filter['product_lack']) && $filter['product_lack']!=''){
            if($filter['_product_lack_search']=='nequal'){
                $sql.=' AND product_lack='.$filter['product_lack'].'';
            }else if($filter['_product_lack_search']=='than'){
                $sql.=' AND product_lack>'.$filter['product_lack'].'';
            }else if($filter['_product_lack_search']=='lthan'){
                $sql.=' AND product_lack<'.$filter['product_lack'].'';
            }
        }
        $sql.=')';

        $rows = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($rows, $cols);
        return $rows;
    }

    public function count($filter=null){
        $branch_sql = '';
        if ($filter['branch_id']) {
            $branch_sql.=" WHERE branch_id=".$filter['branch_id'];
        }
        $sql = "SELECT SUM(i.nums) AS order_freeze,i.nums AS item_num,IFNULL(IF(bb.sum_store<bb.sum_store_freeze,0,bb.sum_store-bb.sum_store_freeze),0) AS enum_store,SUM(i.nums)-IFNULL(IF(bb.sum_store<bb.sum_store_freeze,0,bb.sum_store-bb.sum_store_freeze),0) as product_lack FROM sdb_ome_orders AS o 
        LEFT JOIN sdb_ome_order_items AS i  ON o.order_id=i.order_id 
        LEFT JOIN 
(SELECT bpp. * , SUM( bpp.store ) AS sum_store, SUM( bpp.store_freeze ) AS sum_store_freeze
FROM (

SELECT branch_id, product_id, store, store_freeze
FROM sdb_ome_branch_product ".$branch_sql."
)bpp

GROUP BY bpp.product_id
)bb ON i.product_id=bb.product_id LEFT JOIN sdb_ome_products AS p ON i.product_id=p.product_id LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id
       WHERE o.ship_status IN('0','2') AND o.status='active' AND o.process_status IN ('unconfirmed','confirmed') AND i.delete='false' AND  ".$this->_filter($filter)." GROUP BY i.product_id,i.bn HAVING(order_freeze>enum_store";
        //库存可用搜索
        if(isset($filter['product_lack']) && $filter['product_lack']!=''){
            if($filter['_product_lack_search']=='nequal'){
                $sql.=' AND product_lack='.$filter['product_lack'].'';
            }else if($filter['_product_lack_search']=='than'){
                $sql.=' AND product_lack>'.$filter['product_lack'].'';
            }else if($filter['_product_lack_search']=='lthan'){
                $sql.=' AND product_lack<'.$filter['product_lack'].'';
            }
        }
        $sql.=')';

        $row = $this->db->select($sql);
        return count($row);
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        $where[] = 1;
        if ($filter['shop_id']) {
            $where[] =' o.shop_id="'.$filter['shop_id'].'"';
        }
        if ($filter['bn']) {
            $where[] = ' p.bn LIKE \''.addslashes($filter['bn']).'%\'';
        }
        
        if ($filter['product_name']) {
            $where[] = ' p.name LIKE \''.addslashes($filter['product_name']).'%\'';
        }
        if (!$filter['ifpay']) {
            $where[] = ' o.pay_status ="1"';
        }
        if (!$filter['failorder']) {
            $where[] = ' o.is_fail ="false"';
        }
        if (!$filter['abnormalorder']) {
            $where[] = ' o.abnormal ="false"';
        }
        if ($filter['product_id']) {
            if (is_array($filter['product_id'])) {
                $where[] = ' i.product_id in ('.implode(',',$filter['product_id']).')';
            }
        }
       if ($filter['type_id']) {
           $where[] = ' g.type_id='.$filter['type_id'];
       }
       if ($filter['brand_id']) {
           $where[] = ' g.brand_id='.$filter['brand_id'];
       }
        if ($filter['branch_id']) {
           $where[] = ' bb.branch_id='.$filter['branch_id'];
       }
        //库存可用搜索
        if(isset($filter['stock']) && $filter['stock']!=''){
            if($filter['_stock_search']=='nequal'){
                $where[]=' IFNULL(IF(bb.sum_store<bb.sum_store_freeze,0,bb.sum_store-bb.sum_store_freeze),0)='.$filter['stock'];
            }else if($filter['_stock_search']=='than'){
                $where[]='  IFNULL(IF(bb.sum_store<bb.sum_store_freeze,0,bb.sum_store-bb.sum_store_freeze),0)>'.$filter['stock'];
            }else if($filter['_stock_search']=='lthan'){
                $where[]='  IFNULL(IF(bb.sum_store<bb.sum_store_freeze,0,bb.sum_store-bb.sum_store_freeze),0)<'.$filter['stock'];
            }
        }

        return implode($where,' AND ');
    }

    
    
    /**
     * 获取商品总冻结库存
     * @param   product_id
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function get_stocklist($product_id)
    {
        $branch_product = $this->db->select("SELECT bp.store,bp.store_freeze,p.name as product_name,p.bn FROM sdb_ome_branch_product as bp left join sdb_ome_products as p on bp.product_id=p.product_id WHERE bp.product_id=".$product_id." AND bp.store_freeze>bp.store");
        return $branch_product ;
    }
    
     function io_title( $filter, $ioType='csv' ){

        switch( $filter ){
            case 'order_lack':
                $this->oSchema['csv'][$filter] = array(
                    '*:货号' => 'bn',
                    '*:货品名称' => 'product_name',
                    '*:规格' => 'spec_info',
                    //'*:供应商'=>'supplier_name',
                    '*:商品名称'=>'goods_name',
                    '*:商品编号'=>'goods_bn',
                    '*:订单预占'=>'order_freeze',
                    '*:库存可用'=>'enum_store',
                   // '*:库存可用(含在途)'=>'arrive_enum_store',
                    '*:缺货数量'=>'product_lack',
                 //'*:缺货数量(含在途)'=>'arrive_product_lack',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        
        $serialLogObj = app::get('ome')->model('product_serial_log');
        if( !$data['title']){
            $title = array();
            foreach($this->io_title('order_lack') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['order_lack'] = '"'.implode('","',$title).'"';
        }

        if( !$list=$this->getlist('*',$filter,0,-1) )return false;
       
        foreach( $list as $aFilter ){
            if ($aFilter['product_lack']<0) {
                $aFilter['product_lack'] = '-';
            }
            if ($aFilter['arrive_product_lack']<0) {
                $aFilter['arrive_product_lack'] = '-';
            }
            foreach( $this->oSchema['csv']['order_lack'] as $k => $v ){
                
                $pRow[$k] =  utils::apath( $aFilter,explode('/',$v) );
            }
            $data['content']['order_lack'][] =$this->charset->utf2local('"'.implode( '","', $pRow ).'"'); 
        }
        return false;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();

        foreach( $data['title'] as $k => $val ){
            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        }

        echo implode("\n",$output);
    }

    
    /**
     * 获取订单需要库存
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function get_orderStore($product_id)
    {
        $orderstore = $this->get_order($product_id);
        $store = array();
        foreach ($orderstore as $order ) {
            $store[] = $order['item_num'];
        }
        if ($store) {
            return array_sum($store);
        }
        return 0;
    }
    /**
     * 获取缺货订单商品数量
     * @param
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function get_order($product_id,$bn,$limit=-1,$offset=0)
    {
        $sql = "SELECT i.nums AS item_num,o.order_bn,o.shop_id,o.pay_status,o.createtime,o.pay_status,o.paytime,o.order_limit_time,IFNULL(IF(SUM(bp.store)<SUM(bp.store_freeze),0,SUM(bp.store)-SUM(bp.store_freeze)),0) AS enum_store FROM sdb_ome_orders AS o LEFT JOIN sdb_ome_order_items as i  ON o.order_id=i.order_id LEFT JOIN sdb_ome_branch_product as bp ON i.product_id=bp.product_id WHERE o.ship_status IN('0','2') AND o.status='active' AND o.process_status in ('unconfirmed','confirmed') AND i.product_id=".$product_id." AND i.bn='".$bn."' AND i.delete='false'   group by o.order_bn ORDER BY o.createtime DESC";

        if ($start >= 0 || $limit >= 0){
            $offset = ($offset >= 0) ? $offset . "," : '';
            $limit = ($limit >= 0) ? $limit : '18446744073709551615';
            $sql .= ' LIMIT ' . $offset . ' ' . $limit;
        }

        $orders = $this->db->select($sql );
        return $orders;
        
    }

    
    /**
     *获取冻结订单列表
     * @param
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function get_orderlist($product_id,$bn,$start,$limit)
    {
        $orders = $this->get_order($product_id,$bn,$start,$limit);
        foreach ($orders as &$order ) {
            $order['pay_status'] = self::$pay_status[$order['pay_status']];
            $order['shop_name'] = $this->getShop($order['shop_id']);
        }

        return $orders;
    }

    
    /**
     * 获取库存
     * @param
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function get_store($product_id)
    {
        $branch_product = $this->db->selectrow("SELECT sum(IF(bp.store<bp.store_freeze,0,bp.store-bp.store_freeze)) as sum_store,sum(arrive_store) as sum_arrive_store FROM sdb_ome_branch_product as bp WHERE bp.product_id=".$product_id." ");
        
        return $branch_product ;
    }

    
    /**
     * 返回供应商
     * @param
     * @return  array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getSupplierBygoods($goods_id)
    {
        $SQL = "SELECT s.name FROM sdb_purchase_supplier_goods as g LEFT JOIN sdb_purchase_supplier as s ON g.supplier_id = s.supplier_id WHERE g.goods_id=".$goods_id;

        $supplier = $this->db->select($SQL);
        $supplier_name = array();
        foreach ( $supplier as $su ) {
            $supplier_name[] = $su['name'];
        }

        if ($supplier_name) {
            $supplier_name = implode(',',$supplier_name);
            return $supplier_name;
        }else{
            return ;
        }
        
    }

    
    /**
     * 获取店铺
     * @param   varchar $shop_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    public  function getShop($shop_id)
    {
        $oShop = &$this->app->model('shop');
        if (!isset(self::$_shop[$shop_id])) {
            $shop = $oShop->dump($shop_id,'name');
            self::$_shop[$shop_id] = $shop['name'];
        }
        return self::$_shop[$shop_id];
    }

    
    /**
     * 在途库存
     * @param   product_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getArrivestore($product_id)
    {
        $sql = "SELECT count(*) as _count FROM sdb_purchase_po AS p LEFT JOIN sdb_purchase_po_items AS i ON p.po_id=i.po_id  LEFT JOIN (SELECT bp.product_id,bp.branch_id FROM sdb_ome_orders AS o LEFT JOIN sdb_ome_order_items AS oi  ON o.order_id=oi.order_id 
        LEFT JOIN sdb_ome_branch_product AS bp ON oi.product_id=bp.product_id  WHERE o.ship_status IN('0','2') AND o.status='active' AND o.process_status in ('unconfirmed','confirmed') AND 
        IFNULL(IF(bp.store<bp.store_freeze,0,bp.store-bp.store_freeze),0)<oi.nums AND oi.product_id=".$product_id." AND oi.delete='false' group by bp.branch_id)bb ON (p.branch_id=bb.branch_id) WHERE i.product_id=".$product_id." AND p.check_status='2'  AND eo_status IN('0','1','2')";
        $arrive_storelist = $this->db->selectrow($sql);
        return $arrive_storelist['_count'];
    }

    /**
     * 在途库存
     * @param   product_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getArrivestorelist($product_id,$pagelimit,$offset)
    {
        $sql = "SELECT SUM(i.num-i.in_num) AS arrive_store,p.arrive_time FROM sdb_purchase_po AS p LEFT JOIN sdb_purchase_po_items AS i ON p.po_id=i.po_id  LEFT JOIN (SELECT bp.product_id,bp.branch_id FROM sdb_ome_orders AS o LEFT JOIN sdb_ome_order_items AS oi  ON o.order_id=oi.order_id 
        LEFT JOIN sdb_ome_branch_product AS bp ON oi.product_id=bp.product_id  WHERE o.ship_status IN('0','2') AND o.status='active' AND o.process_status in ('unconfirmed','confirmed') AND 
        IFNULL(IF(bp.store<bp.store_freeze,0,bp.store-bp.store_freeze),0)<oi.nums AND oi.product_id=".$product_id." AND oi.delete='false' group by bp.branch_id)bb ON (p.branch_id=bb.branch_id) WHERE i.product_id=".$product_id." AND p.check_status='2'  AND p.eo_status IN('0','1','2') LIMIT $offset,$pagelimit";

        $arrive_storelist = $this->db->select($sql);
        return $arrive_storelist;
    }

    
    
    /**
     * 缺货数量
     * @param   int product_lack
     * @return 
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_product_lack($row)
    {
        return "<div style='width:48px;padding:2px;height:16px;background-color:red;float:left;'><span style='color:#eeeeee;'>$row</span></div>";
    }
}
?>