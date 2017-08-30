<?php
class ome_finder_extend_supply_product {
    
    function get_extend_colums(){
        
        //供应商
        $oSupplier = &app::get('purchase')->model('supplier');
        $suppliers = $oSupplier -> getList('supplier_id,name');
        foreach($suppliers as $v) {
            $v['name'] = str_replace('有限公司','',$v['name']);
            $v['name'] = str_replace('公司','',$v['name']);
            $v['name'] = mb_substr($v['name'],0,10,'utf-8');
            $supplier_list[$v['supplier_id']] = $v['name'];
        }
        
        $db['supply_product']=array (
          'columns' => 
          array (
            'supplier_id' => 
            array (
              'type' => $supplier_list,
              'default' => 0,
              'label' => '供应商',
              'filtertype' => 'yes',
              'filterdefault' => true,
            ),
            
            'filter_type' => 
            array (
              'type' => array(
                1=>'可用库存 小于 安全库存',
                2=>'真实库存 小于 安全库存',
                3=>'可用库存 小于 指定数量',
                4=>'真实库存 小于 指定数量',
              ),
              'default' => 0,
              'label' => '筛选条件',
              'filtertype' => 'yes',
              'filterdefault' => true,
            ),
            
            'appoint_store' => 
            array (
              'type' => 'int(32)',
              'default' => 0,
              'label' => '指定数量',
              'filtertype' => 'yes',
              'filterdefault' => true,
            ),
            
          ),
        );
        return $db;
    }
}
