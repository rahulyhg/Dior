<?php
/**
 * 入库单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_stockin extends erpapi_wms_response_abstract
{
    /**
     * wms.stockin.status_update
     *
     **/
    public function status_update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'入库单'.$params['stockin_bn'];
        $this->__apilog['original_bn'] = $params['stockin_bn'];

        // 如果是MS打头代表退货入库
        $data = array(
          'io_bn'        => $params['stockin_bn'],
          'branch_bn'    => $params['warehouse'],
          'io_status'    => $params['status'] ? $params['status'] : $params['io_status'],
          'memo'         => $params['remark'],
          'operate_time' => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
          'wms_id'       => $this->__channelObj->wms['channel_id'],
        );

        switch(substr($data['io_bn'], 0, 1)){
          case 'I': $data['io_type'] = 'PURCHASE';break; // 采购入库
          case 'T': $data['io_type'] = 'ALLCOATE';break; // 调拨入库
          case 'D': $data['io_type'] = 'DEFECTIVE';break; // 残损入库
          case 'O': 
          default:
            $data['io_type'] = 'OTHER';break;
        }

        $stockin_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],true) : array();

        if($items){
          foreach($items as $key=>$val){
            if (!$val['product_bn'])  continue;
            
            $stockin_items[$val['product_bn']]['bn']            = $val['product_bn'];
            $stockin_items[$val['product_bn']]['normal_num']    = (int) $stockin_items[$val['product_bn']]['normal_num'] + (int) $val['normal_num'];
            $stockin_items[$val['product_bn']]['defective_num'] = (int) $stockin_items[$val['product_bn']]['defective_num'] + (int) $val['defective_num'];
          }
        }

        $data['items'] = $stockin_items;

        return $data;
    }
}
