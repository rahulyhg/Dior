<?php
/**
 * 商品分配推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_ftp_request_goods extends erpapi_wms_request_goods
{

    /**
     * 商品添加
     * @param Array $sdf = array (
     *                      0 =>
     *                         array (
     *                           'bn' => 'test',
     *                           'name' => '测试商品',
     *                           'product_id' => '1',
     *                           'barcode' => 'test',
     *                           'price' => '199.000',
     *                           'weight' => '10.000',
     *                           'property' => NULL,
     *                           'brand' => NULL,
     *                           'goods_cat' => '通用商品类型',
     *                        ),
     *                       )
     * @return void
     * @author 
     **/
    public function goods_add($sdf){
        $title = $this->__channelObj->wms['channel_name'].'商品添加';

        $params = array('items'=>array());
        foreach ((array) $sdf as $key => $value) {
            $params['items'][$value['product_id']] = array(
                'product_name' => $value['name'],
                'product_bn'   => $value['bn'],
                'barcode'      => $value['barcode'],
            );
        }

        if ($params['items']) {
            $foreignskus = app::get('console')->model('foreign_sku')->getList('inner_product_id',array('inner_product_id'=>array_keys($params['items']), 'wms_id'=>$this->__channelObj->wms['channel_id'], 'sync_status'=>'3'));

            // 过滤掉已经同步的
            foreach ($foreignskus as $key => $value) {
                unset($params['items'][$value['inner_product_id']]);
            }
        }
        sort($params['items']);

        $params['items'] = json_encode($params['items']);

        $rs = $this->__caller->call(WMS_ITEM_ADD, $params, null, $title);

        // 标记同步状态
        if ($rs['rsp'] == 'success') {
            $items = json_decode($params['items'],true); $sync_sku_data = array();
            foreach($items as $value){
                $sync_sku_data[] = array(
                    'inner_sku' => $value['product_bn'],
                    'outer_sku' => '',
                    'status'    => '3',
                );
            }

            if ($sync_sku_data) kernel::single('console_foreignsku')->set_sync_status($sync_sku_data,$this->__channelObj->wms['node_id']);
        }

        return $rs;
    }

    /**
     * 商品编辑
     *
     * @return void
     * @author 
     **/
    public function goods_update($sdf){
        $title = $this->__channelObj->wms['channel_name'].'商品更新';

        $params = array('items'=>array());
        foreach ($sdf as $key => $value) {
            $params['items'][$value['product_id']] = array(
                'product_name' => $value['name'],
                'product_bn'   => $value['bn'],
                'barcode'      => $value['barcode'],
            );
        }

        if ($params['items']) {
            $foreignskus = app::get('console')->model('foreign_sku')->getList('inner_product_id',array('inner_product_id'=>array_keys($params['items']), 'wms_id'=>$this->__channelObj->wms['channel_id'], 'sync_status'=>'3'));

            // 过滤掉已经同步的
            foreach ($foreignskus as $key => $value) {
                unset($params['items'][$value['inner_product_id']]);
            }
        }
        sort($params['items']);

        $params['items'] = json_encode($params['items']);

        $rs = $this->__caller->call(WMS_ITEM_UPDATE, $params, null, $title);

        // 标记同步状态
        if ($rs['rsp'] == 'success') {
            $items = json_decode($params['items'],true); $sync_sku_data = array();
            foreach($items as $value){
                $sync_sku_data[] = array(
                    'inner_sku' => $value['product_bn'],
                    'outer_sku' => '',
                    'status'    => '3',
                );
            }

            if ($sync_sku_data) kernel::single('console_foreignsku')->set_sync_status($sync_sku_data,$this->__channelObj->wms['node_id']);
        }

        return $rs;
    }

}