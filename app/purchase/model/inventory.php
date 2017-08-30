<?php

class purchase_mdl_inventory extends dbeav_model{

    /*
     * 获取货品及货品货位信息
     */
    function getBranchProduct($branch_id, $barcode){
        $sql = "SELECT p.product_id,p.name,p.spec_info,p.bn,bp.store as 'num' FROM sdb_ome_products p 
                        JOIN sdb_ome_branch_product bp 
                            ON p.product_id=bp.product_id 
                        WHERE p.barcode='$barcode' 
                            AND bp.branch_id='$branch_id'";
        $row = $this->db->selectrow($sql);
        if ($row){
            $sql = "SELECT bpp.pos_id,bp.store_position as 'pos_name',bpp.store FROM sdb_ome_branch_product_pos bpp 
                        JOIN sdb_ome_branch_pos bp 
                            ON bpp.pos_id=bp.pos_id  
                        WHERE bpp.branch_id='$branch_id' 
                            AND bpp.product_id='".$row['product_id']."'";
            $data = $this->db->select($sql);
            if ($data){
                $row['item'] = $data;
            }
            return $row;
        }else {
            return false;
        }
    }
    /*
     * 根据货号获取货品及货品货位信息
     */
    function getBnProduct($branch_id, $bn){
        $sql = "SELECT p.product_id,p.name,p.spec_info,p.bn,bp.store as 'num' FROM sdb_ome_products p 
                        JOIN sdb_ome_branch_product bp 
                            ON p.product_id=bp.product_id 
                        WHERE p.bn='$bn' 
                            AND bp.branch_id='$branch_id'";
        $row = $this->db->selectrow($sql);
        if ($row){
            $sql = "SELECT bpp.pos_id,bp.store_position as 'pos_name',bpp.store FROM sdb_ome_branch_product_pos bpp 
                        JOIN sdb_ome_branch_pos bp 
                            ON bpp.pos_id=bp.pos_id  
                        WHERE bpp.branch_id='$branch_id' 
                            AND bpp.product_id='".$row['product_id']."'";
            $data = $this->db->select($sql);
            if ($data){
                $row['item'] = $data;
            }
            return $row;
        }else {
            return false;
        }
    }
    function existPosNotProcess($pos_id){
        $sql = "SELECT COUNT(d.delivery_id) AS '_count' FROM sdb_ome_dly_items_pos dip 
                        JOIN sdb_ome_delivery_items di 
                            ON dip.item_id=di.item_id 
                        JOIN sdb_ome_delivery d 
                            ON di.delivery_id=d.delivery_id 
                        WHERE dip.pos_id = '".$pos_id."' 
                            AND d.process = 'false'";
        $row = $this->db->selectrow($sql);
        if ($row['_count'] > 0) return true;
        return false;
    }
    
    /*
     * 损益汇总表
     */
    function getInventoryList($col='*', $filter=null, $lim=0, $limit=-1){
        
        $sql = " SELECT $col FROM `sdb_purchase_inventory` WHERE 1 ";
        $begin_date = $filter['begin_date'];
        $end_date = $filter['end_date'];
        if ($begin_date) $sql .= " and FROM_UNIXTIME(`inventory_date`,'%Y-%m-%d')>='".$begin_date."' ";
        if ($end_date) $sql .= " and FROM_UNIXTIME(`inventory_date`,'%Y-%m-%d')<='".$end_date."' ";
        $limit = " limit $lim,$limit ";
        $tmp = $this->db->select($sql.$limit);
        return $tmp;
    }
    
