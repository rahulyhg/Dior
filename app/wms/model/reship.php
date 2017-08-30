<?php
    class wms_mdl_reship extends dbeav_model{
    
        public function table_name($real = false){
            if($real){
                $table_name = 'sdb_ome_reship';
        }else{
                $table_name = 'reship';
        }
         return $table_name;
        }

        public function get_schema(){
            return app::get('ome')->model('channel')->get_schema();
        }
    }

?>