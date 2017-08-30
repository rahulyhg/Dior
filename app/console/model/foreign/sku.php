<?php
class console_mdl_foreign_sku extends dbeav_model{
    var $defaultOrder = array('sync_status,inner_sku');

    function exportTemplate(){
        
        $title = $this->import_title();
        foreach($title as $k=>$v){
            $title[$k] = kernel::single('base_charset')->utf2local($v);
        }
        
        return $title;
    }

    //定义导入文件模版字段
    public function import_title(){
        $title = array(
            '*:货品编码',
            '*:货品名称',
        );
        
        return $title;
    }

    public function searchOptions(){
        return array(
            'inner_sku'=>'货品编码',
            'name'=>'货品名称',
        );
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = ' 1 ';
        if(isset($filter['name'])){
            $productsObj = &app::get('ome')->model("products");
            $products = $productsObj->getList('product_id', array('name'=>$filter['name']));
            $product_id = $products[0]['product_id'];

            $where .= " AND inner_product_id = '".$product_id."'";
            unset($filter['name']);
        }
        //商品品牌处理
        if(isset($filter['brand_id'])){
            $sql = "select p.product_id from sdb_ome_products as p,sdb_ome_brand b where b.brand_id = p.brand_id and b.brand_id = '".$filter['brand_id']."'";
            $products_tmp = kernel::database()->select($sql);
            $products = array();
            foreach($products_tmp as $product_id){
                $products[] = $product_id['product_id'];
            }
            $where .= " AND inner_product_id in (".implode($products,',').")";
            unset($filter['brand_id']);
        }
        //商品包处理
//        if(isset($filter['pack_tag'])){
//            $packObj = &app::get('ome')->model("pack");
//            $packrelationObj = &app::get('ome')->model("pack_relation");
//            $pack_id = $packObj->getList('pack_id', array('pack_name'=>$filter['pack_tag']));
//            $product_ids = $packrelationObj->getList('relation_id',array('pack_id'=>$pack_id[0]['pack_id']));
//            $data = array();
//            foreach($product_ids as $product_id){
//                $data[] = $product_id['relation_id'];
//            }
//            $data = implode($data,',');
//            $where .= " AND inner_product_id in (".$data.")";
//            unset($filter['pack_tag']);
//        }
        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".$where;
    }

    public function modifier_sync_status($row){
        $row_name = $this->get_sync_name($row);
        if($row == '4'){
            $render = kernel::single("base_render");
            $msg = '此商品同步成功后，被编辑过，用户可与仓库协商，编辑后的商品是否需要再次进行同步';
            $rs = kernel::single('desktop_view_helper')->block_help('',$msg,$render);
            $data =$row_name."<div style='float:right'>".$rs."</div>";
        }else{
            $data = $row_name;
        }
        return $data;
    }

    public function get_sync_name($sync_status){
        switch($sync_status){
            case 0:
                $name = '未同步';
                break;
            case 1:
                $name = '同步失败';
                break;
            case 2:
                $name = '同步中';
                break;
            case 3:
                $name = '同步成功';
                break;
            case 4:
                $name = '同步后编辑';
                break;
        }
        return $name;
    }

    function prepared_import_csv(){
        set_time_limit(0);
        $this->ioObj->cacheTime = time();
    	   $this->kvdata = '';
        $this->aa = 0;
    }

    function finish_import_csv(){
        $data = $this->kvdata;
        $queueObj = &app::get('base')->model('queue');
        unset($this->kvdata);
        $queueData = array(
            'queue_title'=>'货品分配导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$data['sku']['contents'],
                'app' => 'console',
                'mdl' => 'foreign_sku'
            ),
            'worker'=>'console_foreignsku_import.run',
        );
        $queue_result = $queueObj->save($queueData);
        $queueObj->flush();
        return null;
    }

    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        $this->aa++;
        $fileData = $this->kvdata;
        $wms_id = $_POST['wms_id'];
        if( !$fileData ) $fileData = array();

        if($row){
            if( substr($row[0],0,1) == '*' ){

            }else{

                if(trim($row[0])==''){
                    $msg['error']='货品编码不能为空!';
                    return false;
                  }else if(trim($row[1])==''){
                    $msg['error']='货品名称不能为空!';
                    return false;
                  }else{
                    
                    $productObj = &app::get('ome')->model('products');
                    $product = $productObj->getList('product_id,type',array('bn'=>$row[0]));

                    if(count($product) == '0'){
                        $msg['error']='货品编码在系统中不存在';
                        return false;
                    }else if($product[0]['type'] != 'normal'){
                        
                        $msg['error'] = '只能导入基础货品';
                        return false;
                       
                    }
                    //
                    if($wms_id=='_ALL_'){
                        $wms_list = kernel::single('channel_func')->getWmsChannelList();
                        foreach($wms_list as $v){
                            $wmsid_list[] = $v['wms_id'];
                        }
                    }else{
                        $wmsid_list[] = $wms_id;
                    }
                    //货品编码不能重复
                    $wfsObj = app::get('console')->model('foreign_sku');
                    $info = $wfsObj->getlist('inner_sku',array('inner_sku'=>$row[0],'wms_id'=>$wmsid_list));
                    if(count($info) != '0'){
                            $msg['error'] = '货品编码已存在 ';
                            return false;
                    }
                    foreach($wmsid_list as $wmsid){
                        $data = array(
                            'inner_sku'=>$row[0],
                            'inner_product_id'=>$product[0]['product_id'],
                            'wms_id'=>$wmsid,
                            );
                        $fileData['sku']['contents'][] = $data;
                    }
                }
            }
        }

        $this->kvdata = $fileData;
        
        
        return null;
    }

    
    /**
     *返回商品外部sku
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_product_outer_sku( $wms_id,$bn )
    {
        
        $oForeign_sku = $this->dump(array('inner_sku'=>$bn,'wms_id'=>$wms_id),'outer_sku');
        return $oForeign_sku['outer_sku'];
    }

    
    /**
     * 返回商品内部sku
     * @param   bn
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_product_inner_sku( $wms_id,$bn )
    {

        $oForeign_sku = $this->dump(array('outer_sku'=>$bn,'wms_id'=>$wms_id),'inner_sku');
        return $oForeign_sku['inner_sku'];
    }
    /**
    * 更新货品同步状态
    */
    function update_status($product_id,$bn){
        $SQL = "update sdb_console_foreign_sku SET sync_status='0',inner_sku='".$bn."' WHERE inner_product_id=".$product_id;
        $result = $this->db->exec($SQL);

    }
    
}