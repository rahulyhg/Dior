<?php
class ome_mdl_product_serial extends dbeav_model{
    static $_branch_list = array();
    static $product_name = array();
    static $user_list = array();

    function io_title( $filter, $ioType='csv' ){

        switch( $filter ){
            case 'serial_log':
                $this->oSchema['csv'][$filter] = array(
                    '*:唯一码' => 'serial_number',
                    '*:货号' => 'bn',
                     '*:货品名称' => 'product_name',
                    '*:所在仓库' => 'branch_name',
                    '*:订单号' => 'order_bn',
                    '*:单据号' => 'delivery_bn',
                    '*:操作人' => 'act_owner',
                    '*:操作时间' => 'act_time',
                    '*:状态'=>'status',
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
            foreach($this->io_title('serial_log') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['serial_log'] = '"'.implode('","',$title).'"';
        }
        if( !$list=$this->getlist('*',$filter,0,-1) )return false;
        $rows = array();
        foreach ($list as $k => $row){
            $item_id = $row['item_id'];
            $product_id = $row['product_id'];
            $branch_id = $row['branch_id'];
            
            $serial_log = $serialLogObj->getlist('*',array('item_id'=>$item_id));
            foreach ( $serial_log as $log ) {
                $product = $this->_getProduct_name($product_id);
                $branch = $this->_getBranchlist($branch_id);
                $act_owner = $log['act_owner'];
                $user = $this->_getUser($act_owner);
                $act_time = date('Y-m-d H:i:s',$log['act_time']);
                $serial_status = $log['serial_status'];
                $bill_no = $log['bill_no'];
                $bill_detail = kernel::single('ome_finder_product_serial')->log_status($log);
                $rows[] = array(
                    'product_name'  =>   $product[$product_id],
                    'branch_name'   =>   $branch[$branch_id],
                    'serial_number'  =>    $row['serial_number'],
                    'bn'                   =>   $row['bn'],
                    'order_bn' =>implode(',',$bill_detail['orderBn'])."\t",
                    'delivery_bn'  =>$bill_detail['bill_no']."\t",
                    'act_owner'=>$user[$act_owner],
                    'act_time'=>$act_time,
                    'status'   =>$bill_detail['serial_status'],
                );
            }
            
        }
        foreach( $rows as $aFilter ){
            foreach( $this->oSchema['csv']['serial_log'] as $k => $v ){

                $pRow[$k] =  utils::apath( $aFilter,explode('/',$v) );
            }
            $data['content']['serial_log'][] =$this->charset->utf2local('"'.implode( '","', $pRow ).'"'); 
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
     *返回仓库列表
     * @param
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function _getBranchlist($branch_id)
    {
        $branchObj = $this->app->model('branch');       
        if (!self::$_branch_list[$branch_id]) {
            $branch = $branchObj->dump($branch_id,'name');
            self::$_branch_list[$branch_id] = $branch['name'];
        }
         return self::$_branch_list;
    }

    
    /**
     * 获取商品名称
     * @param
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function _getProduct_name($product_id)
    {
        $productObj = $this->app->model('products');
        if (!self::$product_name[$product_id]) {
            $product = $productObj->dump($product_id,'name');
            self::$product_name[$product_id] = $product['name'];
        }
        return self::$product_name;
    }

    
    /**
     * 返回状态值
     * @param   status
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function _getStatus($status)
    {
        switch($status){
            case 0:
                $status_value = '已入库';
                break;
            case 1:
                $status_value = '已出库';
                break;
            case 2:
                $status_value = '无效';
                break;
        }
        return $status_value;
    }

    
    /**
     * 获取用户列表
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function _getUser($act_owner)
    {
        
        $userObj = app::get('desktop')->model('users');
        if (!self::$user_list[$act_owner]) {
            $user = $userObj->dump($act_owner,'name');

            self::$user_list[$act_owner] = $user['name'];
        }
        return self::$user_list;
    }

    
    /**
     * 返回发货单订单等信息
     * @param   bill_no
     * @return  array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function _getBill($bill_no)
    {
        $result = array();
        $deliveryObj = app::get('ome')->model('delivery');
        $delivery = $deliveryObj->dump($data['bill_no'],'delivery_bn,process');
        $result['delivery_bn'] = $delivery['delivery_bn'];
        if($delivery['process']=='true'){
            $orderIds = $deliveryObj->getOrderIdByDeliveryId($data['bill_no']);
            $orders = $orderObj->getList('order_id,order_bn',array('order_id'=>$orderIds));
            foreach($orders as $key=>$val){
                $orderBn[$val['order_id']] = $val['order_bn'];
            }
            $result['orderBn'] = $orderBn;
        }
        return $result;
    }

     function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'serial_number'=>app::get('base')->_('唯一码'),
            
        );

        return array_merge($childOptions,$parentOptions);
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        $db = kernel::database();
         if (isset($filter['order_bn'])) {
             //
             $sql = "SELECT s.item_id FROM sdb_ome_orders as o left join sdb_ome_delivery_order as del on o.order_id=del.order_id left join sdb_ome_product_serial_log as s on del.delivery_id=s.bill_no WHERE o.order_bn='".$filter['order_bn']."'";

             $items = $this->db->select($sql);
             $item_id_list[] = 0;
             foreach ($items as $item ) {
                 $item_id_list[] = $item['item_id'];
             }
            $where .= ' AND item_id IN ('.implode(',', $item_id_list).')';
             unset($item_id_list,$items);
         }
         if (isset($filter['bill_no'])) {
             $sql = "SELECT s.item_id FROM sdb_ome_delivery as d left join sdb_ome_product_serial_log as s on d.delivery_id=s.bill_no WHERE d.delivery_bn='".$filter['bill_no']."'";

             $items = $this->db->select($sql);
            
             $item_id_list[] = 0;
             foreach ($items as $item ) {
                 $item_id_list[] = $item['item_id'];
             }
             //或者查询退货单号
             $reship_sql = "SELECT s.item_id FROM sdb_ome_reship as r left join sdb_ome_product_serial_log as s on r.reship_id=s.bill_no WHERE r.reship_bn='".$filter['bill_no']."'";

             $reship_items = $this->db->select($reship_sql);
             foreach ( $reship_items as $reship ) {
                $item_id_list[] = $reship['item_id'];
             }
             $where .= ' AND item_id IN ('.implode(',', $item_id_list).')';
             unset($item_id_list,$reship_items,$item);
         }

         if (isset($filter['product_name'])) {
            $sql = "SELECT del.delivery_id FROM sdb_ome_delivery_order AS del LEFT JOIN sdb_ome_orders AS o ON o.order_id = del.order_id LEFT JOIN sdb_ome_order_items AS oi ON oi.order_id = o.order_id  WHERE oi.name LIKE '".$filter['product_name']."'";
            $deliverys = $this->db->select($sql);
             $delivery_id[] = 0;
             foreach ($deliverys as $delivery ) {
                 $delivery_id[] = $delivery['delivery_id'];
             }
             $serial_log = $this->db->select("SELECT s.item_id FROM sdb_ome_product_serial_log as s WHERE s.bill_no IN (".implode(',',$delivery_id).")");

             $item_id_list[] = 0;
             foreach ($serial_log as $log ) {
                 $item_id_list[] = $log['item_id'];
             }
            $where .= ' AND item_id IN ('.implode(',', $item_id_list).')';
             unset($item_id_list,$items);
         }
         if (isset($filter['type_id'])) {
             $sql = "SELECT s.item_id FROM sdb_ome_product_serial as s LEFT JOIN sdb_ome_products as p ON s.product_id=p.product_id LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id WHERE g.type_id=".$filter['type_id'];
             $products = $this->db->select($sql);
             $item_id_list[] = 0;
             foreach ($products as $product ) {
                 $item_id_list[] = $product['item_id'];
             }
            $where .= ' AND item_id IN ('.implode(',', $item_id_list).')';
            unset($item_id_list,$products);
         }
         if (isset($filter['brand_id'])) {
             $sql = "SELECT s.item_id FROM sdb_ome_product_serial as s LEFT JOIN sdb_ome_products as p ON s.product_id=p.product_id LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id WHERE g.brand_id=".$filter['brand_id'];

            $products = $this->db->select($sql);
             $item_id_list[] = 0;
             foreach ($products as $product ) {
                 $item_id_list[] = $product['item_id'];
             }
            $where .= ' AND item_id IN ('.implode(',', $item_id_list).')';
            unset($product_ids,$item_id_list);
         }

         if (isset($filter['serarch_like']) && isset($filter['serial_number'])) {
            $serial = $this->db->select("SELECT s.item_id FROM sdb_ome_product_serial as s WHERE s.serial_number like '".$filter['serial_number']."%'");
            $item_id_list[] = 0;
            foreach ($serial as $log ) {
                $item_id_list[] = $log['item_id'];
            }
            if ($item_id_list) {
                $where .= ' AND item_id IN ('.implode(',', $item_id_list).')';
            }
             
             unset($serial,$item_id_list,$filter['serial_number']);
         }

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    
    /**
     * 根据货号返回商品相关信息
     * @param  bn
     * @return  array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getProductById($product_id)
    {
        $oProduct = $this->app->model('products');
        $oGoods = $this->app->model('goods');
        $product = $oProduct->dump($product_id);
        $goods_id = $product['goods_id'];
        $goods = $oGoods->dump($goods_id);

        $type_id = $goods['type']['type_id'];
        $brand_id = $goods['brand']['brand_id'];

        $oGoods_type = $this->app->model('goods_type');
        $goods_type = $oGoods_type->dump($type_id);
        $product['goods_type_name'] = $goods_type['name'];
        $oBrand = $this->app->model('brand');
        $brand = $oBrand->dump($brand_id);
        $product['brand_name'] = $brand['brand_name'];
        $product['goods_bn'] = $goods['bn'];

        return $product;
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
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_serial') {
            $type .= '_onlycode_list';
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
        $type .= '_onlycode_list';
        $type .= '_import';
        return $type;
    }

     
    /**
     * 根据bill_no,product_id返回唯一码
     * @param   bill_no
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function getSerialByproduct_id($bill_no)
    {
        $SQL = "SELECT s.serial_number,s.product_id FROM sdb_ome_product_serial as s LEFT JOIN sdb_ome_product_serial_log as l ON s.item_id=l.item_id WHERE l.bill_no=".$bill_no." AND serial_status='1' AND act_type='0'";
        $db = kernel::database();
        $serial = $db->select($SQL);
        $serial_log = array();
        foreach ($serial as $log ) {
            $serial_log[$log['product_id']][] = $log['serial_number'];
        }
        return $serial_log;
    }

    

}