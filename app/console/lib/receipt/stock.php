<?php
/**
* 出入库单据相关处理
*/
class console_receipt_stock{
    
    private static $iso_status =array(
        'PARTIN'=>2 ,
        'FINISH'=>3,
        'CLOSE'=>4,
        'CANCEL'=>4,
        'FAILED'=>4,
       
        );
    
    /**
    * 出入库据保存
    * io 0 出库 1 入库
    * 入库会有残损
    * 出库不会
    * $array $data 
    */
    function do_save($data,$io,&$msg){
        set_time_limit(0);

        $Oiso = &app::get('taoguaniostockorder')->model("iso");
        $Oisoitems = &app::get('taoguaniostockorder')->model("iso_items");
        $iso = $Oiso->dump(array('iso_bn'=>$data['io_bn']),'*');
        $purchasereturnObj = kernel::single('console_receipt_purchasereturn');
        //branch_id，iso_bn，iso_id,supplier_id,supplier_name,cost_tax,oper,create_time,operator
        #保存传过来的数据
        $iso_data = array();
        
        $defective_status = true;#是否有入库残损可能
        $iostock_update = true;#残损是否需要确认
        if ($io == '0'){# 出库标识
            $defective_status = false;
            $branch_id = $iso['branch_id'];
            $node_type =kernel::single('ome_branch')->getNodetypBybranchId($branch_id);
            if (in_array($node_type,array('kejie'))) {
                if ($data['io_status'] == 'FINISH' && !$data['items']) {
                    $item_list = array();
                    $iso_items = $Oisoitems->getlist('bn,nums as num',array('iso_id'=>$iso['iso_id']),0,-1);
                    $data['items'] = $iso_items;
                }
            }
        }
        $operator       = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        $io_status = $data['io_status'];
        #需出入库数据
        $iostock_data = array(
                'iso_id'=>$iso['iso_id'],
                'type_id'=>$iso['type_id'],
                'iso_bn'=>$iso['iso_bn'],
                'memo'=>$data['memo'],
                'operate_time'=>$data['operate_time'],
                'branch_id'=>$iso['branch_id'],
                );
        $items = $data['items'];
//
        if ($items){
            #检查货号是否都存在
            if (!$this->checkBnexist($iso['iso_id'],$items)){
                $msg = '包含本单不存在的货号!';

                return false;
            }
            if ($io == '0'){#检查库存是否不足
                
                if (!$purchasereturnObj->checkStore($iso['branch_id'],$items,$msg)) {
                    
                    return false;
                }
            }
           
            foreach ($data['items'] as $item){
                $iso_item = $Oisoitems->dump(array('iso_id'=>$iso['iso_id'],'bn'=>$item['bn']),'iso_items_id,defective_num,normal_num,nums,price,product_id');
                $nums = 0;
                if ($iso_item){
                    if (!$defective_status){#出
                        $nums=intval($item['num']);
                    }else{
                        $nums=intval($item['normal_num']);#入
                    }
                    
                    $item_data = array('iso_items_id'=>$iso_item['iso_items_id']);
                    
                    if ($nums>0){#良品数量大于0时才更新
                        $normal_num    = $iso_item['normal_num'] + $nums;
                        $item_data['normal_num'] = $normal_num;
                       
                        $Oisoitems->save($item_data);#更新出入库数量
                        $iostock_data['items'][] = array(
                            'bn'=>$item['bn'],
                            'nums'=>$nums,#请求入库数量
                            'price'=>$iso_item['price'],
                            'iso_items_id'=>$iso_item['iso_items_id'],
                            'product_id'=>$iso_item['product_id'],
                            'normal_num'=>$normal_num,#已入库数量
                            'num'=>$iso_item['nums'],#原申请数量
                        );
                    }
                    if ($defective_status){#有残损标识
                        if ($item['defective_num']>0){
                            $defective_num    = $iso_item['defective_num'] + $item['defective_num'];
                            $item_data['defective_num'] = $defective_num;
                            $Oisoitems->save($item_data);#更新出入库数量
                            $iostock_update = false;
                        }
                    }
                    
                }
            }
        }
        
        #对传过来货品出入库
        if (count($iostock_data['items'])>0){
            //
            kernel::single('console_iostockorder')->confirm_iostockorder($iostock_data,$iso['type_id'],$msg);
            #对货品释放冻结库存
            if ($io == '0'){#出库
                $this->clear_stockout_store_freeze($iostock_data,'-');
            }
            
        }
        #更新单据状态
        $io_update_data = array('iso_status'=>self::$iso_status[$io_status],'confirm'=>'Y');
        
        if (!$iostock_update){#是否需要确认
            $io_update_data['defective_status'] = '1';#未确认
            
        }
        #是否有备注
        if (!$data['memo']){
            $memo = '';
            if (!$iso['memo']){
                $memo.= $iso['memo'];
            }
            $memo.=htmlspecialchars($data['memo']);
            $io_update_data['memo'] = $memo;
        }
        $Oiso->update($io_update_data,array('iso_id'=>$iso['iso_id']));

        // 扣除在途库存
        if ($iso['type_id'] == '4') {
            $this->reduceArriveStore($iostock_data);
        }
        if ($iso['type_id'] == '40') {
            $this->addArriveStore($iostock_data);
        }

        if ($iso['type_id']=='40' && $io_status == 'FINISH'){
            #调拔出库且为完成时执行调拔入库
            
            kernel::single('console_iostockdata')->allocate_out($iso['iso_id']);
        }
        if ($io == '0' && $io_update_data['iso_status'] == '3'){
            $this->cleanFreezeStore($iso['iso_id'],$iso['iso_bn']);
        }
        return true;
        
    }
    
    

