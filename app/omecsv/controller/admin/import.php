<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class omecsv_ctl_admin_import extends omecsv_prototype{

    function main(){
        $render = kernel::single('desktop_controller');
        $render->pagedata['ctler'] = $_GET['ctler'];
        $render->pagedata['add'] = $_GET['add'];
        $render->pagedata['_finder'] = $_GET['_finder']['finder_id'];
        $render->pagedata['finder_id'] = $_GET['finder_id'];
		
        $get = kernel::single('base_component_request')->get_get();
        try {
            $oName = substr($get['ctler'],strlen($get['add'].'_mdl_'));
            $model = app::get($get['add'])->model( $oName );
        } catch (Exception $e) {
            $msg = $e->getMessage();
            echo $msg;exit;
        }
        unset($get['app'],$get['ctl'],$get['act'],$get['add'],$get['ctler']);
        $render->pagedata['data'] = $get;
        
        if (method_exists($model, 'import_input')) {
            $render->pagedata['import_input'] = $model->import_input();
        }

        $render->display('common/import.html','omecsv');
    }
}
