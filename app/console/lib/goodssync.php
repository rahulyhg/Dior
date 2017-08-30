<?php
class console_goodssync{
    
    /**
    * 编辑货品前的操作
    * @param Array $sdf 货品编辑前的数据
    * @return void
    */
    public function pre_update($sdf=array()){
        if (empty($sdf)) return false;

        if($sdf['type'] == 'normal'){
            $pre_update_md5 = md5($sdf['barcode'].$sdf['name'].$sdf['spec_info']);
            $this->pre_update_md5 = $pre_update_md5;
        }
    }
    
    /**
    * 编辑货品成功后的操作
    * @param Array $sdf 货品编辑后的数据
    * @param String $msg 引用返回消息
    * @return void
    */
    public function after_update($sdf=array(),&$msg=''){
        if (empty($sdf)) return false;

        if($sdf['type'] == 'normal'){
            $after_update_md5 = md5($sdf['barcode'].$sdf['name'].$sdf['spec_info']);
            if($this->pre_update_md5 != $after_update_md5){
                $skuObj = kernel::single('console_foreign_sku');
                //将编辑过后的商品 状态更改为编辑后同步
                $rs = $skuObj->update_sync_status($sdf['bn']);
                $msg = $rs ? '货品信息已被编辑，需重新同步至第三方仓库' : '';
            }
        }
        return $rs;
    }

    /**
    * 删除货品时的操作
    * @param Int $product_id 货品ID
    * @return void
    */
    public function delete_product($product_id=''){
        if (empty($product_id)) return false;

        $skuObj = kernel::single('console_foreignsku');
        if($skuObj->delete_sku($product_id)){
            return true;
        }else return false;
    }

    //同步全部商品
    public function sync_all($filter){
        
        @ini_set('memory_limit','128M');
        $wfsObj = &app::get('console')->model('foreign_sku');
        $db = kernel::database();
        $data = kernel::single('channel_func')->getWmsChannelList();
        $view = $filter['view'];
        if($view != ''){
            $desktop_filter_model = app::get('desktop')->model('filter');
            $_desktop_filter = $desktop_filter_model->getList('*',array('model'=>'console_mdl_foreign_sku'));
            
            $_count = count($data);
            $_filter = $_desktop_filter[(int)($view-$_count[0]['_count']-1)];
            $_filter_query = array();
            parse_str($_filter['filter_query'],$_filter_query);
            $filter = array_merge($filter,$_filter_query);
        }

        //选择全部wms时单独处理
        if($filter['wms_id'] == '0'){
            
            $wms_id = array();
            foreach($data as $v){
                $wms_id[] = $v['wms_id'];
            }
            $filter['wms_id'] = (array)$wms_id;
        }
        #error_log('filter:'.var_export($filter,1),3,__FILE__.'.log');
        $sql_counter = " SELECT count(*) ";
        $sql_list = " SELECT * ";
        $wfsObj->filter_use_like = true;
        $sql_base = ' FROM `sdb_console_foreign_sku` WHERE '.$wfsObj->_filter($filter);
        $sql = $sql_counter . $sql_base;
        #error_log('sql:'.$sql."\n",3,__FILE__.'.log');
        $count = $db->count($sql);
        $limit = 500;
        if ($count){
            $pagecount = ceil($count/$limit);
            for ($page=1;$page<=$pagecount;$page++){
                $offset = ($page-1) * $limit;
                $sql = $sql_list.$sql_base." ORDER BY `inner_product_id` LIMIT ".$offset.",".$limit;
                #error_log('sql1:'.$sql."\n",3,__FILE__.'.log');
                $products = $db->select($sql);
                if ($products){
                    $product_ids = array();
                    foreach ($products as $p){
                        $product_ids[] = $p['inner_product_id'];
                    }
                }
                $sql = 'SELECT p.bn,p.product_id,p.name,p.barcode,p.price,p.weight,p.spec_info as property,b.brand_name as brand,t.name as goods_cat FROM `sdb_ome_products` as p LEFT JOIN sdb_ome_goods as g on p.goods_id=g.goods_id LEFT JOIN sdb_ome_brand as b on g.brand_id=b.brand_id LEFT JOIN sdb_ome_goods_type as t ON t.type_id=g.type_id WHERE p.`product_id` IN ('.implode(',',$product_ids).')';
                $products_sdf = $db->select($sql);

                // 商品同步
                $products_sdf['wms_id'] = $filter['wms_id'];
                if(is_array($filter['wms_id'])) $products_sdf['wms_id'] = '0';
                #error_log('pro_sdf:'.var_export($products_sdf,1),3,__FILE__.'.log');

                $this->syncProduct_notifydata($products_sdf['wms_id'],$products_sdf,$filter['branch_bn']);
                $products_sdf = $product_ids = $products = NULL;
            }
        }

        return true;
    }