    /*
    * 获取货品信息
    */
    function getProduct($data=null, $lim=0, $limit=1, $type='search'){
        
        $search_flag = 0;
        $branch_id = $data['branch_id'];
        //$sql0  = " SELECT ";
        //$selectField = " * ";
        $sql = " SELECT p.product_id,p.bn,p.name,p.spec_info,p.unit,pos.store,pos2.store_position,p.barcode  
                  FROM `sdb_ome_products` p
                  LEFT JOIN `sdb_ome_branch_product_pos` pos on p.product_id=pos.product_id
                  LEFT JOIN  `sdb_ome_branch_pos` pos2 on pos.pos_id=pos2.pos_id
                  LEFT JOIN  `sdb_ome_branch_product` bp on bp.branch_id='$branch_id' 
                  WHERE pos.branch_id = bp.branch_id and p.product_id=bp.product_id ";
        if ($data['pos_name']){
            foreach ($data['pos_name'] as $k=>$v){
                $wheresql .= " or pos2.store_position regexp '".$v."' ";
            }
            $search_flag = 1;
        } 
        if ($data['product_name']){
            foreach ($data['product_name'] as $k=>$v){
                $wheresql .= " or p.name regexp '".$v."' ";
            }
            $search_flag = 1;
        } 
        if ($data['product_bn']){
            foreach ($data['product_bn'] as $k=>$v){
                $wheresql .= " or p.bn regexp '".$v."' ";
            }
            $search_flag = 1;
        } 
        $branch_store = $data['branch_store'];
        if ($branch_store){
            if ($data['store_operator']=='>=') $store_operator = ">=";
            else $store_operator = "<=";
            $wheresql_and .= " and pos.store $store_operator '".$branch_store."' ";
            if ($search_flag) $search_flag = 1;
            else $search_flag = 0;
        }
        if ($search_flag) $where_pre = " AND ( 0 ".$wheresql.")";
        else  $where_pre = " AND ( 1 ".$wheresql." )";
        //忽略零库存
        if (!empty($data['ignore'])){
            $where_pre .= " AND pos.store > '0' ";
        }
        if ($type=='search') $limitsql = " limit $lim,$limit ";
        
        $result = $this->db->select($sql.$where_pre.$wheresql_and.$limitsql);
        $arr = array();
        if ($result) 
        foreach ($result as $k=>$v){
            //当前价格sql
            $sqlCurr = " SELECT e.`purchase_price` FROM `sdb_purchase_branch_product_batch` e
                       WHERE e.`product_id`='".$v['product_id']."' ORDER BY e.`purchase_time` DESC ";
            $cur_temp = $this->db->selectRow($sqlCurr);
            $v['price'] = $cur_temp['purchase_price'];
            $arr[] = $v;
        }
        $selectField = " SELECT count(*) as counts FROM (".$sql.$where_pre.$wheresql_and.") c";
        $count = $this->db->select($selectField);
        $arr['count'] = $count[0]['counts'];
        return $arr;
    }
    
    /*
     * 盘点明细总计
     * getInventoryTotal
     */
    function getInventoryTotal($inventory_id=null){
        $sql = " SELECT sum(accounts_num) accounts_num,sum(actual_num) actual_num,sum(shortage_over) shortage_over
               FROM `sdb_purchase_inventory_items` ";
        if ($inventory_id)
        $wheresql = "WHERE `inventory_id`='$inventory_id'";
        $tmp = $this->db->select($sql.$wheresql);
        $count = $this->db->select(" SELECT count(*) count FROM `sdb_purchase_inventory_items` $wheresql ");
        $tmp['count'] = $count[0]['count'];
        return $tmp;
    }
    
    /*
     * 盘点汇总总计
     * getTotal
     */
    function getTotal($begin_date=null, $end_date=null){
        
        $sql = " SELECT sum(difference) total_shortage_over
                 FROM `sdb_purchase_inventory` ";
        $wheresql0 = " WHERE 1 ";
        //if ($begin_date) $wheresql .= " and FROM_UNIXTIME(`inventory_date`,'%Y-%m-%d')>='$begin_date' ";
        //if ($end_date) $wheresql .= " and FROM_UNIXTIME(`inventory_date`,'%Y-%m-%d')<='$end_date' ";
        if ($begin_date) $wheresql .= " and FROM_UNIXTIME(`inventory_date`,'%Y-%m-%d')>='".$begin_date."' ";
        if ($end_date) $wheresql .= " and FROM_UNIXTIME(`inventory_date`,'%Y-%m-%d')<='".$end_date."' ";
        
        $tmp = $this->db->select($sql.$wheresql0.$wheresql);
        $tesql = " SELECT count(*) count FROM `sdb_purchase_inventory` ".$wheresql0.$wheresql;

        $count = $this->db->select($tesql);
        $tmp['count'] = $count[0]['count'];
        return $tmp;
    }
    
    /*
     * 确认 
     */ 
    function confirm($data=null){
        
        $sql = " UPDATE `sdb_purchase_inventory` SET `confirm_status`='2',`confirm_op`='".$data['confirm_op']."',`confirm_time`='".$data['confirm_time']."' WHERE `inventory_id`='".$data['inventory_id']."' ";

        if ($this->db->exec($sql)) return true;
        else return false;
    } 
    
