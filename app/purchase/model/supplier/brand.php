<?php
/**
 * 供应商关联品牌
 */

class purchase_mdl_supplier_brand extends dbeav_model{
    
    /*
     * 关联供应商提供的品牌
     */
    function saveSupplierBrand($datas){
        
        if ($this->save($datas)) return true;
        else return false;
        
    }
    
}
?>