    public function doadd ($sdf){
        if (!is_array($sdf)) return $this->msg->get_flag('success');

        $productsObj = &app::get('ome')->model('products');
        $product_sdf = array();
        foreach($sdf as $key=>$product_id){
            $tmp = $productsObj->getList('*',array('product_id'=>$product_id));
            if ($tmp){
                foreach ($tmp as $items){
                    $product_sdf[] = $items;
                }
            }
        }
        // 商品同步
        $this->syncProduct_notifydata($sdf['wms_id'],$products_sdf);
        return $this->msg->get_flag('success');
    }

    /**
    *通过wms_id获取未分派的商品
    *@params $wms_id wms_id
    *return array 未分派的商品
    **/
    public function get_goods_by_wms($wms_id,$offset='0',$limit='999999'){
        $db = kernel::database();
        $sql = "SELECT b.brand_name,p.bn,p.name,p.product_id FROM sdb_ome_products p LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id LEFT JOIN sdb_ome_brand b ON b.brand_id = g.brand_id 
                WHERE  p.product_id not in (SELECT inner_product_id FROM sdb_console_foreign_sku WHERE wms_id = '".$wms_id."') AND p.type='normal' GROUP BY p.bn limit ".$offset.",".$limit;

        $data = $db->select($sql);
        return $data;
    }

    /**
    *通过wms_id获取未分派的商品
    *@params $wms_id wms_id
    *@params $search_key 搜索的键
    *@params $search_value 搜索的值
    *return array 未分派的商品
    **/
    public function get_data_by_search($search_key,$search_value,$wms_id){
        $data['search_key'] = $search_key;
        $data['search_value'] = $search_value;
        $data['wms_id'] = $wms_id;
        $product_ids = $this->get_filter($data);
        $limt = 10;
        $product_ids_tmp = array_chunk($product_ids,$limt);
        $db = kernel::database();
        for($i=0;$i<(count($product_ids) / $limt);$i++){
            $sql = "SELECT b.brand_name,p.bn,p.name,p.product_id FROM sdb_ome_products p LEFT JOIN sdb_ome_brand b ON b.brand_id = p.brand_id
                WHERE  p.product_id in (".implode($product_ids_tmp[$i],',').")  AND p.type='normal' ";
            $res[] = $db->select($sql);
        }
        $result = array();
        foreach($res as $key=>$value){
            foreach($value as $v){
                $result[] = $v;
            }
        }
        return $result;
    }


