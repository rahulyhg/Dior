<?php
class console_mdl_interface_iostocksearchs extends dbeav_model{

    public function count($filter = null)
    {
        $prefix = kernel::database()->prefix;
        $app_ome = app::get('ome');

        // 获取货品库存
        $mdl_branch_product = $app_ome->model('branch_product');
        $mdl_products       = $app_ome->model('products');
        $mdl_iostock        = $app_ome->model('iostock');

        //$sql = sprintf("SELECT COUNT(iostock_id) AS _count
                        //FROM (SELECT iostock_id
                              //FROM %s AS iostock
                              //WHERE 1 = 1
                                //AND %s
                              //GROUP BY iostock.bn, iostock.branch_id) AS _iostock", $mdl_iostock->table_name(true), $this->_filter($filter));

         $sql = sprintf("SELECT COUNT(iostock.iostock_id) as _count  FROM (
                        SELECT (iostock.iostock_id) FROM %s AS iostock,%s AS products,%s AS branch_product 
                        WHERE 1 = 1 AND iostock.bn = products.bn AND products.product_id = branch_product.product_id AND branch_product.branch_id = iostock.branch_id
                            AND %s
                        GROUP BY iostock.bn, iostock.branch_id) as iostock", $mdl_iostock->table_name(true), $mdl_products->table_name(true), $mdl_branch_product->table_name(true), $this->_filter($filter)
                            );
        $row = $mdl_iostock->db->select($sql);

