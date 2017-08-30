<?php
/**
 * 出库单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_qimen_response_stockout extends erpapi_wms_response_stockout
{
    public function status_update($params){

        $data = parent::status_update($params);

        $stockout_items = array();
        $items = isset($params['item']) ? json_decode($params['item'], true) : array();
        if($items){
          foreach($items as $key=>$val){
              if (!$val['product_bn'])  continue;
              
              $stockout_items[$val['product_bn']]['bn'] =  $val['product_bn'];
              $stockout_items[$val['product_bn']]['num'] =  (int)$stockout_items[$val['product_bn']]['num'] + (int)$val['normal_num'];
          }
        }

        $data['items'] = $stockout_items;
        return $data;
    }
}