    /*
    * 盘点表编号
    */
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $inventory_bn = 'PD'.date('YmdH').str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow("SELECT `inventory_bn` from `sdb_purchase_inventory` where `inventory_bn`='".$inventory_bn."'");
        }while($row);
        return $inventory_bn;
    }
   
    
   //盘点日期计格式化
   function modifier_inventory_date($row){
        $tmp = date('Y-m-d',$row);
        return $tmp;
    }

    function searchOptions(){
        return array(
                
            );
    }
    
    
     function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'export':
                $this->oSchema['csv'][$filter] = array(
                    '*:商品名称' => 'name',
                    '*:货号' => 'bn',
                    '*:规格型号' => 'spec_info',
                    '*:单位' => 'unit',
                    '*:货位' => 'store_position',
                    '*:账面数量' => 'store',
                    '*:价格' => 'price',
                    '*:实际数量' => 'entity_num',
                    '*:品质情况' => 'condition',
                    '*:条形码' => 'barcode',
                );
                break;
            case 'branch':
                $this->oSchema['csv'][$filter] = array(
                    '*:仓库' => 'branch',
                    '*:盘点名称' => 'name',
                    '*:盘点人' => 'f_checker',
                    '*:复核人' => 's_checker',
                    '*:账务负责人' => 'finance_dept',
                    '*:仓库负责人' => 'warehousing_dept',
                    '*:盘点类型' => 'type',
                );
                break;
            case 'import':
                $this->oSchema['csv'][$filter] = array(
                    '*:商品名称' => 'name',
                    '*:货号' => 'bn',
                    '*:规格型号' => 'spec_info',
                    '*:单位' => 'unit',
                    '*:货位' => 'store_position',
                    '*:账面数量' => 'store',
                    '*:价格' => 'price',
                    '*:实际数量' => 'num',
                    '*:品质情况' => 'condition',
                    '*:条形码' => 'barcode',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }
     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        $post = $_POST;
        if ( $post ){
            $branch = &app::get('ome')->model('branch')->dump($post['branch_id']);
            $data['content']['branch']['branch']            = $branch['name'];
            $data['content']['branch']['name']              = $post['inventory_name'];
            $data['content']['branch']['f_checker']         = $post['inventory_checker'];
            $data['content']['branch']['s_checker']         = $post['second_checker'];
            $data['content']['branch']['finance_dept']      = $post['finance_dept'];
            $data['content']['branch']['warehousing_dept']  = $post['warehousing_dept'];
            $data['content']['branch']['type']              = $post['inventory_type']=='1'?'自定义':'全盘';
            
            
            if( !$data['title']['branch'] ){
                $title = array();
                foreach( $this->io_title('branch') as $k => $v ){
                    $title[] = $this->charset->utf2local($v);
                }
                $data['title']['branch'] = '"'.implode('","',$title).'"';
            }
            if( !$data['title']['export'] ){
                $title = array();
                foreach( $this->io_title('export') as $k => $v ){
                    $title[] = $this->charset->utf2local($v);
                }
                $data['title']['export'] = '"'.implode('","',$title).'"';
            }
            
            
            foreach( $this->oSchema['csv']['branch'] as $k => $v ){
                    $branchRow[$v] = $this->charset->utf2local( utils::apath( $data['content']['branch'],explode('/',$v) ) );
            }
            $data['content']['branch'] = '"'.implode('","',$branchRow).'"';
            /*
            $where = '';
            $s = ' AND ( ';
            $e = ' ) ';
            if (!empty($post['pos_name'])){
                foreach ($post['pos_name'] as $it){
                    if ($s != ''){
                        $where .= $s;
                        $s = '';
                        $or = '';
                    }else {
                        $or = ' OR ';
                    }
                    $where .= $or." bp.store_position LIKE '%$it%' ";
                }
            }
            if (!empty($post['product_bn'])){
                foreach ($post['product_bn'] as $it){
                    if ($s != ''){
                        $where .= $s;
                        $s = '';
                        $or = '';
                    }else {
                        $or = ' OR ';
                    }
                    $where .= $or." p.bn LIKE '%$it%' ";
                }
            }
            if (!empty($post['product_name'])){
                foreach ($post['product_name'] as $it){
                    if ($s != ''){
                        $where .= $s;
                        $s = '';
                        $or = '';
                    }else {
                        $or = ' OR ';
                    }
                    $where .= $or." p.name LIKE '%$it%' ";
                }
            }
            if ($s == ''){
                $where .= $e;
            }
            if (!empty($post['store_operator'])){
                $where .= " AND bpp.store".$post['store_operator'].$post['branch_store']." ";
            }
            
            if (!empty($post['branch_id'])){
                //$bran = $this->app->model('branch')->dump(array('name'=>$filter['_branch']));
                if ($post['inventory_type'] == '1'){
                    $where = " AND bp.branch_id=".$post['branch_id']." ".$where;
                }else {
                    $where = " AND bp.branch_id=".$post['branch_id'];
                }
            }
            if (!empty($post['ignore'])){
                $where .= " AND bpp.store > 0 ";
            }
            //p.product_id,p.bn,p.name,p.spec_info,p.unit,pos.store,pos.pos_id,pos2.store_position 
            $sql = "SELECT p.product_id,
                            p.name,p.bn,
                            p.spec_info,
                            p.unit,
                            bp.store_position,
                            bpp.store 
                        FROM  sdb_ome_products p 
                            LEFT JOIN sdb_ome_branch_product_pos bpp 
                                ON bpp.product_id=p.product_id 
                            LEFT JOIN sdb_ome_branch_pos bp 
                                ON bp.pos_id=bpp.pos_id  WHERE 1 ".$where;
            
            $rows = $this->db->select($sql);
            foreach ($rows as $row){
                $sqlCurr = " SELECT e.`purchase_price` FROM `sdb_purchase_branch_product_batch` e
                       WHERE e.`product_id`='".$row['product_id']."' ORDER BY e.`purchase_time` DESC ";
                $cur_temp = $this->db->selectRow($sqlCurr);
                $row['price'] = $cur_temp['purchase_price'];
                foreach( $this->oSchema['csv']['export'] as $k => $v ){
                    if ($v){
                        $pRow[$v] = $this->charset->utf2local( utils::apath( $row,explode('/',$v) ) );
                    }
                }
                $data['content']['export'][] = '"'.implode('","',$pRow).'"';
            }
            */
            $export_data['branch_id'] = $post['branch_id'];
            $export_data['pos_name'] = $post['pos_name'];
            $export_data['product_bn'] = $post['product_bn'];
            $export_data['product_name'] = $post['product_name'];
            $export_data['branch_store'] = $post['branch_store'];
            $export_data['store_operator'] = $post['store_operator'];
            $export_data['ignore'] = $post['ignore'];
            $inventory_list = $this->getProduct($export_data, '', '', 'export');
            unset($inventory_list['count']);
            if ($inventory_list){
                foreach ($inventory_list as $row){
                    foreach( $this->oSchema['csv']['export'] as $k => $v ){
                        if ($v){
                            $pRow[$v] = $this->utf8togbk( utils::apath( $row,explode('/',$v) ) );
                        }else {
                            $pRow[$v] = '';
                        }
                    }
                    $data['content']['export'][] = '"'.implode('","',$pRow).'"';
                }
            }
            $data['name'] = $post['inventory_name'];
            return false;
        }
    }
    function utf8togbk($s)
    {
        return iconv("UTF-8", "GBK//TRANSLIT", $s);
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
    

    function prepared_import_csv(){
        set_time_limit(0);
        $this->ioObj->cacheTime = time();
        $this->products = &app::get('ome')->model('products');
        $this->branch_pos = &app::get('ome')->model('branch_pos');
        $this->branch = &app::get('ome')->model('branch');
        $this->kvdata = '';
        $this->aa = 0;
    }

    function finish_import_csv(){
        $data = $this->kvdata;//base_kvstore::instance('purchase_inventory')->fetch('inventory-'.$this->ioObj->cacheTime);
        unset($this->kvdata);//base_kvstore::instance('purchase_inventory')->store('inventory-'.$this->ioObj->cacheTime,'');
        
        $oQueue = &app::get('base')->model('queue');
        $oInven = &app::get('purchase')->model('inventory');
        $number = 0;
        $page = 0;
        $limit = 50;
        $branch = app::get('ome')->model('branch')->dump(array('name'=>$data['branch']['contents'][0][0]));
        
        $inv['inventory_name']      =  $data['branch']['contents'][0][1];
        $inv['inventory_bn']        = $oInven->gen_id();
        $inv['inventory_date']      = time();
        $inv['inventory_checker']   = $data['branch']['contents'][0][2];
        $inv['second_checker']      = $data['branch']['contents'][0][3];
        $inv['finance_dept']        = $data['branch']['contents'][0][4];
        $inv['warehousing_dept']    = $data['branch']['contents'][0][5];
        $inv['op_name']             = kernel::single('desktop_user')->get_name();
        $inv['op_id']               = kernel::single('desktop_user')->get_id();
        $inv['branch_id']           = $branch['branch_id'];
        $inv['branch_name']         = $branch['name'];
        $inv['inventory_type']      = $data['branch']['contents'][0][6]=='自定义'?'1':'2';
        
        
        $re = $oInven->save($inv);
        $psdf['branch_id']  = $branch['branch_id'];
        $psdf['branch']     = $branch['name'];
        $psdf['inv_id']     = $inv['inventory_id'];
        
        
        $sdfs = array();
        foreach ($data['products']['contents'] as $k => $v){
            $sdf = array();
            //$sql = "SELECT product_id FROM sdb_ome_products WHERE bn='".$v[2]."'";
            //$p = $this->db->selectrow($sql);
            $sdf['product_id']     = $v['product_id'];//$p['product_id'];
            $sdf['bn']             = $v[1];
            $sdf['name']           = $v[0];
            $sdf['spec_info']      = $v[2];
            $sdf['unit']           = $v[3];
            $sdf['store_position'] = $v[4];
            $sdf['store']          = (int)$v[5];
            $sdf['price']          = $v[6];
            $sdf['num']            = (int)$v[7];
            $sdf['condition']      = $v[8];
            
            if ($number < $limit){
                $number++;
            }else{
                $page++;
                $number = 0;
            }
            $sdfs[$page][] = $sdf;
        }
        foreach ($sdfs as $i){
            $psdf['products']  = $i;
            $queueData = array(
                'queue_title'=>'盘点导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$psdf,
                    'app' => 'ome',
                    'mdl' => 'products'
                ),
                'worker'=>'purchase_products_import.run',
            );
            
            $oQueue->save($queueData); 
        }
        $oQueue->flush();
        return null;
    }
    //导入
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        
        $this->aa++;
        if (empty($row)){
            if ($this->flag){
                if ($this->not_exist_product_bn){
                    $temp = $this->not_exist_product_bn;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n数据库中不存在的商品货号：';
                    $ms = '';
                    foreach ($tmp as $k => $v){
                        if ($k >= 10){
                            $ms = '...\n';
                            break;
                        }
                        if ($k < 5){
                            $tmp1[] = $v;
                            continue;
                        }
                        $tmp2[] = $v;
                    }
                    $msg['error'] .= '\n'.implode(',', $tmp1);
                    if (!empty($tmp2)) $msg['error'] .= '\n'.implode(',', $tmp2);
                    $msg['error'] .= $ms;
                    $tmp1 = null;
                    $tmp2 = null;
                }
                if ($this->not_exist_pos){
                    $temp = $this->not_exist_pos;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n数据库中不存在的货位：';
                    $ms = '';
                    foreach ($tmp as $k => $v){
                        if ($k >= 10){
                            $ms = '...\n';
                            break;
                        }
                        if ($k < 5){
                            $tmp1[] = $v;
                            continue;
                        }
                        $tmp2[] = $v;
                    }
                    $msg['error'] .= '\n'.implode(',', $tmp1);
                    if (!empty($tmp2)) $msg['error'] .= '\n'.implode(',', $tmp2);
                    $msg['error'] .= $ms;
                    $tmp1 = null;
                    $tmp2 = null;
                }
                
                $this->kvdata = '';
                //base_kvstore::instance('purchase_inventory')->store('inventory-'.$this->ioObj->cacheTime,'');
                return false;
            }
            return true;
        }
        $mark = false;
        $fileData = $this->kvdata;//base_kvstore::instance('purchase_inventory')->fetch('inventory-'.$this->ioObj->cacheTime);
        
        if( !$fileData )
            $fileData = array();

        if( substr($row[0],0,2) == '*:' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            
            return $titleRs;
        }else{
            if( $row[0] ){ 
                if( array_key_exists( '*:商品名称',$title ) ) {
                    $product = $this->products->dump(array('bn'=>$row[1]),'product_id');
                    $pos = $this->branch_pos->dump(array('store_position'=>$row[4]),'pos_id');
                    
                    if(!$product){
                        $this->flag = true;
                        $this->not_exist_product_bn = isset($this->not_exist_product_bn)?array_merge($this->not_exist_product_bn,array($row[1])):array($row[1]);
                    }else {
                        $row['product_id'] = $product['product_id'];
                    }
                    if(!$pos){
                        $this->flag = true;
                        $this->not_exist_pos = isset($this->not_exist_pos)?array_merge($this->not_exist_pos,array($row[4])):array($row[4]);
                    }
                    
                    $fileData['products']['contents'][] = $row;
                }else {
                    $branch = $this->branch->dump(array('name'=>$row[0]),'branch_id');
                    if ( !$branch ){
                        $msg['error'] = "没有此仓库：".$row[0];
                        return false;
                    }
                    
                    $fileData['branch']['contents'][] = $row;
                }
                $this->kvdata = $fileData;
                //base_kvstore::instance('purchase_inventory')->store('inventory-'.$this->ioObj->cacheTime,$fileData);
            }else {
                $msg['error'] = "商品名称不能为空！";
                return false;
            }
        }
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }
   
}