    /**
     *
     * 出入库单取消
     * @param $io_bn 出入库单号
     * @param $io 出 入标识
     */
    public function cancel($io_bn,$io){
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        
        $result = $oIso->update(array('iso_status'=>'4'),array('iso_bn'=>$io_bn));
        //释放冻结库存
        if ($io=='0'){
            $this->clear_stockout_store_freeze(array('iso_bn'=>$io_bn),'-');
        }

        return $result;
    }

    /**
    * 判断出入库明细是否存在
    *
    */
    public function checkExist($io_bn){
        $Oiso = &app::get('taoguaniostockorder')->model("iso");
        
        $iso = $Oiso->dump(array('iso_bn'=>$io_bn),'*');
        return $iso;
        
    }
    
    /**
     * 检查传过来的货号是否都存在于单据中
     * @param iso_id 出入库单ID
     * @param items array 货品明细
     */
     public function checkBnexist($iso_id,$items){#taoguaniostockorder_iso
        $oPo = &app::get('taoguaniostockorder')->model("iso");
        $bn_array = array();
        foreach($items as $item){
            $bn_array[]=$item['bn'];
        }
        $bn_total = count($bn_array);

        $bn_array = '\''.implode('\',\'',$bn_array).'\'';
 
        $iso_items = $oPo->db->selectrow('SELECT count(iso_items_id) as count FROM sdb_taoguaniostockorder_iso_items WHERE iso_id='.$iso_id.' AND bn in ('.$bn_array.')');

       
        if ($bn_total!=$iso_items['count']){#比较数目是否相等
            return false;
        }
        return true;
    }

    /**
     *
     * 检查出入库单是否有效
     * @param  $iso_bn 出入库单编号
     * @param $status 需要执行状态
     * @msg 返回结果
     *
     */
    public function checkValid($iso_bn,$status,&$msg){
        $iso = $this->checkExist($iso_bn);
        $iso_status = $iso['iso_status'];
        switch($status){
            case 'PARTIN':
            case 'FINISH':
                if ($iso_status=='3'){
                    $msg = '单据已完成,不可以入库';
                    return false;
                }
                if ($iso_status == '4'){
                    $msg = '单据已取消，不可以入库';
                    return false;
                }
                break;
            case 'CANCEL':
            case 'CLOSE':
                if ($iso_status=='3' || $iso_status=='2'){

                    $msg = '单据已部分或全部入库,不可以取消';
                    return false;
                }
                if ($iso_status == '4'){
                    $msg = '单据已取消，不可以再次取消';
                    return false;
                }
                break;
        }
        return true;
    }

    /**
     * 调拨出库增加在途库存
     *
     * @return void
     * @author 
     **/
    private function addArriveStore($data)
    {
        $iso_bn = $data['iso_bn'];
        $oIso = &app::get('taoguaniostockorder')->model('iso');
        $iso = $oIso->dump(array('iso_bn'=>$iso_bn),'branch_id,iso_id,iso_status,original_id,type_id');

        $oItems = &app::get('taoguaniostockorder')->model('iso_items');
        if ($data['items']){
            $items = $data['items'];
        }else{
            $items = $oItems->getlist('product_id,bn,nums',array('iso_id'=>$iso['iso_id']),0,-1);
        }

        // 调拨单

        $oAppropriation_items = &app::get('taoguanallocate')->model('appropriation_items');
        $appropriaton_items = $oAppropriation_items->getlist('*',array('appropriation_id'=>$iso['original_id']),0,1);
        $to_branch_id = $appropriaton_items[0]['to_branch_id'];
        

        $time = time();
        foreach ($items as $item) {
            if ($item['nums'] > 0) {
                $sql = "UPDATE sdb_ome_branch_product SET arrive_store = arrive_store+{$item['nums']} ,last_modified = {$time} WHERE product_id = {$item['product_id']} AND branch_id={$to_branch_id}";
                $oIso->db->exec($sql);

            }
        }
    }

