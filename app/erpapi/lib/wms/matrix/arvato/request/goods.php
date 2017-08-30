<?php
/**
 * 商品分配推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_arvato_request_goods extends erpapi_wms_request_goods
{

    protected function _format_goods_params($sdf)
    {

        $params = $items = array();
        if ($sdf){
            foreach ($sdf as $p){
                if (!$p) continue;

                $spec_info = preg_replace(array('/：/','/、/'),array(':',';'),$p['property']);
                $items[] = array(
                    'name'                => $p['name'],
                    'title'               => $p['name'],// 商品标题
                    'item_code'           => $p['bn'],
                    'remark'              => '',//商品备注
                    'type'                => 'NORMAL',
                    'is_sku'              => '1',
                    'gross_weight'        => $p['weight'] ? $p['weight'] : '0.00',// 毛重,单位G
                    'net_weight'          => $p['weight'] ? $p['weight'] : '0.00',// 商品净重,单位G
                    'tare_weight'         => '0.00',// 商品皮重，单位G
                    'is_friable'          => '否',// 是否易碎品
                    'is_dangerous'        => '否',// 是否危险品
                    //'weight'            => $p['weight'] ? $p['weight'] : '0',
                    //'length'            => '0.00',// 商品长度，单位厘米
                    //'width'             => '0.00',// 商品宽度，单位厘米
                    //'height'            => '0.00',// 商品高度，单位厘米
                    //'volume'            => '0.00',// 商品体积，单位立方厘米
                    'pricing_cat'         => '',// 计价货类
                    'package_material'    => '',// 商品包装材料类型
                    'price'               => '0.00',
                    'support_batch'       => '否',
                    'support_expire_date' => '0',
                    'expire_date'         => date('Y-m-d'),
                    'support_barcode'     => '00',
                    'barcode'             => $p['barcode'] ? $p['barcode'] : '',
                    'support_antifake'    => '0',
                    'unit'                => $p['unit'] ? $p['unit'] : '',
                    'package_spec'        => '',// 商品包装规格
                    'ename'               => '',// 商品英文名称
                    'brand'               => '',
                    'batch_no'            => '',
                    'goods_cat'           => '0',// 商品分类
                    'color'               => '',// 商品颜色
                    'property'            => $spec_info,//规格
                );
            }
        }

        $params['item_lists'] = json_encode(array('item'=>$items));
        $params['uniqid'] = self::uniqid();
        return $params;
    }
}