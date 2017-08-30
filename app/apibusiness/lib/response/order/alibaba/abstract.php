    <?php
/**
* alibaba(阿里巴巴平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/alibaba
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_alibaba_abstract extends apibusiness_response_order_abstractbase
{
    /**
     * 是否接收订单
     *
     * @return void
     * @author
     **/
    protected function canAccept()
    {
        $result = parent::canAccept();
        if ($result === false) {
            return false;
        }

        # 未支付的款到发货订单拒收
        if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }

        return true;
    }

    /**
     * 能够创建订单
     *
     * @return void
     * @author 
     **/
    public function canCreate()
    {

        if ($this->_ordersdf['status'] != 'active') {
            $this->_apiLog['info']['msg'] = '取消的订单不接收';
            return false;
        }     

        return parent::canCreate();
    }

    public function postCreate()
    {
        parent::postCreate();

        // 保存卖家登录名
        if ($this->_newOrder['order_id']) {
            $data = array(
                'order_id' => $this->_newOrder['order_id'],
                'sellermemberid' => $this->_ordersdf['sellermemberid'],
            );
            $orderExtendModel = app::get(self::_APP_NAME)->model('order_extend');
            $orderExtendModel->save($data);
        }
    }

    /**
     * 需要更新的组件
     *
     * @return void
     * @author
     **/
    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype','consignee');

        return $components;
    }

    /**
     * 对平台接收的数据纠正(有些是前端打的不对的)
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        parent::reTransSdf();

        if( !$this->_ordersdf['payinfo']['pay_name'] ) $this->_ordersdf['payinfo']['pay_name'] = '支付宝担保交易';

        if($this->_ordersdf['shipping']['is_cod'] == 'true'){
            unset($this->_ordersdf['payments'],$this->_ordersdf['payment_detail']);
            $this->_ordersdf['pay_bn'] = 'online';
            $this->_ordersdf['payinfo']['pay_name'] = '货到付款';
            $this->_ordersdf['pay_status'] = '0';
        }

        // 获取货号
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object) {
            if($object['sub_order_bn']){
                $this->_ordersdf['order_objects'][$objkey]['oid'] = is_numeric($object['sub_order_bn']) ? number_format($object['sub_order_bn'],0,'','') : $object['sub_order_bn'];
            } else{
                $this->_ordersdf['order_objects'][$objkey]['oid'] = is_numeric($object['oid']) ? number_format($object['oid'],0,'','') : $object['oid'];
            }
            

            $goods = $this->item_get($object['bn']);
 
            $this->_ordersdf['order_objects'][$objkey]['shop_goods_id'] = $object['bn'];
            $this->_ordersdf['order_objects'][$objkey]['bn'] = $goods['bn'];
            foreach ($object['order_items'] as $itemkey => $item) {
                $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['shop_goods_id'] = $object['bn'];
                $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['shop_product_id'] = $item['specId'];
                $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['bn'] = isset($goods['skus']) ? $goods['skus'][$item['specId']]['bn'] : $goods['bn'];
                $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['product_attr'] = isset($goods['skus']) ? $goods['skus'][$item['specId']]['product_attr'] : '';

                $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['pmt_price'] = bcsub($item['amount'], $item['sale_price'],3);
            }
        }
    }

    /**
     * 获取货品
     *
     * @param String $num_iid 商品ID
     * @return void
     * @author 
     **/
    protected function item_get($num_iid)
    {
        static $goods;

        if ($goods[$num_iid]) {
            return $goods[$num_iid];
        }

        $rs = kernel::single('apibusiness_router_request')->setShopId($this->_shop['shop_id'])->item_get($num_iid,$this->_shop['shop_id']);
        if ($rs->rsp == 'fail' || !$rs->data ){
            $this->_apiLog['info'][] = '获取商品('.$rs->msg_id.')失败：' . $num_iid;
            return array();
        }

        $data = json_decode($rs->data,true);unset($data['toReturn'][0]['details']);
        $this->_apiLog['info'][] = '获取商品('.$rs->msg_id.')：' . $num_iid;

        if ($rs->rsp == 'succ' && $data) {
            $item = $data;unset($data);
            $item = $item['toReturn'][0];

            $feature = array();
            foreach ((array) $item['productFeatureList'] as $value) {
                if ($value['name'] == '货号') {
                    $goods[$num_iid]['bn'] = $value['value'];
                }
                $feature[$value['fid']] = $value;
            }

            if ($item['isSkuOffer'] == true) {
                foreach ((array) $item['skuArray'] as $v1) {
                    if ($v1['children']) {
                        foreach ((array) $v1['children'] as $v2) {
                            $goods[$num_iid]['skus'][$v2['specId']]['bn'] = $v2['cargoNumber'];
                            $goods[$num_iid]['skus'][$v2['specId']]['product_attr'] = array(
                                0 => array('value' => $v1['value'], 'label' => $feature[$v1['fid']]['name']),
                                1 => array('value' => $v2['value'], 'label' => $feature[$v2['fid']]['name'] ),
                            );
                        }
                    } else {
                        $goods[$num_iid]['skus'][$v1['specId']]['bn'] = $v1['cargoNumber'];
                        $goods[$num_iid]['skus'][$v1['specId']]['product_attr'] = array(
                            0 => array( 'value' => $v1['value'], 'label' => $feature[$v1['fid']]['name']),
                        );
                    }
                }
            }
        }

        return $goods[$num_iid];
    }

}