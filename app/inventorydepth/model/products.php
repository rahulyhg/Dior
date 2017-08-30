<?php

class inventorydepth_mdl_products extends dbeav_model{

    public function table_name($real=false)
    {
        $table_name = 'products';
        if($real){
            return kernel::database()->prefix.app::get('ome')->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    public function get_schema()
    {
        return app::get('ome')->model('products')->get_schema();
    }
}
?>