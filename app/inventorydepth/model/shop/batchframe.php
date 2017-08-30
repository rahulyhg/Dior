<?php
/**
*
*/
class inventorydepth_mdl_shop_batchframe extends dbeav_model
{

    public function table_name($real=false)
    {
        $table_name = 'shop_items';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    public function modifier_detail_url($row)
    {
        return <<<EOF
        <a target='_blank' href="{$row}">{$row}</a>
EOF;
    }
}