<?php
class wmsmgr_mdl_wms extends channel_mdl_channel{

    public function table_name($real = false){
        if($real){
           $table_name = 'sdb_channel_channel';
        }else{
           $table_name = 'channel';
        }
        return $table_name;
	}

    public function get_schema(){
        return app::get('channel')->model('channel')->get_schema();
    }

}