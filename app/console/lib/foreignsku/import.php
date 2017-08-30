<?php
class console_foreignsku_import{

    function run(&$cursor_id,$params){
        
        $wfsObj = &app::get('console')->model('foreign_sku');
        foreach($params['sdfdata'] as $k=>$value){
            $inner_sku = $value['inner_sku'];
            $wms_id = $value['wms_id'];
            
            $foreign_detail = $wfsObj->getList('inner_sku',array('inner_sku'=>$inner_sku,'wms_id'=>$wms_id));
            if (empty($foreign_detail[0]['inner_sku'])){
                $wfsObj->insert($value);
            }
        }
        
        return false;
    }
}

?>