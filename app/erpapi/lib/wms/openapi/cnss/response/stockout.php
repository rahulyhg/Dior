<?php
/**
 * 出库单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_cnss_response_stockout extends erpapi_wms_response_stockout
{
    public function status_update($params){
        $items = isset($params['item']) ? json_decode($params['item'],true) : array();

        foreach ($items as $key => $value) {
            if ($value['product_bn']) {
                $product = app::get('ome')->model('products')->dump(array('barcode'=>$value['product_bn']),'bn');

                $items[$key]['product_bn'] = $product['bn'];
            }
        }

        $params['item'] = json_encode($items);
        
        return parent::status_update($params);
    }
}
