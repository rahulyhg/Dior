<?php
/**
 * 盘点
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_cnss_response_inventory extends erpapi_wms_response_inventory
{
    public function add($params){
        $items = isset($params['item']) ? json_decode($params['item'],true) : array();

        foreach ($items as $key => $value) {
            if ($value['product_bn']) {
                $product = app::get('ome')->model('products')->dump(array('barcode'=>$value['product_bn']),'bn');

                $items[$key]['product_bn'] = $product['bn'];
            }
        }

        $params['item'] = json_encode($items);

        return parent::add($params);
    }
}
