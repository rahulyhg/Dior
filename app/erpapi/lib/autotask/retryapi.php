<?php
@include_once(dirname(__FILE__).'/../apiname.php');
class erpapi_autotask_retryapi{

    public function process($params, &$error_msg) 
    {
        $apiModel = app::get('erpapi')->model('api_fail');
        $apilog = $apiModel->dump(array('id'=>$params['id']),'status,obj_type');

        if ($apilog['status'] != 'fail') {
            $error_msg = $params['obj_bn'].'已经重试，不允许发起';
            return false;
        }
        $apiModel->update(array('status'=>'running'),array('id'=>$params['id']));

        try {
            switch ($params['method']) {
                case WMS_INORDER_CREATE:
                    // 入库
                    if ($apilog['obj_type'] == 'purchase') {
                        $iso = app::get('purchase')->model('po')->dump(array('po_bn'=>$params['obj_bn'],'check_status'=>'2','eo_status'=>'1'));

                        if (!$iso) {
                            $error_msg = $params['obj_bn'].'状态(check_status:'.$iso['check_status'].'、eo_status:'.$iso['eo_status'].')不允许发起';
                            return false;
                        }

                        kernel::single('console_event_trigger_purchase')->create(array('po_id'=>$iso['po_id']), false);
                    } else {
                        $iso  = app::get('taoguaniostockorder')->model('iso')->dump(array('iso_bn'=>$params['obj_bn'],'check_status'=>'2','iso_status'=>'1'),'iso_id,check_status,iso_status');


                        if (!$iso) {
                            $error_msg = $params['obj_bn'].'状态(check_status:'.$iso['check_status'].'、iso_status:'.$iso['iso_status'].')不允许发起';
                            return false;
                        }

                        kernel::single('console_event_trigger_otherstockin')->create(array('iso_id'=>$iso['iso_id']),false);
                    }

                    break;
                case WMS_OUTORDER_CREATE:
                    // 出库
                    if ($apilog['obj_type'] == 'purchase_return') {
                        $iso = app::get('purchase')->model('returned_purchase')->dump(array('rp_bn'=>$params['obj_bn'],'check_status'=>'2'));

                        if (!$iso) {
                            $error_msg = $params['obj_bn'].'状态(check_status:'.$iso['check_status'].'不允许发起';
                            return false;
                        }
                        kernel::single('console_event_trigger_purchasereturn')->create(array('rp_id'=>$iso['rp_id']), false);
                    } else {
                        $iso  = app::get('taoguaniostockorder')->model('iso')->dump(array('iso_bn'=>$params['obj_bn'],'check_status'=>'2','iso_status'=>'1'),'iso_id,check_status,iso_status');
                        if (!$iso) {
                            $error_msg = $params['obj_bn'].'状态(check_status:'.$iso['check_status'].'、iso_status:'.$iso['iso_status'].')不允许发起';
                            return false;
                        }

                        kernel::single('console_event_trigger_otherstockout')->create(array('iso_id'=>$iso['iso_id']),false);        
                    }


                    break;
                case WMS_SALEORDER_CREATE:
                    // 销售出库
                    $delivery = app::get('ome')->model('delivery')->dump(array('delivery_bn'=>$params['obj_bn'],'stock_status'=>'false','deliv_status'=>'false','expre_status'=>'false','pause'=>'false','process'=>'false','status'=>array('progress','ready')),'delivery_id');

                    if (!$delivery) {
                        $error_msg = $params['obj_bn'].'状态异常，不允许发起';
                        return false;
                    }

                    $original_data = kernel::single('ome_event_data_delivery')->generate($delivery['delivery_id']);
                    $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
                    $result = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, false);
                    break;
                case WMS_RETURNORDER_CREATE:
                    // 退货入库
                    $reship = app::get('ome')->model('reship')->dump(array('reship_bn'=>$params['obj_bn'],'is_check'=>1),'reship_id,is_check');
                    if (!$reship) {
                        $error_msg = $params['obj_bn'].'状态(is_check:'.$reship['is_check'].')不允许发起';
                        return false;
                    }


                    $reship_data = kernel::single('ome_receipt_reship')->reship_create(array('reship_id'=>$reship['reship_id']));
                    $wms_id = kernel::single('ome_branch')->getWmsIdById($reship_data['branch_id']);
                    kernel::single('console_event_trigger_reship')->create($wms_id, $reship_data, false);
                    break;
                default:
                    # code...
                    break;
            }
        } catch (Exception $e) {
            
        }
        
        return true;
    }
}