<?php
/**
 * 退货单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_reship extends erpapi_wms_response_abstract
{    
    /**
     * wms.reship.status_update
     *
     **/
    public function status_update($params){

        $this->__apilog['title'] = $this->__channelObj->wms['channel_name'] . '退货单' . $params['reship_bn'];
        $this->__apilog['original_bn'] = $params['reship_bn'];

      
      $data = array(
        'reship_bn'    => trim($params['reship_bn']),
        'logi_code'    => $params['logistics'],
        'logi_no'      => $params['logi_no'],
        'branch_bn'    => $params['warehouse'],
        'memo'         => $params['remark'],
        'operate_time' => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
        'wms_id'       => $this->__channelObj->wms['channel_id'],
      );
      $params['status'] = $params['status'] ? $params['status'] : $params['io_status'];
      switch($params['status']){
        case 'FINISH': $data['status']='FINISH';break;
        case 'PARTIN': $data['status']='PARTIN';break;
        case 'CLOSE':
        case 'FAILED':
        case 'DENY':
          $data['status'] = 'CLOSE'; break;
        default:
          $data['status'] = $params['status'];break;
      }

      $reship_items = array();
      $items = isset($params['item']) ? json_decode($params['item'],true) : array();
      if($items){
          foreach($items as $key=>$val){
            if (!$val['product_bn']) continue;

            $reship_items[$val['product_bn']]['bn']            = $val['product_bn'];
            $reship_items[$val['product_bn']]['normal_num']    = (int)$reship_items[$val['product_bn']]['normal_num'] + (int)$val['normal_num'];
            $reship_items[$val['product_bn']]['defective_num'] = (int)$reship_items[$val['product_bn']]['defective_num'] + (int)$val['defective_num'];
          }
      }
      
      $data['items'] = $reship_items;
      return $data;
    }
}
