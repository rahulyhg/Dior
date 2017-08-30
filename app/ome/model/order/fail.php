<?php
class ome_mdl_order_fail extends ome_mdl_orders{
    public function table_name($real=false){
        $table_name = 'orders';
        if($real){
            return kernel::database()->prefix.'ome_'.$table_name;
        }else{
            return $table_name;
        }
    }
}
?>