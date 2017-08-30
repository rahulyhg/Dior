<?php
/**
* 库存对账
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class erpapi_wms_response_stock extends erpapi_wms_response_abstract
{    
    /**
     * wms.stock.status_update
     *
     **/
    public function quantity($params){

      
      $data = array(
        'operate_time' => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
        'wms_id'       => $this->__channelObj->wms['channel_id'],
      );

      $stock_items = array();
      $items = $params['item'] ? json_decode($params['item'], true) : array();

      if($items){
        $data['batch'] = $items[0]['batch'];

        foreach($items as $key=>$val)  {
          $stock_items[] = array(
            'branch_bn'     => $val['warehouse'],
            'logi_code'     => $val['logistics'],
            'product_bn'    => $val['product_bn'],
            'normal_num'    => $val['normal_num'],
            'defective_num' => $val['defective_num'],
          
          );
        }
      }
     
     $this->__apilog['title']       = $this->__channelObj->wms['channel_name'] . '库存对帐' . $data['batch'];   
     $this->__apilog['original_bn'] = $data['batch'];

      $data['items'] = $stock_items;
      return $data;        
    }
}
