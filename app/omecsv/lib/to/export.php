<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
define('PHPEXCEL_ROOT', ROOT_DIR.'/app/omecsv/lib/static');
require_once PHPEXCEL_ROOT.'/PHPExcel.php';
require_once PHPEXCEL_ROOT.'/PHPExcel/IOFactory.php';
class omecsv_to_export extends omecsv_prototype{

    function main(){
        $post = kernel::single('base_component_request')->get_post();

        foreach( kernel::servicelist('omecsv_io') as $aIo ){
            if( $aIo->io_type_name == $post['_io_type'] ){
                $oImportType = $aIo;
                break;
            }
        }

        $oName = substr($post['ctler'],strlen($post['add'].'_mdl_'));
        $model = app::get($post['add'])->model( $oName );

        $model->filter_use_like = true;
        $oImportType->init($model);

        $offset = 0;
        $data = array();
        $filename = $oName;
        if (method_exists($model,'exportName')) {
            $model->exportName($filename,$post['filter']);
        }
        
        //后台导出service
        $obj_services = kernel::servicelist('desktop_background_export');
        if($obj_services){
            foreach($obj_services as $service){
                if(method_exists($service, 'doBackgroundExport')){
                    if($service->doBackgroundExport($post['add'],$oName,$post['filter'])){
                        return true;
                    }
                }
            }
        }
      

        $oImportType->export_header( $filename );
    
        $pRow = 1;
        while( $listFlag = $model->fgetlist($data,$post['filter'],$offset,$post['_export_type']) ){
            $offset++; $k = 0;
            # 暂时支持两层
            foreach ($data as $layer => $contents) {
                if($k>1) break;
                foreach ($contents as $content) {
                    $oImportType->export( $content,$pRow,$model,$post['_export_type']);

                    $pRow++;
                }
                
                if($post['filter']['template'] == 1) $pRow++;

                $k++;
            }
        }
        $oImportType->finish_export();
        
    }

}
