<?php
class wms_inventory{
    /**
    * 分批盘点
    *
    */
    function doajax_inventorylist($data,$itemId,&$fail,&$succ,&$fallinfo){
        $oInventory = &app::get('taoguaninventory')->model('inventory');
        $oProducts      = &app::get('ome')->model("products");
        $inventoryObj = kernel::single('taoguaninventory_inventorylist');
        $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');
        $inventory = $oInventory->db->selectrow('SELECT inventory_type,inventory_bn,branch_id,branch_name,op_id,op_name,confirm_status,inventory_id FROM sdb_taoguaninventory_inventory WHERE inventory_id='.$data['inventory_id']);
        $branch_id = $inventory['branch_id'];
        $inventory_id = $data['inventory_id'];
        $branch = $oInventory->db->selectrow('SELECT branch_bn FROM sdb_ome_branch WHERE branch_id='.$branch_id);
        $item_id_list = array();
        foreach($itemId as $item_id){
            $item_id = explode('||',$item_id);
            $item_id = $item_id[1];
            $item_id_list[] = $item_id;
        }
        $SQL = 'SELECT i.item_id,i.status,i.bn,i.product_id,i.name,i.actual_num,bp.store as accounts_num FROM sdb_taoguaninventory_inventory_items as i 
        LEFT join sdb_ome_products as p ON i.bn=p.bn 
        LEFT JOIN sdb_ome_branch_product as bp ON p.product_id=bp.product_id 
        WHERE i.item_id in('.implode(',',$item_id_list).') AND bp.branch_id='.$branch_id.' AND i.inventory_id='.$inventory_id.' AND i.status=\'false\'';
       
        $inventory_items = $oInventory_items->db->select($SQL);
        $item = array();

        $batch_item_sql = array();
        foreach ($inventory_items as $inventory_item){
            $accounts_num = $inventory_item['accounts_num'];
            $shortage_over = $inventory_item['actual_num']-$accounts_num;
            $item_id = $inventory_item['item_id'];
            $item[] = array(
                'bn'            => $inventory_item['bn'],
                'name'          => $inventory_item['name'],
                'quantity'      => $inventory_item['actual_num'],
                'product_id'    => $inventory_item['product_id'],
                'normal_num'    => $shortage_over,
                'defective_num' => 0,
            );
            $batch_item_sql[] = "('$item_id','$accounts_num','true')";
        }
        #批量更新明细状态和账面数量
        #
        if (count($batch_item_sql)>0){
            $update_item_sql = 'INSERT INTO sdb_taoguaninventory_inventory_items(item_id, accounts_num, `status`) VALUES'.implode(',',$batch_item_sql).' ON DUPLICATE KEY UPDATE accounts_num=VALUES(accounts_num),`status`=VALUES(`status`)';
           
            $oInventory_items->db->exec($update_item_sql);
        }
        $wms_id = kernel::single('ome_branch')->getWmsIdById($inventory['branch_id']);
        $tmp = array(
                'inventory_bn'    => $inventory['inventory_bn'],
                'inventory_type'  => $inventory['inventory_type'],
                'io_source'       => 'selfwms',
                'type'                  => 'once',
                'append'                => 'false',
                'branch_bn'                => $branch['branch_bn'],//仓库编号
                'wms_id'                => $wms_id,
                'wms_bn'                => kernel::single('channel_func')->getWmsBnByWmsId($wms_id),
                'inventory_date'        => time(),
                'memo'                  => $inventory['memo'],
                'items' => $item,
        );
        
        $result = kernel::single('wms_event_trigger_inventory')->apply($wms_id, $tmp, true);
        $this->updateInventory($inventory_id);

        return true;

    }

    /**
    * 更新盘点确认状态
    */
    function updateInventory($inventory_id){
        $oInventory = &app::get('taoguaninventory')->model('inventory');
        $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');
        $count_inventory_items = $oInventory_items->count(array('inventory_id'=>$inventory_id,'status'=>'false'));
        $inventory_data = array();
        
        if($count_inventory_items==0){
            $inventory_data['confirm_status'] = 2;
        }else{
            $inventory_data['confirm_status'] = 4;
        }
        $inventory_data['confirm_time'] = time();
        $inventory_data['confirm_op'] = kernel::single('desktop_user')->get_name();

        $oInventory ->update($inventory_data,array('inventory_id'=>$inventory_id));

    }
   
}


?>