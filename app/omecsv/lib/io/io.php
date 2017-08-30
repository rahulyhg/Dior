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
class omecsv_io_io{

    var $charset = null;
    var $limitRow = 2000;

    public function __construct(){
        if(!setlocale(LC_ALL, 'zh_CN.gbk')){
            setlocale(LC_ALL, "chs");
        }
        $this->charset = kernel::single('base_charset');

        $this->objPHPExcel = new PHPExcel();
    }

    public function getTitle(&$cols){
        $title = array();
        foreach( $cols as $col => $val ){
            if( !$val['deny_export'] )
                $title[$col] = $val['label'].'('.$col.')';
        }
        return $title;
    }

    public function init( &$model ){
        $model->charset = $this->charset;
        $model->io = $this;
        $this->model->$model;
    }

    /**
     * @param String $inputFileName 导入文件名
     * @param Array $contents 数量
     * @return Void
     **/
    public function fgethandle($inputFileName,&$contents){
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
        $cacheSettings = array();  
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings);

        $classType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($classType);
        if (method_exists($objReader, 'setReadDataOnly')) {
            $objReader->setReadDataOnly(true);
        }

        $objPHPExcel = $objReader->load($inputFileName);
        $contents = $objPHPExcel->setActiveSheetIndex()->toArray(null,false,false,false);
    }

    /**
     * sheet 基本信息
     *
     * @return void
     * @author 
     **/
    public function listWorksheetInfo($inputFileName)
    {
        $classType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($classType);

        $sheetInfo = $objReader->listWorksheetInfo($inputFileName);

        return $sheetInfo;
    }

    public function prepared_import( $appId,$mdl ){
        $this->model = &app::get($appId)->model($mdl);
        $this->model->ioObj = $this;
        if( method_exists( $this->model,'prepared_import_csv' ) ){
            $this->model->prepared_import_csv();
        }
        return;
    }

    public function finish_import(){
        if( method_exists( $this->model,'finish_import_csv' ) ){
            $this->model->finish_import_csv();
        }
    }

}
