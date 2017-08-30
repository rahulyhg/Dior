<?php
/**
* 库存对账
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class erpapi_wms_matrix_ilc_response_stock extends erpapi_wms_response_stock
{

    public function quantity($params){
        $params = parent::quantity($params);

        if ($params['items']){
            foreach ((array) $params['items'] as $key=>$item){
                $barcode = $item['bn'] ? $item['bn'] : $item['product_bn'];

                // 条码转货号
                if ($barcode) {
                    $product = app::get('ome')->model('products')->dump(array('barcode'=>$barcode),'bn');
                    $params['items'][$key]['bn'] = $params['items'][$key]['product_bn'] = $product['bn'] ? $product['bn'] : $barcode;    
                }
            }
        }
        return $params;
    }
}
