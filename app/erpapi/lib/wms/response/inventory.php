<?php
/**
 * 盘点
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_inventory extends erpapi_wms_response_abstract
{
    /**
     * wms.inventory.add
     *
     **/
    public function add($params){
      // 参数校验
      $this->__apilog['title']       = $this->__channelObj->wms['channel_name'] . '盘点' . $params['inventory_bn']; 
      $this->__apilog['original_bn'] = $params['inventory_bn'];

      $data = array(
        'inventory_bn' => trim($params['inventory_bn']),
        'branch_bn'    => $params['warehouse'],
        'memo'         => $params['remark'],
        'operate_time' => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
        'wms_id'       => $this->__channelObj->wms['channel_id'],
      );

      $inventory_items = array();
      $items = $params['item'] ? json_decode($params['item'],true) : array();

      if ($items) {
        foreach ($items as $key => $val) {
          if (!$val['product_bn']) continue;

          $inventory_items[$val['product_bn']]['bn'] = $val['product_bn'];
          $inventory_items[$val['product_bn']]['normal_num'] = (int)$inventory_items[$val['product_bn']]['normal_num'] + (int)$val['normal_num'];
          $inventory_items[$val['product_bn']]['defective_num'] = (int)$inventory_items[$val['product_bn']]['defective_num'] + (int)$val['defective_num'];
        }
      }

      $data['items'] = $inventory_items;
      return $data;
    }
}
