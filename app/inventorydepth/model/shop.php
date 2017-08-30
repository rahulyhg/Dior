<?php
/**
 * @copyright shopex.cn
 * @author ymz lymz.86@gmail.com
 * @version ocs
 */

class inventorydepth_mdl_shop extends dbeav_model {

    public function table_name($real=false)
    {
        $table_name = 'shop';
        if($real){
            return kernel::database()->prefix.app::get('ome')->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    public function get_schema()
    {
        $schema = app::get('ome')->model('shop')->get_schema();
        
        # 重新排序
        $schema['columns']['name']['order'] = 10;
        $schema['columns']['shop_type']['order'] = 20;
        $schema['columns']['last_download_time']['order'] = 50;
        $schema['columns']['last_upload_time']['order'] = 60;

        # 重新进行FINDER排序
        unset($schema['in_list'],$schema['default_in_list']);
        $schema['in_list'] = array(
            0 => 'name',    
            1 => 'shop_type',
            2 => 'last_download_time',
            3 => 'last_upload_time',
        );

        $schema['default_in_list'] = array(
            0 => 'name',
            1 => 'shop_type',
            2 => 'last_download_time',
            3 => 'last_upload_time',
        );

        return $schema;
    }

    public function modifier_shop_type($row)
    {
        $row = $row ? ome_shop_type::shop_name($row) : '';
        return $row;
    }

}
