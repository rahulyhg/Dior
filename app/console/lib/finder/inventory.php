<?php
class console_finder_inventory{
    //var $detail_base = "基本信息";
    var $detail_item = "详情";

    /*function detail_base($appro_id){
        $render = &app::get('console')->render();
        
    }*/
    
    function detail_item($inventory_id){
        $render = &app::get('console')->render();
        $inv_iObj = &app::get('console')->model('inventory_items');
        
        $count = $inv_iObj->count(array('inventory_id'=>$inventory_id));
        if ($count > 20){
            $render->pagedata['many'] = 'true';
            $rows = $inv_iObj->getList('*', array('inventory_id'=>$inventory_id), 0, 20);
        }else {
            $rows = $inv_iObj->getList('*', array('inventory_id'=>$inventory_id), 0, -1);
        }
        $render->pagedata['inventory_id'] = $inventory_id;
        $render->pagedata['rows'] = $rows;
        return $render->fetch("admin/inventory/item.html");
    }

}
?>
