<?php
class ome_finder_supply_product{
    
    var $column_safe_store = '安全库存数';
    var $column_safe_store_order = 60;
    public function column_safe_store($row){
        $safe_store = $row['safe_store'];
        $branch_id = $row['branch_id'];
        $product_id = $row['product_id'];
        $unique_id = $branch_id.'_'.$product_id;
        return "<span id='state$unique_id'></span>
			<input name='safe_store' id='$unique_id' 
			onfocus='focusin(this)' onkeydown='keydown(this,event);' 
			onchange='focusout(this,6)' onblur='focusout(this)' 
			type='text' class='txt' value='$safe_store' maxlength='8' size='8' />
			<input id='_$unique_id' type='hidden' value='$safe_store' />
		";
    }    
}
