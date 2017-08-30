<?php
class replacesku_mdl_order extends ome_mdl_orders{
   public function table_name($real=false){
        $table_name = 'orders';
        if($real){
            return kernel::database()->prefix.'ome_'.$table_name;
        }else{
            return $table_name;
        }
    }

	
	   public function get_schema(){
        return app::get('ome')->model('orders')->get_schema();
    }

	
	
}
?>