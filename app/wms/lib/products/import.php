<?php
class wms_products_import {

    function run(&$cursor_id,$params){

        $productsObj = &app::get('ome')->model('products');
        $branchPosObj = &app::get('ome')->model('branch_pos');
        $branchProductObj = &app::get('ome')->model('branch_product');
        $inventoryItemsObj = &app::get('taoguaninventory')->model('inventory_items');
        $inventoryObj = &app::get('taoguaninventory')->model('inventory');
        $branch_id = $params['sdfdata']['branch_id'];
        if(!$branch_id) return false;

        $branch    = $params['sdfdata']['branch'];
        $inv_id    = $params['sdfdata']['inv_id'];
        $total = 0;
        $import_type = $params['sdfdata']['import_type'];

        $db = kernel::database();
        foreach ($params['sdfdata']['products'] as $v){

            $inv_item = array();
            $product = $productsObj->dump($v['product_id'],'product_id,store');

            if ($product){
                    //获取系统内的真实库存
                    //$v['store'] = $branchProductObj->getStoreByBranch($v['product_id'],$branch_id);
                    $sqlstr = '';
                     kernel::single('taoguaninventory_inventorylist')->create_branch_product($branch_id,$v['product_id']);
                    if($v['pos_name']!=''){
                        if($import_type==0){
                            $pos_id = kernel::single('taoguaninventory_inventorylist')->create_branch_product_pos($branch_id,$v['pos_name'],$v['product_id']);
                            $inv_item['pos_id'] = $pos_id;
                            $inv_item['pos_name'] = $v['pos_name'];
                            $sqlstr.=' AND io.pos_id='.$pos_id;
                        }
                    }else{
                        $inv_item['pos_id'] = 0;
                        $inv_item['pos_name'] = '';
                    }
                    $sql = 'SELECT inv.inventory_id,inv.difference,inv.op_id,inv.inventory_name,io.obj_id  FROM sdb_taoguaninventory_inventory as inv left join sdb_taoguaninventory_inventory_object as io on inv.inventory_id=io.inventory_id WHERE inv.branch_id='.$branch_id.' AND inv.confirm_status=1 AND io.product_id='.$v['product_id'].$sqlstr.' ORDER BY inv.inventory_id';

                    $inventory = $db->selectRow($sql);

                    $inv_item['inventory_id'] = $inv_id;
                    $inv_item['product_id'] = $v['product_id'];

                    $inv_item['name'] = $v['name'];
                    $inv_item['bn'] = $v['bn'];
                    $inv_item['spec_info'] = $v['spec_info'];
                    $inv_item['unit'] = $v['unit'];

                    //$inv_item['accounts_num'] = $v['store'];
                    $inv_item['number'] = $v['num'];
                    $inv_item['shortage_over'] = $v['num']-$v['store'];
                    $inv_item['price'] = $v['price'];
                    $inv_item['availability'] = 'true';
                    $inv_item['memo'] = $v['condition'];
                    $inv_item['branch_id'] = $branch_id;
                    $total += $inv_item['shortage_over']*$v['price'];

                    if($inventory){

                        $inv_item['num_over'] = 1;

                    }
                    $invitem = $inventoryItemsObj->dump(array('inventory_id'=>$inv_id,'product_id'=>$v['product_id']),'item_id');
                    if($invitem){
                        $inv_item['item_id'] = $invitem['item_id'];
                    }

                    $result=kernel::single('wms_inventorylist')->update_inventory_item($inv_item);

            }
        }

        return false;
    }
}
