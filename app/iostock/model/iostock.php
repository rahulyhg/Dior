<?php
class iostock_mdl_iostock extends ome_mdl_iostock{
    function __construct($app){
        parent::__construct(app::get('ome'));
    }

    public function table_name($real=false){
        $table_name = "iostock";
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }


}