    /**
     * 调拨入库扣除在途库存
     *
     * @return void
     * @author 
     **/
    private function reduceArriveStore($data)
    {
        $iso_bn = $data['iso_bn'];
        $oIso = &app::get('taoguaniostockorder')->model('iso');
        $iso = $oIso->dump(array('iso_bn'=>$iso_bn),'branch_id,iso_id,iso_status,original_id,type_id');

        $oItems = &app::get('taoguaniostockorder')->model('iso_items');
        if ($data['items']){
            $items = $data['items'];
        }else{
            $items = $oItems->getlist('product_id,bn,nums',array('iso_id'=>$iso['iso_id']),0,-1);
        }

        $time = time();
        foreach ($items as $item) {
            if ($item['nums'] > 0) {
                $sql = "UPDATE sdb_ome_branch_product SET arrive_store = IF(arrive_store>={$item['nums']},arrive_store-{$item['nums']},0) ,last_modified = {$time} WHERE product_id = {$item['product_id']} AND branch_id={$iso['branch_id']}";
                $oIso->db->exec($sql);

            }
        }
    }

    /**
    * 释放冻结库存
    * array data 当有明细时,操作对应明细，否则操作所有
    */
    public function clear_stockout_store_freeze($data,$io_type='-'){
        $iso_bn = $data['iso_bn'];
        $oIso = &app::get('taoguaniostockorder')->model('iso');
        $oItems = &app::get('taoguaniostockorder')->model('iso_items');
        $iso = $oIso->dump(array('iso_bn'=>$iso_bn),'branch_id,iso_id,iso_status,original_id,type_id');
        $oProducts = &app::get('ome')->model('products');
        $oBranch_product = &app::get('ome')->model('branch_product');

        if ($data['items']){
            $items = $data['items'];
        }else{
            $items = $oItems->getlist('product_id,bn,nums',array('iso_id'=>$iso['iso_id']),0,-1);
        }

        $branch_id = $iso['branch_id'];
        if ($io_type == '+'){#添加冻结库存
            foreach($items as $item){
                $product_id = $item['product_id'];
                $nums = $item['nums'];
                $oProducts->chg_product_store_freeze($product_id,$nums,'+','other');
                $oBranch_product->chg_product_store_freeze($branch_id,$product_id,$nums,'+','other');
            }
        }else{
            #释放数量不得大于原单据入库数量
            #nums 已入库数量 num原申请数量 
            
            foreach($items as $item){
                $product_id = $item['product_id'];
                if (($item['normal_num'] && $item['num'])&& ($item['normal_num']>$item['num'])){
                    $effective_num = $item['normal_num']-$item['num'];#已入库数量与请求数量差值
                    if ($effective_num<=$item['nums']){#差值与原申请数量比较
                        $nums = $item['nums']-$effective_num;
                    }else{
                        $nums = 0;
                    }
                }else{
                    $nums = $item['nums'];
                }
                if ($nums>0) {
                    $oProducts->chg_product_store_freeze($product_id,$nums,'-','other');
                    $oBranch_product->chg_product_store_freeze($branch_id,$product_id,$nums,'-','other');
                }
                
                
            }
        }
        

        return true;
    }

    /**
    * 查看差异数据
    */
    function difference_stock($iso_bn){
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        $iso = $oIso->dump(array('iso_bn'=>$iso_bn),'iso_id');
        $iso_id = $iso['iso_id'];
        $sql = 'SELECT i.nums,i.normal_num,i.defective_num,i.bn,p.name,p.spec_info,p.barcode FROM sdb_taoguaniostockorder_iso_items as i LEFT JOIN sdb_ome_products as p ON i.bn=p.bn WHERE i.iso_id='.$iso_id.' AND (i.normal_num!=i.nums OR i.defective_num>0)';
        
        $iso_item = $oIso->db->select($sql);
        return $iso_item;
    }

    /**
    * 清除冻结库存
    */
    function cleanFreezeStore($iso_id,$iso_bn){
        $oIso_items = &app::get('taoguaniostockorder')->model("iso_items");
        $SQL = 'SELECT i.product_id,i.nums,i.normal_num FROM sdb_taoguaniostockorder_iso_items as i WHERE i.iso_id='.$iso_id.' AND i.nums>i.normal_num ';
        $items = $oIso_items->db->select($SQL);
       
        $item = array('iso_bn'=>$iso_bn);
        foreach($items as $items){

            $nums = $items['nums']-$items['normal_num'];
            $product_id =  $items['product_id'];
            $item['items'][] = array(
                        'nums'=>$nums,'product_id'=>$product_id        
            );
            
        }
        
        $this->clear_stockout_store_freeze($item,'-');
    }
}
    

?>