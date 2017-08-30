<?php

class ome_mdl_products extends dbeav_model{

    /*
     * 统计商品总库存
     */
    function count_store($product_id){
        $row = $this->db->selectrow("SELECT product_id,SUM(store) AS 'store' FROM sdb_ome_branch_product WHERE product_id='".$product_id."' GROUP BY product_id");
        $time = time();
        if (!$row){
            $p = array(
                'product_id' => $product_id,
                'store' => 0
            );
        }else {
            $p = array(
                'product_id' => $row['product_id'],
                'store' => $row['store']
            );
        }
        $p['last_modified'] = $time;
        $p['real_store_lastmodify'] = $time;
        $p['max_store_lastmodify'] = $time;
        $this->save($p);
        return true;
    }
    function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = "1";

        $goodsFilter = array();
        if(isset($filter['type_id'])){
            $goodsFilter['type_id'] = $filter['type_id'];
            unset($filter['type_id']);
        }
        if(isset($filter['brand_id'])){
            $goodsFilter['brand_id'] = $filter['brand_id'];
            unset($filter['brand_id']);
        }

        if(isset($goodsFilter) && count($goodsFilter)>0){
            $goodsObj = &app::get('ome')->model("goods");
            $rows = $goodsObj->getList('goods_id',$goodsFilter);
            $goodsId[] = 0;
            foreach($rows as $row){
                $goodsId[] = $row['goods_id'];
            }
            $where .= '  AND goods_id IN ('.implode(',', $goodsId).')';
            unset($goodsFilter);
        }
        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }


    function countAnother($filter=null){
        $other_table_name = app::get('ome')->model('branch_product')->table_name(1);
        $count = ' COUNT(*) ';
        if (isset($filter['product_group'])){
            $count = ' COUNT( DISTINCT '.$this->table_name(1).'.product_id ) ';
        }
        
        $strWhere = '';
        
        if(isset($filter['branch_id'])){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND '.$other_table_name.'.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND '.$other_table_name.'.branch_id = '.$filter['branch_id'];
            }
        }else{
            if ($filter['branch_ids']) {
                if (is_array($filter['branch_ids'])){
                    $strWhere = ' AND '.$other_table_name.'.branch_id IN ('.implode(',', $filter['branch_ids']).') ';
                }else {
                    $strWhere = ' AND '.$other_table_name.'.branch_id = '.$filter['branch_ids'];
                }
            }
        }
        $sql = 'SELECT '.$count.'as _count FROM `'.$this->table_name(1).'` LEFT JOIN  '.$other_table_name.'  ON '.$this->table_name(1).'.product_id = '.$other_table_name.'.product_id WHERE '.$this->_filter($filter) . $strWhere;

        $row = $this->db->selectrow($sql);

        return intval($row['_count']);
    }

    function getListAnother($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        if($this->use_meta){
             $meta_info = $this->prepare_select($cols);
        }

        $other_table_name = app::get('ome')->model('branch_product')->table_name(1);
        $strWhere = '';
        if(isset($filter['branch_id'])){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND '.$other_table_name.'.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND '.$other_table_name.'.branch_id = '.$filter['branch_id'];
            }
        }else{
            if ($filter['branch_ids']) {
                if (is_array($filter['branch_ids'])){
                    $strWhere = ' AND '.$other_table_name.'.branch_id IN ('.implode(',', $filter['branch_ids']).') ';
                }else {
                    $strWhere = ' AND '.$other_table_name.'.branch_id = '.$filter['branch_ids'];
                }
            }
            
        }
        $strGroup = '';
        if(isset($filter['product_group'])){
            $strGroup = ' GROUP BY '.$this->table_name(1).'.product_id ';
        }

        $tmpCols = array();
        foreach(explode(',',$cols) as $col){
            if($col == 1){
                $tmpCols[] = $col;
            }else{
                $tmpCols[] = $this->table_name(true).'.'.$col;
            }
        }
        $cols = implode(',',$tmpCols);
        unset($tmpCols);

        $orderType = $orderType?$orderType:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` LEFT JOIN  '.$other_table_name.'  ON '.$this->table_name(1).'.product_id = '.$other_table_name.'.product_id WHERE '.$this->_filter($filter) . $strWhere;

        if($strGroup)$sql.=$strGroup;
        if($orderType) {$this->table_name(true).'.'.
            $sql.=' ORDER BY ';
            if (is_array($orderType)){
                $sql .= $this->table_name(true).'.';
                $sql .= implode(','.$this->table_name(true).'.' , $orderType);
            }else {
                $sql .= $this->table_name(true).'.'.$orderType;
            }
        }

        $data = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($data, $cols);
        foreach($data as $key=>$_v){
             $data[$key]['name'] = trim($_v['name']);
        }
        if($this->use_meta && count($meta_info['metacols']) && $data){
            foreach($meta_info['metacols'] as $col){
                $obj_meta = new dbeav_meta($this->table_name(true),$col,$meta_info['has_pk']);
                $obj_meta->select($data);
            }
        }
        return $data;
    }

    function getBranchPdtList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        if($this->use_meta){
             $meta_info = $this->prepare_select($cols);
        }

        $other_table_name = app::get('ome')->model('branch_product')->table_name(1);
        $strWhere = '';
        if(isset($filter['branch_id'])){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND '.$other_table_name.'.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND '.$other_table_name.'.branch_id = '.$filter['branch_id'];
            }
        }else{
            if ($filter['branch_ids']) {
                if (is_array($filter['branch_ids'])){
                    $strWhere = ' AND '.$other_table_name.'.branch_id IN ('.implode(',', $filter['branch_ids']).') ';
                }else {
                    $strWhere = ' AND '.$other_table_name.'.branch_id = '.$filter['branch_ids'];
                }
            }
            
        }
        $strGroup = '';
        if(isset($filter['product_group'])){
            $strGroup = ' GROUP BY '.$this->table_name(1).'.product_id ';
        }

        $tmpCols = array();
        foreach(explode(',',$cols) as $col){
            if($col == 1){
                $tmpCols[] = $col;
            }else{
                $tmpCols[] = $this->table_name(true).'.'.$col;
            }
        }
        $cols = implode(',',$tmpCols);
        unset($tmpCols);

        //特定的调拨商品筛选器，获取分仓下的库存
        $cols = str_replace('sdb_ome_products.`store`',$other_table_name.'.store',$cols);
        $cols = str_replace('sdb_ome_products.`store_freeze`',$other_table_name.'.store_freeze',$cols);

        $orderType = $orderType?$orderType:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` LEFT JOIN  '.$other_table_name.'  ON '.$this->table_name(1).'.product_id = '.$other_table_name.'.product_id WHERE '.$this->_filter($filter) . $strWhere;

        if($strGroup)$sql.=$strGroup;
        if($orderType) {$this->table_name(true).'.'.
            $sql.=' ORDER BY ';
            if (is_array($orderType)){
                $sql .= $this->table_name(true).'.';
                $sql .= implode(','.$this->table_name(true).'.' , $orderType);
            }else {
                $sql .= $this->table_name(true).'.'.$orderType;
            }
        }

        $data = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($data, $cols);
        foreach($data as $key=>$_v){
             $data[$key]['name'] = trim($_v['name']);
        }
        if($this->use_meta && count($meta_info['metacols']) && $data){
            foreach($meta_info['metacols'] as $col){
                $obj_meta = new dbeav_meta($this->table_name(true),$col,$meta_info['has_pk']);
                $obj_meta->select($data);
            }
        }
        return $data;
    }

    /*
     * 还原货品的冻结库存
     *
     * @param int $branch_id 仓库id
     * @param int $product_id 货品id
     * @param int $nums 还原的数量
     *
     * @return bool
     */
    function unfreez($branch_id,$product_id,$nums){
        //暂时没有在branch_product上使用冻结库存
        $this->chg_product_store_freeze($product_id,$nums,"-");

        return true;
    }

    /*
     * 增加货品的冻结库存
     *
     * @param int $branch_id 仓库id
     * @param int $product_id 货品id
     * @param int $nums 新增的数量
     *
     * @return bool
     */
    function freez($product_id,$nums){
        //暂时没有在branch_product上使用冻结库存
        $this->chg_product_store_freeze($product_id,$nums,"+");

        return true;
    }

    /*
     * 修改冻结库存
     */
    function chg_product_store_freeze($product_id,$num,$operator='=',$log_type='order'){
        $now = time();
        $store_freeze = "";
        //danny_freeze_stock_log
        $mark_no = uniqid();
        switch($operator){
            case "+":
                $store_freeze = "store_freeze=IFNULL(store_freeze,0)+".$num.",";
                //danny_freeze_stock_log
                $action = '增加';
                break;
            case "-":
                $store_freeze = " store_freeze=IF((CAST(store_freeze AS SIGNED)-$num)>0,store_freeze-$num,0),";
                //danny_freeze_stock_log
                $action = '扣减';
                break;
            case "=":
            default:
                $store_freeze = "store_freeze=".$num.",";
                //danny_freeze_stock_log
                $action = '覆盖';
                break;
        }
        //danny_freeze_stock_log
        $lastinfo = $this->db->selectrow('select goods_id,bn,store_freeze from sdb_ome_products where product_id ='.$product_id);

        $sql = 'UPDATE sdb_ome_products SET '.$store_freeze.'last_modified='.$now.',max_store_lastmodify='.$now.' WHERE product_id='.$product_id;
        $this->db->exec($sql);

        //danny_freeze_stock_log
        $currentinfo = $this->db->selectrow('select store_freeze from sdb_ome_products where product_id ='.intval($product_id));
        $log = array(
                'log_type'=>$log_type,
                'mark_no'=>$mark_no,
                'oper_time'=>$now,
                'product_id'=>$product_id,
                'goods_id'=>$lastinfo['goods_id'],
                'bn'=>$lastinfo['bn'],
                'stock_action_type'=>$action,
                'last_num'=>$lastinfo['store_freeze'],
                'change_num'=>$num,
                'current_num'=>$currentinfo['store_freeze'],
        );
        kernel::single('ome_freeze_stock_log')->changeLog($log);
    }

    /*
     * 获取货品在对应仓库中的库存
     *
     * @param int $product_id 货品id
     *
     * @return array
     */
    function get_branch_store($product_id){
        $ret = array();
        $branch_product = $this->db->select("SELECT * FROM sdb_ome_branch_product WHERE product_id=".intval($product_id));
        if($branch_product){
            foreach($branch_product as $v){
            	//将订单确认拆分的仓库货品数量由store改为store_freeze
                $store = max(0,$v['store']-$v['store_freeze']);
                $ret[$v['branch_id']] = $store;
            }
        }
        return $ret;
    }

    /*
     * 根据仓库ID和货品ID 获取相应的库存数量
     *
     * @param int $product_id 货品id
     *
     * @return array
     */
    function get_product_store($branch_id,$product_id){
        $branch_product = $this->db->selectrow("SELECT * FROM sdb_ome_branch_product WHERE product_id=".intval($product_id)." AND branch_id=".intval($branch_id));
        $sale_store = $branch_product['store']-$branch_product['store_freeze'];
        return $sale_store;
    }

    /*
    *将上传的数据导入货品表
    *需查看purchase模块是否安装
    */
    function import_product($adata)
    {

        $oBranch_product=&$this->app->model('branch_product');
        $oBranch= &$this->app->model('branch');

        $oBranch_pos = &$this->app->model('branch_pos');
        $oPos = &$this->app->model('branch_product_pos');
        $oOperation_log = &$this->app->model('operation_log');//写日志
        $pur_status=app::get('purchase')->status();
        /*检查仓库对应货位是否存在。不存在添加*/
        $product_data=array(
            'bn'=>$adata['bn'],
            //'product_name'=>$adata['product_name'],
            'store'=>$adata['store'],
            'sku_property'=>$adata['sku_property'],
            'weight'=>$adata['weight']
         );

       $branch_pos = $oBranch_pos->dump(array(
           'store_position'=>$adata['store_position'],
           'branch_id'=>$adata['branch_id']),'*');
       $adata['pos_id'] = $branch_pos['pos_id'];
       $product=$this->dump(array('bn'=>$adata['bn']),'product_id,store');

        if(!empty($product)){
            $product_id = $product['product_id'];
            /*判断商品和货号是否建立联系。如果未建立建立*/
            $branch_pro_data = array('branch_id'=>$adata['branch_id'],'product_id'=>$product_id);

            $branch_product = $oBranch_product->dump($branch_pro_data,'*');

            if(empty($branch_product)){

                $oBranch_product->save($branch_pro_data);
            }
            $pos_data = array('product_id'=>$product_id);
            $pos = $oPos->dump($pos_data,'*');
            if(empty($pos)){
                $pos_data['pos_id']=$adata['pos_id'];
                $pos_data['default_pos']=true;
                $oPos->save($pos_data);
            }
            $lower_store_data= array(
               'pos_id'=>$adata['pos_id'],'product_id'=>$product_id,'num'=>$adata['store'],'branch_id'=>$adata['branch_id']);
            if($adata['type']=='1'){
                 $oPos->change_store($adata['branch_id'], $product_id, $adata['pos_id'], $adata['store'], '+');
            }else{
                 $adata['product_id'] = $product_id;
                 $this->tosave($adata, true);
            }
        }
        $oOperation_log->write_log('branch@ome',$adata['branch_id'],'导入了库存');
    }

    /*
    * 获取库存详情
    *$param int
    *return array
    */
    function products_detail($product_id){
        $pro=$this->dump($product_id);
        $oBranch_product=&$this->app->model('branch_product_pos');
        //$oBranch_pos=&$this->app->model('branch_pos');
        $oBr_product=&$this->app->model('branch_product');//Get_name($branch_id)
        //$oBranch = &$this->app->model('branch');
        $branch_product = $this->db->select('SELECT
        p.product_id,p.branch_id,p.arrive_store,p.store,p.store_freeze,p.safe_store,p.is_locked,
        bc.name as branch_name
        FROM sdb_ome_branch_product as p
        LEFT JOIN sdb_ome_branch as bc ON bc.branch_id=p.branch_id
        WHERE p.product_id='.$product_id);

        //c.store_position,
        // LEFT JOIN sdb_ome_branch_product_pos as b ON (p.product_id=b.product_id AND p.branch_id=b.branch_id)
        // LEFT JOIN sdb_ome_branch_pos as c ON b.pos_id=c.pos_id
        /* 可以删除，已经用leftjoin替代，防止因没有权限导致仓库名错误
        foreach($branch_product as $key=>$val){
            $branch_product[$key]['branch_name'] = $oBranch->Get_name($val['branch_id']);
        }
        */
        foreach($branch_product as $key=>$val){
            $pos_string ='';
            $posLists = $oBranch_product->get_pos($val['product_id'], $val['branch_id']);
            if(count($posLists) > 0){
                foreach($posLists as $pos){
                    $pos_string .= $pos['store_position'].",";
                }
                $branch_product[$key]['store_position'] = substr($pos_string,0,strlen($pos_string)-1);
            }
        }

        $pro['branch_product'] = $branch_product;

        /*$item = $this->db->select("SELECT p.* ,s.store_position,b.name,b.branch_id FROM sdb_ome_branch_product_pos as p left join sdb_ome_branch_pos as s on p.pos_id=s.pos_id left join sdb_ome_branch as b on s.branch_id=b.branch_id WHERE p.product_id='$product_id'");
        $ret = array();
        foreach($item as $k=>$v){
           $br_product = $oBr_product->dump(array('branch_id'=>$v['branch_id'],'product_id'=>$v['product_id']),'arrive_store');
           if(!isset($ret[$v['branch_id']])){
               $ret[$v['branch_id']] = $v['branch_id'];
               $item[$k]['arrive_store'] = $br_product['arrive_store'];
           }
        }
        $pro['items']=$item;*/

        return $pro;
    }

    function countBranchProduct($product_id, $column='safe_store'){
        $sql = "SELECT SUM($column) AS 'total' FROM sdb_ome_branch_product WHERE product_id = $product_id ";
        $count = $this->db->selectrow($sql);

        return $count['total'];
    }
   /*
    * 调整库存值
    * 增加导入标志,$import_flag
    */
    function tosave($adata, $import_flag='false'){
        $bppObj = &$this->app->model('branch_product_pos');
        $bppObj->change_store($adata['branch_id'],$adata['product_id'],$adata['pos_id'],$adata['store']);
    }

    function getFieldById($id, $aFeild=array('*')){
        $sqlString = "SELECT ".implode(',', $aFeild)." FROM sdb_ome_products WHERE product_id = ".intval($id);
        return $this->db->selectrow($sqlString);
    }
    function save(&$data,$mustUpdate = null){
        if (isset($data['bn'])) $data['bn'] = trim($data['bn']);
        if (isset($data['barcode'])) $data['barcode'] = trim($data['barcode']);
        if (isset($data['goods_id'])) $data['goods_id'] = trim($data['goods_id']);

        if (isset($data['spec_desc'])){
            $data['spec_info'] = implode('、', (array)$data['spec_desc']['spec_value']);
        }
        parent::save($data,$mustUpdate);
    }

    function dump($filter,$field = '*',$subSdf = null){
        $data = parent::dump($filter,$field,$subSdf);
        if(is_array($data)){
            $data['price']['price']['current_price'] = $data['price']['price']['price'];
        }
        return $data;
    }

    /**
     * 库存查询相关方法，2011.11.01更新
     */
    function search_stockinfo($search){
        ini_set('memory_limit','128M');
        $product_ids = array();
        $product_info = array();
        //模糊搜索商品
        $g_list = $this->db->select("SELECT goods_id FROM sdb_ome_goods WHERE  visibility='true' and (name LIKE '".$search."%' OR bn LIKE '".$search."%' OR brief LIKE '".$search."%' OR barcode LIKE '".$search."%')");
        if($g_list){
            foreach($g_list as $v){
                $t_products = $this->getList("product_id",array('goods_id'=>$v['goods_id']));
                foreach($t_products as $p){
                    $product_ids[] = $p['product_id'];
                }
            }
        }

        //模糊搜索货品
        $p_list = $this->db->select("SELECT product_id FROM sdb_ome_products WHERE  visibility='true' and ( bn LIKE '".$search."%' OR barcode LIKE '".$search."%')");
        if($p_list){
            foreach($p_list as $v){
                $product_ids[] = $v['product_id'];
            }
        }

        $product_ids = array_unique($product_ids);

        /*
         * 获取操作员管辖仓库
         */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                //获取所属仓库下的货品
                $oBranchProduct = &app::get('ome')->model('branch_product');
                $branch_product = $oBranchProduct->getList('product_id', array('branch_id'=>$branch_ids), 0,-1);
                if ($branch_product)
                foreach($branch_product as $bp){
                    $branch_product_ids[] = $bp['product_id'];
                }
                if ($product_ids or $branch_product_ids);
                $product_ids = array_intersect($product_ids,$branch_product_ids);
            }
            else{
                $product_ids = "";
            }
        }



        if($product_ids){
            $ids = implode(',', $product_ids);
            /*foreach($product_ids as $v){

                $sql = "SELECT p.name,p.bn,p.spec_info,p.store,p.store-IFNULL(p.store_freeze,0) AS max_store,bp.store_position,b.name AS branch FROM sdb_ome_products AS p
                        LEFT JOIN sdb_ome_branch_product_pos AS bpp ON(p.product_id=bpp.product_id)
                        LEFT JOIN sdb_ome_branch_product AS bpt ON(p.product_id=bpt.product_id)
                        LEFT JOIN sdb_ome_branch_pos AS bp ON(bpp.pos_id=bp.pos_id)
                        LEFT JOIN sdb_ome_branch AS b ON(bpt.branch_id=b.branch_id)
                        WHERE p.product_id=".$v;
                $product_info[] = $this->db->selectrow($sql);
            }*/

            /*$sql = "SELECT p.name,p.bn,p.spec_info,bpp.store,p.store-IFNULL(p.store_freeze,0) AS max_store,bp.store_position,b.name AS branch FROM sdb_ome_products AS p
                        LEFT JOIN sdb_ome_branch_product_pos AS bpp ON(p.product_id=bpp.product_id)
                        LEFT JOIN sdb_ome_branch_product AS bpt ON(p.product_id=bpt.product_id)
                        JOIN sdb_ome_branch_pos AS bp ON(bpp.pos_id=bp.pos_id && bpt.branch_id=bp.branch_id)
                        LEFT JOIN sdb_ome_branch AS b ON(bpt.branch_id=b.branch_id)
                        WHERE p.product_id IN (".$ids.")";
             */

            /*$sql = "SELECT p.product_id,p.name,p.bn,p.barcode,p.spec_info,bpt.store,p.store-IFNULL(p.store_freeze,0) AS max_store,
            b.name AS branch,bp.store_position
                        FROM sdb_ome_products AS p
                        LEFT JOIN sdb_ome_branch_product AS bpt ON(p.product_id=bpt.product_id)
                        LEFT JOIN sdb_ome_branch AS b ON(bpt.branch_id=b.branch_id)
                        LEFT JOIN sdb_ome_branch_product_pos AS bpp ON(bpt.product_id=bpp.product_id AND bpt.branch_id=bpp.branch_id)
                        LEFT JOIN sdb_ome_branch_pos AS bp ON(bpp.pos_id=bp.pos_id)
                        WHERE p.product_id IN (".$ids.")";
            */
            $sql = "SELECT p.product_id,p.name,p.bn,p.barcode,p.spec_info,bpt.store,p.store-IFNULL(p.store_freeze,0) AS max_store,
            b.name AS branch,b.branch_id
                        FROM sdb_ome_products AS p
                        LEFT JOIN sdb_ome_branch_product AS bpt ON(p.product_id=bpt.product_id)
                        LEFT JOIN sdb_ome_branch AS b ON(bpt.branch_id=b.branch_id)

                        WHERE p.product_id IN (".$ids.")";
            if(!$is_super){
                if(!empty($branch_ids)){
                    $_branch_ids = implode(',',$branch_ids);
                }else{
                    $_branch_ids = 0;
                }
                $sql .=' and b.branch_id in( '.$_branch_ids.' )';
            }
            $product_info = $this->db->select($sql);

        }
        $branch_product_posObj = &app::get('ome')->model('branch_product_pos');
        foreach($product_info as $key=>$val){
            $pos_string ='';
            $posLists = $branch_product_posObj->get_pos($val['product_id'], $val['branch_id']);
            if(count($posLists) > 0){
                foreach($posLists as $pos){
                    $pos_string .= $pos['store_position'].",";
                }
                $product_info[$key]['store_position'] = substr($pos_string,0,strlen($pos_string)-1);
            }
        }

       //print_r($product_info);
        return $product_info;
    }

   //录入日期
   function modifier_uptime($row){
        if (!$row) return '';
        $tmp = date('Y-m-d',$row);
        return $tmp;
    }

   //最近一次修改日期
   function modifier_last_modified($row){
        $tmp = date('Y-m-d',$row);
        return $tmp;
    }

    /**
    * 库存导出
    */
    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ) {
        if( !$data['title']){
            $title = array();

            foreach($this->io_title('products') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['products'] = '"'.implode('","',$title).'"';
        }
        
        if( !$list=$this->getListAnother('*',$filter,0,-1) )return false;
        foreach( $list as $aFilter ){
            $pRow = array();
            
            $detail['bn'] ="\t".$this->charset->utf2local($aFilter['bn']);
            $detail['barcode'] = "\t".$this->charset->utf2local($aFilter['barcode']);
            $detail['name'] = mb_convert_encoding($aFilter['name'], 'GBK', 'UTF-8');
            #解决规格中存在的换行问题
            if(!empty($aFilter['spec_info'])){
                $_spec_info  = explode("、",$aFilter['spec_info']);
                foreach($_spec_info as $v){
                    $sepc[] = trim($v);
                }
                $aFilter['spec_info'] = implode("|",$sepc);
                unset($sepc);
            }
            $spec_info = $aFilter['spec_info'];
            if ($spec_info) {
                #解决规格中存在的换行问题
                $_spec_info  = explode("、",$aFilter['spec_info']);
                foreach($_spec_info as $v){
                    $sepc[] = trim($v);
                }
                $aFilter['spec_info'] = implode("|",$sepc);
                unset($sepc);
            }
            
            $detail['spec_info'] = $this->charset->utf2local($aFilter['spec_info']);
            $detail['store'] = $aFilter['store'];
            $detail['store_freeze'] = $aFilter['store_freeze'];
            
            $num = $this->countBranchProduct($aFilter['product_id'],'arrive_store');
            $detail['arrive_store'] = $num;
            foreach( $this->oSchema['csv']['products'] as $k => $v ){

                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['contents']['products'][] = implode(',',$pRow);
        }

   
        return false;
    }

    function export_csv($data,$exportType = 1 ){

        $output = array();
        $output[] = $data['title']['products']."\n".implode("\n",(array)$data['contents']['products']);

        echo implode("\n",$output);
    }

    function io_title( $filter, $ioType='csv' ){

        switch( $filter ){
            case 'products':
                $this->oSchema['csv'][$filter] = array(
               
                '*:货号' => 'bn',
                '*:条形码' => 'barcode',
                '*:货品名称' => 'name',
                '*:规格' => 'spec_info',
                '*:库存' => 'store',
                '*:冻结库存' => 'store_freeze',
                '*:在途库存'=>'arrive_store'
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }
     #获取所有捆绑商品的实际库存(可售库存/捆绑数量)
     function getPkgGoodsStore($pkg_goods_id){
         $sql= 'select
                    pkg.goods_id,
                    MIN((product.store-product.store_freeze)/pkg.pkgnum) as  min_sotre
                 from sdb_omepkg_pkg_product pkg
                 left join sdb_ome_products product on pkg.product_id= product.product_id
                 where pkg.goods_id in ('.$pkg_goods_id .') GROUP BY goods_id';
         $product_info = $this->db->select($sql);
         return $product_info;
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
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_stock') {
            $type .= '_stockManager_totalStockList';
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
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_stock') {
            $type .= '_stockManager_totalStockList';
        }
        $type .= '_import';
        return $type;
    }
}
?>
