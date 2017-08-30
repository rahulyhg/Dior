<?php
class purchase_products_import {

    function run(&$cursor_id,$params){
        //$pObj      = &app::get($params['app'])->model($params['mdl']);
        //$branchObj = &app::get('ome')->model('branch');
        $productsObj = &app::get('ome')->model('products');
        $branchPosObj = &app::get('ome')->model('branch_pos');
        $branchProductObj = &app::get('ome')->model('branch_product');
        $branchProductPosObj = &app::get('ome')->model('branch_product_pos');
        $inventoryItemsObj = &app::get('purchase')->model('inventory_items');
        $inventoryObj = &app::get('purchase')->model('inventory');
        $branch_id = $params['sdfdata']['branch_id'];
        $branch    = $params['sdfdata']['branch'];
        $inv_id    = $params['sdfdata']['inv_id'];
        $total = 0;
        foreach ($params['sdfdata']['products'] as $v){
            $inv_item = array();
            $product = $productsObj->dump($v['product_id'],'product_id,store');
            $pos = $branchPosObj->dump(array('store_position'=>$v['store_position'],'branch_id'=>$branch_id),'pos_id');
            if ($product){
                if ($pos){
                    $inv_item['inventory_id'] = $inv_id;
                    $inv_item['product_id'] = $v['product_id'];
                    $inv_item['pos_id'] = $pos['pos_id'];
                    $inv_item['name'] = $v['name'];
                    $inv_item['bn'] = $v['bn'];
                    $inv_item['spec_info'] = $v['spec_info'];
                    $inv_item['unit'] = $v['unit'];
                    $inv_item['pos_name'] = $v['store_position'];
                    $inv_item['accounts_num'] = $v['store'];
                    $inv_item['actual_num'] = $v['num'];
                    $inv_item['shortage_over'] = $v['num']-$v['store'];
                    $inv_item['price'] = $v['price'];
                    $inv_item['availability'] = 'true';
                    $inv_item['memo'] = $v['condition'];
                    
                    $inventoryItemsObj->save($inv_item);//记录导入明细
                    
                    $add_num = $v['num']-$v['store'];//加这个数
                    /*
                    //更新库存
                        if($add_num > 0){
                            $operator = '+';
                            $update_num = $add_num;
                        }else if($add_num < 0){
                            if($product['store'] > abs($add_num)){
                                $operator = '-';
                                $update_num = abs($add_num);
                            }else{
                                $operator = '=';
                                $update_num = 0;
                            }
                        }
					
                    if($add_num != 0){//避免不必要的更新
                        $productsObj->chg_product_store($product['product_id'],$update_num,$operator);
                    }
					
                    $branp = $branchProductObj->dump(array('product_id'=>$product['product_id'],'branch_id'=>$branch_id),'branch_id,product_id,store');
                    if ($branp){
                        if($add_num != 0){//避免不必要的更新
                            $strUpdateStore = '';
                            if($add_num > 0){
                                $strUpdateStore = "store=IFNULL(store,0)+$add_num";
                            }else if($add_num < 0){
                                if($branp['store'] > abs($add_num)){
                                    $strUpdateStore = "store=IFNULL(store,0)-".abs($add_num);
                                }else{
                                    $strUpdateStore = "store=0";
                                }
                            }
						
                        $sql = "UPDATE sdb_ome_branch_product SET $strUpdateStore WHERE product_id=".$product['product_id']." AND branch_id=".$branch_id;
                        $productsObj->db->exec($sql);//更新branch_product表
						}
                    }else {
                        $bp['product_id'] = $product['product_id'];
                        $bp['branch_id'] = $branch_id;
						
                        if($add_num > 0){
                            $bp['store'] = $add_num;
                        }else{
                            $bp['store'] = 0;
                        }
                       
                        $bp['store_freeze'] = 0;
                        
                        $branchProductObj->save($bp);
                    }
                    */
                    $branpp = $branchProductPosObj->dump(array('pos_id'=>$pos['pos_id'],'product_id'=>$product['product_id']),'pos_id,product_id,store,branch_id');
                    if ($branpp){
                        if($add_num != 0){//避免不必要的更新
                            $strUpdateStore = '';
                            if($add_num > 0){
                                $branchProductPosObj->change_store($branch_id,$product['product_id'],$pos['pos_id'],$add_num,'+');
                            }else if($add_num < 0){
                                $branchProductPosObj->change_store($branch_id,$product['product_id'],$pos['pos_id'],abs($add_num),'-');
                            }
						
                        //$sql = "UPDATE sdb_ome_branch_product_pos SET $strUpdateStore WHERE pos_id=".$pos['pos_id']." AND product_id=".$product['product_id'];
                        //$productsObj->db->exec($sql);//更新branch_product_pos表
						}
                    }else {
                        $bpp['product_id'] = $product['product_id'];
                        $bpp['pos_id'] = $pos['pos_id'];
                        $bpp['branch_id'] = $branch_id;
                       
                        if($add_num > 0){
                            $bpp['store'] = $add_num;
                        }else{
                            $bpp['store'] = 0;
                        }
                       
                        $default_branpp = $branchProductPosObj->getList('product_id',array('branch_id'=>$branch_id,'product_id'=>$product['product_id'],'default_pos'=>'true'));
                        if(is_array($default_branpp) && count($default_branpp)>0){
                            $bpp['default_pos'] = 'false';
                        }else{
                            $bpp['default_pos'] = 'true';
                        }
                       
                        $bpp['create_time'] = time();
                        
                        $branchProductPosObj->save($bpp);
                        $branchProductPosObj->count_store($product['product_id'],$branch_id);
                    }
                    
                    //统计差异金额
                    $total += $add_num*$v['price'];
                }else {
                    $inv_item['inventory_id'] = $inv_id;
                    $inv_item['product_id'] = $v['product_id'];
                    $inv_item['pos_id'] = $v['pos_id'];
                    $inv_item['name'] = $v['name'];
                    $inv_item['bn'] = $v['bn'];
                    $inv_item['spec_info'] = $v['spec_info'];
                    $inv_item['unit'] = $v['unit'];
                    $inv_item['pos_name'] = $v['store_position'];
                    $inv_item['accounts_num'] = $v['store'];
                    $inv_item['actual_num'] = $v['num'];
                    $inv_item['shortage_over'] = 0;
                    $inv_item['price'] = $v['price'];
                    $inv_item['memo'] = $v['condition'];
                    $inv_item['availability'] = 'false';
                    $inv_item['error_log'] = $v['store_position']."：此仓库货位不存在;";
                    
                    $inventoryItemsObj->save($inv_item);
                }
            }else {
                $inv_item['inventory_id'] = $inv_id;
                $inv_item['product_id'] = $v['product_id'];
                $inv_item['pos_id'] = $pos?$pos['pos_id']:0;
                $inv_item['name'] = $v['name'];
                $inv_item['bn'] = $v['bn'];
                $inv_item['spec_info'] = $v['spec_info'];
                $inv_item['unit'] = $v['unit'];
                $inv_item['pos_name'] = $v['store_position'];
                $inv_item['accounts_num'] = $v['store'];
                $inv_item['actual_num'] = $v['num'];
                $inv_item['shortage_over'] = 0;
                $inv_item['price'] = $v['price'];
                $inv_item['availability'] = 'false';
                $inv_item['memo'] = $v['condition'];
                if ($pos){
                    $inv_item['error_log'] = $v['product_id']."：此货品不存在";
                }else {
                    $inv_item['error_log'] = $v['product_id']."：此货品不存在;".$v['store_position']."此货位不存在;";
                }
                
                $inventoryItemsObj->save($inv_item);
            }
        }
        //$sql = "SELECT SUM((actual_num-accounts_num)*price) AS 'total' FROM sdb_purchase_inventory_items WHERE inventory_id=".$inv_id;
        //$tmp = $inventoryItemsObj->db->selectrow($sql);
        $inv['inventory_id'] = $inv_id;
        $inv['difference'] = $total;//$tmp['total'];
        $inv['import_status'] = '2';
        $inv['update_status'] = '2';
        
        
        $inventoryObj->save($inv);
        return false;
    }
}
