<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class omecsv_ctl_admin_export extends omecsv_prototype{

    function main(){
        $render = kernel::single('desktop_controller');
        $get = kernel::single('base_component_request')->get_get();
        unset($get['app'],$get['ctl'],$get['act']);

        $render->pagedata['data'] = $get;

        $ioType = array();
        foreach( kernel::servicelist('omecsv_io') as $aIo ){        
            $ioType[$aIo->io_type_name] = '.'.$aIo->io_type_name;
        }

        if (!$get['ctler'] || !$get['add']) {
            echo 'PRAMS ERROR!!!';exit;
        }

        try {
            $oName = substr($get['ctler'],strlen($get['add'].'_mdl_'));
            $model = app::get($get['add'])->model( $oName );
            $this->doLog($model, array_merge($_POST, $_GET));
        } catch (Exception $e) {
            $msg = $e->getMessage();
            echo $msg;exit;
        }


        if (method_exists($model, 'export_input')) {
            $render->pagedata['export_input'] = $model->export_input();
        }

        if (method_exists($model, 'support_io')) {
            $model->support_io($ioType);
        }

        $render->pagedata['ioType'] = $ioType;
        
        # 调用自身模板
        $render->display('common/export.html','omecsv');
    }
    /**
     * 操作日志
     * @param Obj $model 数据模型
     * @param Array $params 参数
     */
    function doLog($model, $params) {
        $logParams = array(
            'app' => trim($params['app']),
            'ctl' => trim($params['ctl']),
            'act' => trim($params['act']),
            'modelFullName' => $params['ctler'],
            'type' => 'export',
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
