<?php
/**
 * 转储单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_cnss_request_stockdump extends erpapi_wms_request_stockdump
{
    protected function _format_stockdump_create_params($sdf)
    {
        $params = parent::_format_stockdump_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'barcode');

                $items['item'][] = array(
                    'item_code'     => $product['barcode'],
                    'item_name'     => $v['name'],
                    'item_quantity' => $v['num'],
                    'item_price'    => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num' => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'item_remark'   => '',// TODO: 商品备注
                );
            }
        }

        $params['items'] = json_encode($items);
        return $params;   
    }
}