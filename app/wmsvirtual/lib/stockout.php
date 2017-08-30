<?php

/**
 * 出库
 * @
 * @
 * @author cyyr24@sina.cn
 */
class wmsvirtual_stockout
{
    
    /**
     * 出库结果
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function result($result,$node_id)
    {
        $method = 'wms.stockout.status_update';
        $data = $this->format_data['result'];
        kernel::single('wmsvirtual_response')->dispatch('wms',$method,&$data,$node_id);
    }

     
    /**
     * 格式转换
     * @param  
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    protected function format_data($result)
    {
        $oForeign_sku = app::get('console')->model('foreign_sku');
        
        $data =  array(
            'stockout_bn' => $result['stockout_bn'],
            //'warehouse' => 'ILC-SH',
            'status' => $result['status'],
            'task' => time(),
            'remark' => '备注啦',
            'operate_time' => date('Y-m-d H:i:s')
        );
        if ($result['item']) {
            $items = json_decode($result['item'],true);
            foreach ($items as $k=>$item ) {
                $product_bn  = $item['product_bn'];
                $foreigh_sku = $oForeign_sku->get_product_inner_sku( $product_bn );
                $items[$k]['product_bn'] = $foreigh_sku;
            }
            $data['item'] = json_encode($items);
        }
        
        return $data;
    }
} 

?>