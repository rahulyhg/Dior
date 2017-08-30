<?php
/**
* 库存对账
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class erpapi_wms_openapi_cnss_response_stock extends erpapi_wms_response_stock
{
    public function quantity($params){
        $items = isset($params['item']) ? json_decode($params['item'],true) : array();

        foreach ($items as $key => $value) {
            if ($value['product_bn']) {
                $product = app::get('ome')->model('products')->dump(array('barcode'=>$value['product_bn']),'bn');

                $items[$key]['product_bn'] = $product['bn'];
            }
        }

        $params['item'] = json_encode($items);

        return parent::quantity($params);
    }
}
