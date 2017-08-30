<?php

/**
 *入库
 * @
 * @
 * @author cyyr24@sina.cn
 */
class wmsvirtual_stockin
{
    function result($result,$node_id){
        $method = 'wms.stockin.status_update';
        $data = $this->format_data($result);
        print_r($data);
        kernel::single('wmsvirtual_response')->dispatch('wms',$method,$data,$node_id);
        
    }
    
    
    /**
     * 格式化入库数据
     * @param   
     * @return 
     * @access  public
     * @author cyyr24@sina.cn
     */
    protected function format_data($result){
        
        $data = array(
            'stockin_bn'=>$result['stockin_bn'],
            'warehouse'=>'ILC-SH',
            'status'=>$result['status'],
            'remark'=>'',
            'operate_time'=>date('Y-m-d'),
         );
        $oForeign_sku = app::get('console')->model('foreign_sku');
        if ($result['item']) {
            
            $items = json_decode($result['item'],true);
            
            if ($items) {
            
                foreach($items as $k=>$item ) {
                    $product_bn  = $item['product_bn'];
                    $foreigh_sku = $oForeign_sku->get_product_inner_sku( $product_bn );

                    $items[$k]['product_bn'] = $foreigh_sku;
                }

                $data['item'] = json_encode($items);
            }
        }
        
        return $data;
    }
} 

?>