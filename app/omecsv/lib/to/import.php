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
require_once PHPEXCEL_ROOT.'/PHPExcel/CachedObjectStorageFactory.php';
require_once PHPEXCEL_ROOT.'/PHPExcel/Settings.php';
require_once PHPEXCEL_ROOT.'/PHPExcel/CachedObjectStorage/MemorySerialized.php';
class omecsv_to_import extends omecsv_prototype{

    function main(){
        @set_time_limit(600);   @ini_set('memory_limit','1024M');
        $fileName = $_FILES['import_file']['name'];
        if( !$fileName ){
            echo '<script>top.MessageBox.error("上传失败");alert("未上传文件");</script>';
            exit;
        }

        $oQueue = app::get('base')->model('queue');


        
        $this->object_name = $_GET['ctler'];
        $this->app->app_id = $_GET['add'];
        $mdl = substr($this->object_name,strlen( $this->app->app_id.'_mdl_'));
        //操作日志
        $this->doLog($mdl, array_merge($_POST, $_GET));

        if (isset($_POST['filter'])) {
            $model = app::get($this->app->app_id)->model($mdl);
            $model->import_filter = $_POST['filter'];
        }

        $pathinfo = pathinfo($fileName);
        $oIo = kernel::servicelist('omecsv_io');
        foreach( $oIo as $aIo ){        
            if( $aIo->io_type_name == $pathinfo['extension']){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);

        if( !$oImportType ){
            echo '<script>top.MessageBox.error("上传失败");alert("导入格式错误");</script>';
            exit;
        }

        try {
            # 条数限制
            $sheetInfo = $oImportType->listWorksheetInfo($_FILES['import_file']['tmp_name']);
            if ((int)$sheetInfo['totalRows'] > $oImportType->limitRow ) {
                echo '<script>top.MessageBox.error("上传失败");alert("导入数据量过大，请减至'.$oImportType->limitRow.'单以下");</script>';
                exit;
            }

            $contents = array();
            $oImportType->fgethandle($_FILES['import_file']['tmp_name'],$contents);
            $model->import_totalRows = count($contents);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            echo '<script>top.MessageBox.error("'.$msg.'");alert("'.$msg.'");</script>';exit;
        }

        $newarray = array();
        $newFileName = $this->app->app_id.'_'.$mdl.'_'.$fileName.'-'.time();
        $oo = kernel::single('omecsv_to_run_import');
        $msgList = $oo->turn_to_sdf($contents,$aaa,$newarray,array(
                    'file_type' => $pathinfo['extension'],
                    'app' => $this->app->app_id,
                    'mdl' => $mdl,
                    'file_name' => $newFileName
                ));
        $msg = array();


        if( $msgList['error'] )
            $rs = array('failure',$msgList['error']);
        else
            $rs = array('success',$msgList['warning']);


        $echoMsg = '';
        if( $rs[0] == 'success' ){
			$o = app::get( $this->app->app_id )->model($mdl);
			$oImportType->model = $o;
			$oImportType->finish_import();

            $echoMsg =app::get('desktop')->_('上传成功 已加入队列 系统会自动跑完队列');
            if($msgList['warning']){
                $echoMsg .= app::get('desktop')->_('但是存在以下问题')."\\n";
                $echoMsg .= implode("\\n",$msgList['warning']);
            }
        }else{
            $echoMsg = app::get('desktop')->_('导入失败 错误如下：'."\\n".implode("\\n",$msgList['error']));
        }

        header("content-type:text/html; charset=utf-8");
        echo "<script>parent.MessageBox.success(\"上传成功\");alert(\"".$echoMsg."\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
    }
    
    /**
     * 操作日志
     * @param Obj $model 数据模型
     * @param Array $params 参数
     */
    function doLog($modelName, $params) {
        $model = app::get($this->app->app_id)->model($modelName);
        $logParams = array(
            'app' => trim($params['app']),
            'ctl' => trim($params['ctl']),
            'act' => trim($params['act']),
            'modelFullName' => $params['ctler'],
            'type' => 'import',
        );
        unset($params['app']);
        unset($params['ctl']);
        unset($params['act']);
        unset($params['ctler']);
        if (isset($params['_finder'])) {
            unset($params['_finder']);
        }
        if (isset($params['finder_id'])) {
            unset($params['finder_id']);
        }
        $logParams['params'] = $params;
//        echo "<pre>";
//        print_r($logParams);exit;
        //是否记录日志
        if ($model->isDoLog()) {
            $type = $model->getLogType($logParams);
            //ome应用是否已经安装
            if (app::get('ome')->is_installed()) {
                ome_operation_log::insert($type, $logParams);
            }
        }
    }

}
