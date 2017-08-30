<?php
/**
 * 出库单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_stockout extends erpapi_wms_response_abstract
{    
    /**
     * wms.stockout.status_update
     *
     **/
    public function status_update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'出库单'.$params['stockout_bn'];
        $this->__apilog['original_bn'] = $params['stockout_bn'];

        $data = array(
           'io_bn'           => $params['stockout_bn'],  
           'branch_bn'       => $params['warehouse'],
           'io_status'       => $params['status'] ? $params['status'] : $params['io_status'],
           'memo'            => $params['remark'],
           'operate_time'    => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
           'logi_no'         => $params['logi_no'],
           'out_delivery_bn' => $params['out_delivery_bn'],
           'logi_id'         => $params['logistics'],
           'wms_id'         => $this->__channelObj->wms['channel_id'],
        );

        switch(substr($data['io_bn'], 0, 1)){
          case 'H': $data['io_type'] = 'PURCHASE_RETURN'; break;
          case 'R': $data['io_type'] = 'ALLCOATE';break;
          case 'B': $data['io_type'] = 'DEFECTIVE';break;
          case 'U':
          default:
            $data['io_type'] = 'OTHER';break;
          
        }

        $stockout_items = array();
        $items = isset($params['item']) ? json_decode($params['item'], true) : array();
        if($items){
          foreach($items as $key=>$val){
              if (!$val['product_bn'])  continue;
              
              $stockout_items[$val['product_bn']]['bn'] =  $val['product_bn'];
              $stockout_items[$val['product_bn']]['num'] =  (int)$stockout_items[$val['product_bn']]['num'] + (int)$val['num'];
          }
        }

        $data['items'] = $stockout_items;
        return $data;
    }
}
