<?php
/**
 * 商品分配推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_360buy_request_goods extends erpapi_wms_request_goods
{
    /**
     * 单个同步
     *
     * @return void
     * @author 
     **/
    public function goods_add($sdf){
        $title = $this->__channelObj->wms['channel_name'].'商品添加';

        $callback = array(
            'class'  => get_class($this),
            'method' => 'goods_callback',
            'params' => array('node_id'=>$this->__channelObj->wms['node_id']),
        );

        foreach ($sdf as $good) {
            if (!$good) continue;

            $params = $this->_format_goods_params($good);

            $callback['params']['inner_sku'] = array($good['bn']);

            $this->__caller->call(WMS_ITEM_ADD, $params, $callback, $title, 10, $good['bn']);
        }
    }

    /**
     * 单个更新
     *
     * @return void
     * @author 
     **/
    public function goods_update($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'商品更新';

        $callback = array(
            'class'  => get_class($this),
            'method' => 'goods_callback',
            'params' => array('node_id'=>$this->__channelObj->wms['node_id']),
        );

        foreach ($sdf as $good) {
            if (!$good) continue;

            $params = $this->_format_goods_params($good);

            $callback['params']['inner_sku'] = array($good['bn']);

            $this->__caller->call(WMS_ITEM_UPDATE, $params, $callback, $title, 10, $good['bn']);
        }
    }

    protected function _format_goods_params($p)
    {
        $params = $items = array();
        $spec_info = preg_replace(array('/：/','/、/'),array(':',';'),$p['property']);

        $items[] = array(
            'name'                => $p['name'],
            'title'               => $p['name'],// 商品标题
            'item_code'           => $p['bn'],
            'remark'              => '',//商品备注
            'type'                => 'NORMAL',
            'is_sku'              => '1',
            'gross_weight'        => $p['weight'] ? $p['weight'] : '',// 毛重,单位G
            'net_weight'          => $p['weight'] ? $p['weight'] : '',// 商品净重,单位G
            'tare_weight'         => '',// 商品皮重，单位G
            'is_friable'          => '',// 是否易碎品
            'is_dangerous'        => '',// 是否危险品
            
            'pricing_cat'         => '',// 计价货类
            'package_material'    => '',// 商品包装材料类型
            'price'               => '',
            'support_batch'       => '否',
            'support_expire_date' => '0',
            'expire_date'         => date('Y-m-d'),
            'support_barcode'     => '0',
            'barcode'             => $p['barcode'] ? $p['barcode'] : '',
            'support_antifake'    => '否',
            'unit'                => $p['unit'] ? $p['unit'] : '',
            'package_spec'        => '',// 商品包装规格
            'ename'               => '',// 商品英文名称
            'brand'               => '',
            'batch_no'            => '',
            'goods_cat'           => '1',// 商品分类
            'goods_cat_name'      =>'分类',
            'color'               => '',// 商品颜色
            'property'            => $spec_info,//规格
            'length'              =>'33',
            'width'               =>'4',
            'height'              =>'5',
            'volume'              =>'6',
            'size_definition'     =>'2',
        );

        $params['item_lists'] = json_encode(array('item'=>$items));
        $params['uniqid'] = self::uniqid();

        return $params;
    }
}