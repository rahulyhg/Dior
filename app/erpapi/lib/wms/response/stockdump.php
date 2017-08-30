<?php
/**
 * 转储单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_stockdump extends erpapi_wms_response_abstract
{    
    /**
     * wms.stockdump.status_update
     *
     **/
    public function status_update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'转储单'.$params['stockdump_bn'];
        $this->__apilog['original_bn'] = $params['stockdump_bn'];


      $data = array(
          'stockdump_bn' => $params['stockdump_bn'],
          'branch_bn'    => $params['warehouse'],
          'status'       => $params['status'] ? $params['status'] : $params['io_status'],
          'memo'         => $params['remark'],
          'operate_time' =>isset($params['operate_time']) ? $params['operate_time'] : date('Y-d-m H:i:s'),
          'wms_id'       => $this->__channelObj->wms['channel_id'],
      );


      $stockdump_items = array();
      $items = isset($params['item']) ? json_decode($params['item'],true) : array();
       
      if($items){
        foreach($items as $key=>$val){
          if(!$val['product_bn'])  continue;

          $stockdump_items[] = array(
              'bn' => $val['product_bn'],
              'num'=> $val['num'],
          );
        }  
      }

      $data['items'] = $stockdump_items;
      return $data;
    }
}