    /**
    *通过wms_id获取未分派的商品的数量
    *@params $wms_id wms_id
    *return array 未分派的商品
    **/
    public function get_goods_count_by_wms($wms_id){
        $db = kernel::database();
        $sql = "SELECT count(*) as count FROM (SELECT p.bn FROM sdb_ome_products p LEFT JOIN sdb_ome_brand b ON b.brand_id = p.brand_id 
                WHERE  p.product_id not in (SELECT inner_product_id FROM sdb_console_foreign_sku WHERE wms_id = '".$wms_id."')  AND p.type='normal' GROUP BY p.bn) AS tb";
        $data = $db->select($sql);
        return $data;
    }

    /**
    *通过product_id获取未分派的商品的数量
    *@params array $product_id 
    *return array 未分派的商品
    **/
    public function get_wms_goods($product_id){
        $product_id_str = implode($product_id, ',');
        $db = kernel::database();
        $sql = "SELECT p.bn,p.name,p.product_id FROM sdb_ome_products p WHERE  p.product_id in(".$product_id_str.")  AND p.type='normal'";
        
        $data = $db->select($sql);
        return $data;
    }

    /**
    *获取自定义搜素选项
    *return array 
    **/
    public function get_search_options(){
        $options = array(
            'bn'=>'货品编码',
            'name'=>'货品名称',
            'brand'=>'商品品牌',
            //'pack'=>'商品包',
        );
        return $options;
    }

    /**
    *获取自定义搜素选项
    *return array 
    **/
    public function get_search_list(){
        $brandObj = &app::get('ome')->model('brand');
        //$packObj = &app::get('ome')->model('pack');//
        $brand_tmp =$brandObj->getList('brand_name,brand_id');
        $brand = array();
        foreach($brand_tmp as $branddata){
            $brand[$branddata['brand_id']] = $branddata['brand_name'];
        }
        //$pack_tmp = $packObj->getList('pack_name');//
        $pack = array();
        //foreach($pack_tmp as $packdata){
           // $pack[] = $packdata['pack_name'];
        //}
        $list = array(
            'brand'=>$brand,
            //'pack'=>$pack,
        );
        return $list;
    }

    /**
    *组织filter条件
    *return array 
    **/
    public function get_filter($data,$offset='0',$limit='999999'){
        if(empty($data['search_key']) || empty($data['search_value'])){
            return false;
        }
        $db = kernel::database();
        $base_wheresql = ' AND type=\'normal\'';
        switch ($data['search_key']) {
            case 'bn':
                $sql = "select product_id  from sdb_ome_products where bn like '".$data['search_value']."%' and product_id not in (SELECT inner_product_id FROM sdb_console_foreign_sku WHERE wms_id = '".$data['wms_id']."') $base_wheresql limit ".$offset.",".$limit;
                $row = $db->select($sql);
                break;
            
            case 'name':
                $sql = "select product_id  from sdb_ome_products where name like '".$data['search_value']."%' and product_id not in (SELECT inner_product_id FROM sdb_console_foreign_sku WHERE wms_id = '".$data['wms_id']."') $base_wheresql limit ".$offset.",".$limit;
                $row = $db->select($sql);
                break;

            case 'brand':
                $base_wheresql = ' AND p.type=\'normal\'';
                $sql = "select p.product_id  from  sdb_ome_products as p LEFT JOIN sdb_ome_goods as g ON g.goods_id=p.goods_id left join sdb_ome_brand as b on g.brand_id = b.brand_id where b.brand_id=".$data['search_value']." and p.product_id not in (SELECT inner_product_id FROM sdb_console_foreign_sku WHERE wms_id = '".$data['wms_id']."') $base_wheresql limit ".$offset.",".$limit;
                
                $row = $db->select($sql);
                break;

            case 'pack':
                //$sql = "select pr.relation_id as product_id from sdb_ome_pack_relation as pr left join sdb_ome_pack as p on pr.pack_id = p.pack_id where p.pack_name like '".$data['search_value']."%' and pr.relation_id not in (SELECT inner_product_id FROM sdb_omewms_foreign_sku WHERE wms_id = '".$data['wms_id']."') limit ".$offset.",".$limit;
               // $row = $db->select($sql);
                break;
        }
        
        $data = array();
        foreach($row as $v){
            $data[] = $v['product_id'];
        }
        return $data;
    }

    /*通过搜索条件获取未分派的商品
    *@params $filter 搜索条件
    *return array 未分派的商品
    **/
    public function get_goods_by_product_ids($product_ids){
        $db = kernel::database();
        $product_id_str = implode($product_ids, ',');
        $sql = "SELECT b.brand_name,p.bn,p.name,p.product_id FROM sdb_ome_products p LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id LEFT JOIN sdb_ome_brand b ON b.brand_id = g.brand_id
                WHERE  p.product_id in (".$product_id_str.") AND p.type='normal' ";
                
        $data = $db->select($sql);
        return $data;
    }

    /*通过搜索条件获取未分派的商品数量
    *@params $data 搜索条件
    *return array 未分派的商品
    **/
    public function get_goods_count_by_search($data){
        $db = kernel::database();
        $base_wheresql = ' AND type=\'normal\'';
        switch ($data['search_key']) {
            case 'bn':
                $sql = "select count('product_id') as count  from sdb_ome_products where bn like '".$data['search_value']."%' and product_id not in (SELECT inner_product_id FROM sdb_console_foreign_sku WHERE wms_id = '".$data['wms_id']."') $base_wheresql";
                $row = $db->select($sql);
                break;
            
            case 'name':
                $sql = "select count('product_id') as count  from sdb_ome_products where name like '".$data['search_value']."%' and product_id not in (SELECT inner_product_id FROM sdb_console_foreign_sku WHERE wms_id = '".$data['wms_id']."') $base_wheresql";
                $row = $db->select($sql);
                break;

            case 'brand':
                $base_wheresql = ' AND p.type=\'normal\'';
                $sql = "select count('product_id') as count  from sdb_ome_products as p LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id left join sdb_ome_brand as b on g.brand_id = b.brand_id where b.brand_name like '".$data['search_value']."%' and p.product_id not in (SELECT inner_product_id FROM sdb_console_foreign_sku WHERE wms_id = '".$data['wms_id']."') $base_wheresql ";
                $row = $db->select($sql);
                break;

            case 'pack':
               // $sql = "select count('product_id') as count  from sdb_ome_pack_relation as pr left join sdb_ome_pack as p on pr.pack_id = p.pack_id where p.pack_name like '".$data['search_value']."%' and pr.relation_id not in (SELECT inner_product_id FROM sdb_omewms_foreign_sku WHERE wms_id = '".$data['wms_id']."')";
                //$row = $db->select($sql);
                break;
        }
        return $row[0]['count'];
    }

    /**
    * 获得非selfwms wms_id数组
    * flag notequal
    */
    function get_wms_list($type,$flag='') {
        $wms_list = kernel::single('channel_func')->getWmsChannelList();
        
        $wms = array();
        if ($flag=='notequal') {
            foreach ($wms_list as $v) {
                if ($v['adapter']!=$type) {
                    $wms[] = $v['wms_id'];
                }
            }
            
        }else{
            foreach ($wms_list as $v) {
                if ($v['adapter']==$type) {
                    $wms[] = $v['wms_id'];
                }
            }
        }   
        
        return $wms;


    }

    /**
    * 商品同步发起通知数据
    */
    function syncProduct_notifydata($wms_id,$product_sdf,$branch_bn='') {
        #error_log(var_export($product_sdf,1),3,__FILE__.'.log');
        if($wms_id == '0'){
            $wms_list = kernel::single('channel_func')->getWmsChannelList();
            
        }else{
            $wms_channellist = kernel::single('channel_func')->getWmsChannelList();
            $wms_list = array();
            foreach($wms_channellist as $chwms){
                if($wms_id==$chwms['wms_id']){
                    $wms_list[] = $chwms;
                    break;
                }
            }
        }
        foreach($wms_list as $wms){
            if (is_array($product_sdf)) {
                $wms_id = $wms['wms_id'];
                
                foreach($product_sdf as $key=>$items ){
                    $bn = $items['bn'];
                    $sku_info = kernel::single('console_foreignsku')->sku_info($bn,$wms_id);

                    if(empty($sku_info)) continue;
                    if ($sku_info['sync_status'] == '2' || $sku_info['sync_status'] == '3') continue;//已同步的不发起同步 同步中的不发起同步
                    $sync_sku[] = array(
                        'inner_sku' => $bn,
                        'outer_sku' => '',
                        'status' => '2',
                    );
                    // 组织sku数据
                    $sync_method = $sku_info['sync_status'] == 4 ? 'update' : 'add';
                    
                    $items['branch_bn'] = $branch_bn;
                    $products_sdf[$sync_method][] = $items;
                }
               if($products_sdf){

                foreach ($products_sdf as $kdata=>$vdata) {
                    if($kdata == 'add'){
                        $method = 'create';
                    }else{
                        $method = 'update';
                    }
                    
                    $result = kernel::single('console_event_trigger_goodssync')->$method($wms_id, $vdata, false);   
                }
               }
                
           }

        }
        
        return true;
    }

        
}