<?php

class inventorydepth_mdl_goods extends dbeav_model{

    public function table_name($real=false)
    {
        $table_name = 'goods';
        if($real){
            return kernel::database()->prefix.app::get('ome')->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    public function get_schema()
    {
        return app::get('ome')->model('goods')->get_schema();
    }
}
?>