<?php
/**
 * 商品分配推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_ilc_request_goods extends erpapi_wms_request_goods
{

    protected function _format_goods_params($sdf)
    {
        $params = array();
        foreach ($sdf as $p){
            if (!$p) continue;

            $items[] = array(
                'name'       => $p['name'],
                'product_bn' => $p['bn'],
                'barcode'    => $p['barcode'],
                'item_code'  => $p['bn'],
            );
        }
        $params['item_lists']       = json_encode(array('item'=>$items));
        $params['uniqid']           = self::uniqid();
        $params['line_total_count'] = count($items);
        
        return $params;
    }
}