        return intval($row[0]['_count']);
    }

    public function details($bn, $branch_id, $filter = array())
    {
        $prefix         = kernel::database()->prefix;
        $app_ome        = app::get('ome');
        $mdl_iostock    = $app_ome->model('iostock');

        // 库存类型
        $types = $this->get_branch_types();

        //获取sku，出库数，入库数，仓库名，时间，出入库类型，价格
        //$filter = array('time_to'=>$_SESSION['time_to'],'time_from'=>$_SESSION['time_from']);

//        $sql = "select op.bn,oi.iostock_bn,oi.original_bn,oi.iostock_price,oi.nums,oi.type_id,oi.create_time,ob.name as branch_name,oit.type_name as type_name,op.goods_id,og.bn as og_sku from ".kernel::database()->prefix."ome_iostock as oi left join ".kernel::database()->prefix."ome_products as op on oi.bn=op.bn left join ".kernel::database()->prefix."ome_goods  as og on og.goods_id=op.goods_id left join ".kernel::database()->prefix."ome_iostock_type as oit on oit.type_id = oi.type_id left join ".kernel::database()->prefix."ome_branch  as ob on ob.branch_id=oi.branch_id where oi.bn='$bn' and ob.branch_id='$branch_id' and ".$this->_filter($filter);
        $sql = sprintf("SELECT products.bn, iostock.iostock_bn, iostock.original_bn, iostock.iostock_price, iostock.nums, iostock.type_id, iostock.create_time, branch_product.branch_id, products.goods_id, products.bn as og_sku
                FROM %s AS iostock,%some_products AS products,%some_branch_product AS branch_product
                WHERE iostock.bn = '%s'
                    AND iostock.bn = products.bn
                    AND products.product_id = branch_product.product_id AND branch_product.branch_id = iostock.branch_id
                    AND iostock.branch_id = %s
                    AND %s ", $mdl_iostock->table_name(true), $prefix, $prefix, $bn, $branch_id, $this->_filter($filter).' ORDER BY create_time DESC ');
        $row = kernel::database()->select($sql);
        foreach($row as $key => $r)
        {
            $row[$key]['create_time']   = date("Y-m-d H:i:s", $r['create_time']);
            $row[$key]['type_name']     = $types[$r['type_id']];
        }
        return $row;
    }
    public function getlistbase($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        return parent::getList($cols, $filter, $offset, $limit, $orderType);
    }
    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $prefix = kernel::database()->prefix;
        $app_ome = app::get('ome');

        // 获取库存列表
        $branchs = $this->get_branch_list();

        // 获取货品库存
        $mdl_branch_product = $app_ome->model('branch_product');
        $mdl_products       = $app_ome->model('products');
        $mdl_iostock        = $app_ome->model('iostock');
        if(!$cols)
            $cols = $this->defaultCols;
        if(!empty($this->appendCols))
            $cols.=','.$this->appendCols;
        if($this->use_meta)
             $meta_info = $this->prepare_select($cols);
        $orderType = $orderType ? $orderType : $this->defaultOrder;
        $sql = sprintf("SELECT branch_product.branch_id, branch_product.product_id, products.name, products.bn, branch_product.store, branch_product.store_freeze
                        FROM %s AS iostock,%s AS products,%s AS branch_product
                        WHERE 1 = 1
                            AND iostock.bn = products.bn
                            AND products.product_id = branch_product.product_id AND branch_product.branch_id = iostock.branch_id
                            AND %s
                        GROUP BY iostock.bn, iostock.branch_id", $mdl_iostock->table_name(true), $mdl_products->table_name(true), $mdl_branch_product->table_name(true), $this->_filter($filter));
//        $sql = sprintf("SELECT branch_product.branch_id, branch_product.product_id, products.name, products.bn, branch_product.store, branch_product.store_freeze,
//                        FROM %s AS branch_product, %s AS products
//                        WHERE branch_product.product_id = products.product_id
//                            AND %s", $mdl_branch_product, $mdl_products, $this->_filter($filter));
        //if($orderType)
            //$sql .= ' ORDER BY '.(is_array($orderType) ? implode($orderType, ' ') : $orderType);

        $branch_info = $this->db->selectLimit($sql, $limit, $offset);

        $arr = array();
        foreach($branch_info as $key => $branch)
        {
            $arr[$key]['bn']            = $branch['bn'];
            $arr[$key]['goods_name']    = $branch['name'];
            $arr[$key]['store']         = $branch['store'] - $branch['store_freeze'];
            $arr[$key]['store_name']    = $branchs[$branch['branch_id']];
            $arr[$key]['store_freeze']  = $branch['store_freeze'];
            $arr[$key]['stores']        = $branch['store'];
            $arr[$key]['time_to']       = $filter['time_to'];
            $arr[$key]['time_from']     = $filter['time_from'];
            $arr[$key]['bn_branch']     = sprintf("%s*$**%s", $branch['branch_id'], $branch['bn']);
        }
        return $arr;
    }

    /**
     * 获取库存列表
     *
     * @return          array
     */
    public function get_branch_list()
    {
        $_branchs = app::get('ome')->model('branch')->getlist('branch_id, name');
        $branchs = array();
        foreach ($_branchs as $value)
        {
            $branchs[$value['branch_id']] = $value['name'];
        }
        return $branchs;
    }

    /**
     * 获取库存列表
     *
     * @return          array
     */
    public function get_branch_types()
    {
        $_types = app::get('ome')->model('iostock_type')->getlist('type_id, type_name');
        $types = array();
        foreach ($_types as $value)
        {
            $types[$value['type_id']] = $value['type_name'];
        }

        return $types;
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $_SESSION['fil'] = $filter;
        $where = array(1);
            if(isset($filter['time_from']) && $filter['time_from']){
                $where[] = ' create_time >='.strtotime($filter['time_from']);
            }
            if(isset($filter['time_to']) && $filter['time_to']){
                $where[] = ' create_time <'.(strtotime($filter['time_to'])+86400);
            }
            if(isset($filter['bn'])){
                $where[] = ' iostock.bn like \'%' . $filter['bn'] . '%\' ';
            }
            if(isset($filter['goods_name'])){
                $where[] = ' products.name like \'%' . $filter['goods_name'] . '%\' ';
            }
            if(isset($filter['store_name'])){
				if(is_array($filter['store_name']))
					$where[] = ' iostock.branch_id in (' . implode($filter['store_name'],' ,').')';
				else $where[] = ' iostock.branch_id =' . $filter['store_name'];
                
            }
            //可用库存的查询
            if(isset($filter['_store_search'])){
                $var = ($filter['_store_search'] == 'between' ? array($filter['store_from'],$filter['store_to']) : $filter['store']);
                $where[] = $this->getMultiFilter('(branch_product.store-CAST(branch_product.store_freeze AS SIGNED))',$filter['_store_search'],$var);
            }
            //实际库存的查询
            if(isset($filter['_stores_search'])){
                $var = ($filter['_stores_search'] == 'between' ? array($filter['stores_from'],$filter['stores_to']) : $filter['stores']);
                $where[] = $this->getMultiFilter('branch_product.store',$filter['_stores_search'],$var);
            }
        return implode($where,' AND ');
     }

     /**
     *解析搜索类型
     *
     **/
    function getMultiFilter($col,$type,$var){
        $FilterArray= array('than'=>' > '.$var,
                            'lthan'=>' < '.$var,
                            'nequal'=>' = \''.$var.'\'',
                            'noequal'=>' <> \''.$var.'\'',
                            'tequal'=>' = \''.$var.'\'',
                            'sthan'=>' <= '.$var,
                            'bthan'=>' >= '.$var,
                            'has'=>' like \'%'.$var.'%\'',
                            'head'=>' like \''.$var.'%\'',
                            'foot'=>' like \'%'.$var.'\'',
                            'nohas'=>' not like \'%'.$var.'%\'',
                            'between'=>" {$col}>=".$var[0].' and '." {$col}<".$var[1],
                            'in' =>" in ('".implode("','",(array)$var)."') ",
                            'notin' =>" not in ('".implode("','",(array)$var)."') ",
                            );
        return $type == 'between' ? $FilterArray[$type] : $col . $FilterArray[$type];

    }

        function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        $filter = $_SESSION['fil'];
        unset($_SESSION['fil']);
           $limit = 100;
            $cols = $this->_columns();
            $oSchema = array_flip($this->getTitle($cols));

            if(!$data['title']){
                $title = array();
                foreach( $this->getTitle($cols) as $titlek => $aTitle ){
                if($titlek=='bn_branch'){
                    continue;
                }
                    //$title[$titlek] = $this->charset->utf2local($aTitle);
                $title[$titlek] = $aTitle;
                }
                $data['title'] = '"'.implode('","',$title).'"';
            }
            if(!$list = $this->getList('*',$filter,$offset*$limit,$limit))return false;
            foreach( $list as $line => $row ){
                $rowRow = array();
                foreach( $oSchema as $k => $v ){
                if($v=='bn_branch'){
                    continue;
                }
                $v = utils::apath( $row,explode('/',$v) ) . '';
                    //$rowRow[$k] = $this->charset->utf2local($v);

                $rowRow[$k] = $v;
                }
                $data['contents'][] = '"'.implode('","',$rowRow).'"';
            }
            $data['name'] = '库存异动'.date("m月d日",time());
            return true;
        }
