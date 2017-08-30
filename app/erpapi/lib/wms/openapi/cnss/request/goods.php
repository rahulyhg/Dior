<?php
/**
 * 商品分配推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_cnss_request_goods extends erpapi_wms_request_goods
{
    public function goods_add($sdf){
        $title = $this->__channelObj->wms['channel_name'].'商品添加';

        $callback = array();

        foreach (array_chunk($sdf, 100) as $sub_sdf) {
            $inner_sku = array();

            foreach ($sub_sdf as $key => $pro) {
                if (!is_array($pro)) {unset($sub_sdf[$key]); continue;}

                if ($pro['bn']) $inner_sku['inner_sku'][] = $pro['bn'];
            }
            $inner_sku['node_id'] = $this->__channelObj->wms['node_id'];

            $params = $this->_format_goods_params($sub_sdf);
            $response = $this->__caller->call(WMS_ITEM_ADD, $params, $callback, $title, 10);
            $response['data'] = json_encode($response['data']);

            if ($response) $this->goods_callback($response,$inner_sku);
        }
    }

    public function goods_update($sdf){
        $title = $this->__channelObj->wms['channel_name'] . '商品编辑';

        foreach (array_chunk($sdf, 100) as $sub_sdf) {
            $inner_sku = array();

            foreach ($sub_sdf as $key => $pro) {
                if (!is_array($pro)) {unset($sub_sdf[$key]); continue;}

                if ($pro['bn']) $inner_sku['inner_sku'][] = $pro['bn'];
            }
            $inner_sku['node_id'] = $this->__channelObj->wms['node_id'];

            $params = $this->_format_goods_params($sub_sdf);
            $response = $this->__caller->call(WMS_ITEM_UPDATE, $params, $callback, $title, 10);
            $response['data'] = json_encode($response['data']);

            if ($response) $this->goods_callback($response,$inner_sku);
        }
    }
}