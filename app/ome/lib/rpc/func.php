<?php
/**
* OME API接口函数
* @copyright shopex.cn 2011.7.15
* @author Mr.dong
*/
class ome_rpc_func{


    /**
    * 获取app xml信息
    * @access public
    * @param String $app 名称
    * @return Array xml信息
    */
    function app_xml($app='ome'){
        if (empty($app)) return NULL;
        
        return app::get($app)->define();
    }

    /**
    * 存储前端店铺的版本号
    * @access public
    * @param String $api_v 前端店铺API版本号
    * @param String $node_id 前端店铺节点号
    * @return bool
    */
    function store_shop_api_v($api_v,$node_id=''){
        if (empty($node_id) || empty($api_v)) return false;

        base_kvstore::instance('setting_ome')->fetch('api_v_'.$node_id,$his_api_v);

        if (version_compare($api_v,$his_api_v,'!=')){
            #更新数据库
            $oShop = &app::get('ome')->model('shop');
            $oShop->update(array('api_version'=>$api_v),array('node_id'=>$node_id));
            #更新KV
            base_kvstore::instance('setting_ome')->store('api_v_'.$node_id,$api_v);
        }
        return true;
    }

    /**
    * 读取前端店铺的版本号
    * @access public
    * @param String $node_id 前端店铺节点号
    * @return String 前端店铺API版本号
    */
    function fetch_shop_api_v($node_id=''){
        if (empty($node_id)) return NULL;

        base_kvstore::instance('setting_ome')->fetch('api_v_'.$node_id,$api_version);
                   
        if (empty($api_version)){
            #读取数据库
            $oShop = &app::get('ome')->model('shop');
            $shop_detail = $oShop->getList('api_version',array('node_id'=>$node_id),0,1);
            $api_version = $shop_detail[0]['api_version'];
            if (!empty($api_version)){
                #更新KV
                base_kvstore::instance('setting_ome')->store('api_v_'.$node_id,$api_version);
            }
        }
        return $api_version;
    }

    /**
    * 接口日志分类
    * @access public
    * @param String $method API接口名称
    * @return 日志分类
    */
    public static function method2type($method=''){
        if (empty($method)) return NULL;

        $logtype_method_relationship = array(
            'store.trade' => // 交易信息同步
            array(
                'store.trade.update',
                'store.trade.status.update',
                'store.trade.ship_status.update',
                'store.trade.pay_status.update',
                'store.trade.memo.add',
                'store.trade.memo.update',
                'store.trade.buyer_message.add',
                'store.trade.shippingaddress.update',
                'store.trade.print.data.get',
                'store.trade.print',
                'store.trade.fullinfo.get',
                'store.iframe.trade.edit',
                'iframe.tradeEdit',                
            ),
            'store.trade.delivery' => // 发货信息同步
            array(
                'store.trade.shipping.add',
                'store.trade.shipping.update',
                'store.trade.shipping.status.update',
                'store.logistics.offline.send',
            ),
            'store.trade.stockout' => //出库信息同步
            array(
                'store.trade.outstorage',
            ),
            'store.trade.payment' => // 支付信息同步
            array(
                'store.trade.payment.add',
                'store.trade.payment.status.update',
                'store.shop.payment_type.list.get',
            ),
            'store.trade.refund' => // 退款信息同步
            array(
                'store.trade.refund.add',
                'store.trade.refund.status.update',
            ),
            'store.trade.aftersale' => // 售后信息同步
            array(
                'store.trade.aftersale.add',
                'store.trade.aftersale.status.update',
            ),
            'store.trade.reship' => // 退货信息同步
            array(
                'store.trade.reship.add',
                'store.trade.reship.status.update',
            ),
            'store.trade.stock' => // 库存信息同步
            array(
                'store.trade.item.freezstore.update',
                'store.items.quantity.list.update',
            ),
            'store.trade.goods' => // 商品信息同步
            array(
                'store.items.list.get',
                'store.item.sku_list.price.update',
                'store.item.approve_status_list.update'
            ),
        );
        $log_type_name = '';
        foreach ($logtype_method_relationship as $log_type=>$method_arr){
            if (in_array($method, $method_arr)){
                $log_type_name = $log_type;
                break;
            }
        }
        return $log_type_name;
    }

}