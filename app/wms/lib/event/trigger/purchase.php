<?php
class wms_event_trigger_purchase extends wms_event_trigger_stockinabstract{

    
    /**
     * 采购入库数据
     * @param   type    $data    需要转化数据
     * @return  type    array 
     * @access  public
     * @author
     */
    function getStockInData($data)
    {
        $po_id = $data['po_id'];
        $Po = &app::get('purchase')->model("po")->dump($po_id,'eo_status,po_status,po_bn,supplier_id,branch_id');
        $oPo_items = &app::get('purchase')->model("po_items");
        $tmp['io_type'] = 'PURCHASE';//类型
        $tmp['io_bn'] = $Po['po_bn'];//单号
     
        $tmp['status'] = $Po['po_status'];//采购状态
        $tmp['io_source'] = 'selfwms';//来源
        $tmp['memo'] = '';//备注
        $tmp['branch_id'] = $Po['branch_id'];
        $tmp['supplier_id'] = $Po['supplier_id'];
       
        $Po_items = array();
        foreach($data['ids'] as $i){
            $v = intval($data['entry_num'][$i]);
            $k = $i;
            $items = $oPo_items->dump($k,'bn');
           
            #$amount+=$v*$Po_items['price'];
            $item_memo = $data['item_memo'][$k];
            $Po_items[]=array(
               'normal_num'=>$v,
               'item_memo'=>addslashes($item_memo),
               'bn'=>$items['bn'],
             );
        }
        $tmp['items'] = $Po_items;
        return $tmp;
    } 

}

?>