/**/
        function getTitle(&$cols){
            $title = array();
            foreach( $cols as $col => $val ){
                if( !$val['deny_export'] )
                    $title[$col] = $val['label'].'('.$col.')';
            }
            return $title;
        }

        function export_csv($data,$exportType){
            $rs = '';
            if( is_array( $data ) ){
                $data = (array)$data;
                if( empty( $data['title'] ) && empty( $data['contents'] ) ){
                    $rs = implode( "\n", $data );
                }else{
					$rs = $data['title']."\n".implode("\n",(array)$data['contents']);
                }
            }else{
                $rs = (string)$data;
            }
            return $rs;
        }

        public function get_schema(){
            $schema = array (
            'columns' => array (
                'bn' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '货号',
                    'width' => 150,
                    'searchtype' => 'has',
                     'filtertype' => 'normal',
                     'filterdefault' => true,
                    'editable' => false,
                    'order' =>1,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                 'goods_name' => array (
                    'type' => 'varchar(100)',
                    'label' => '货品名称',
                    'width' => 260,
                    'editable' => false,
                    'order' =>2,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                ),

                'store_name' => array (
                    'type' => 'table:branch@ome',
                    'label' => '仓库名称',
                    'width' => 110,
                    'order' =>3,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'store_freeze' => array (
                    'type' => 'int(10)',
                    'label' => '冻结库存',
                    'width' => 75,
                    'order' =>4,
                    'in_list' =>true,
                    'default_in_list' => true,
                ),

                'store' => array (
                    'type' => 'number',
                    'required' => true,
                    'default' => 0,
                    'label' => '可用库存',
                    'filtertype' => 'number',
                    'filterdefault' => true,
                    'width' => 100,
                    'order' =>5,
                    'in_list' =>true,
                    'default_in_list' => true,
                ),
                'stores' => array (
                    'type' => 'number',
                    'default' => 0,
                    'required' => true,
                    'label' => '实际库存',
                    'filtertype' => 'number',
                    'filterdefault' => true,
                    'width' => 110,
                    'order' =>6,
                    'in_list' =>true,
                    'default_in_list' => true,
                ),
                 'bn_branch' => array (
                    'type' => 'varchar(100)',
                    'label' => '货品仓库',
                    'width' => 110,
                    'editable' => false,
                    'in_list' => false,
                    'default_in_list' => false,
                ),

            ),

        'idColumn' => 'bn_branch',
        'in_list' => array (
                0 => 'bn',
                1 => 'goods_name',
                2 => 'store_name',
                3 => 'store_freeze',
                4 => 'store',
                5 => 'stores',
                //6 => 'bn_branch',
            ),
        'default_in_list' => array (
                0 => 'bn',
                1 => 'goods_name',
                2 => 'store_name',
                3 => 'store_freeze',
                4 => 'store',
                5 => 'stores',
                //6 => 'bn_branch',
            ),
        );
        return $schema;
    }
}