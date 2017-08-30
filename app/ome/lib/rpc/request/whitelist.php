<?php
/**
 * RPC同步白名单
 * 所有发起请求的最后都需要经过白名单，拒绝或许可
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_whitelist {

    /**
     * 构造 初始化白名单
     * @access public
     * @return void
     */
    function __construct(){
        $this->whitelist = array(
            'shopex_b2c' => $this->shopexb2c,
            'shopex_b2b' => $this->shopexb2b,
            'ecos.b2c' => $this->ecosb2c,
            'ecos.dzg' => $this->ecosdzg,
            'taobao' => $this->taobao,
            'ecshop_b2c' => $this->ecshop,
            'youa' => $this->youa,
            'paipai' => $this->paipai,
            '360buy' => $this->jingdong,
            'yihaodian' => $this->yihaodian,
            'qq_buy' => $this->qqbuy,
            'dangdang' => $this->dangdang,
            'amazon' => $this->amazon,
            'yintai' => $this->yintai,
        );
    }

    /**
     * RPC白名单过滤
     * @access public
     * @param string $node_type 节点类型
     * @param 远程服务接口名称
     * @return boolean 允许或拒绝
     */
    public function check_node($node_type,$method){
        if(in_array($method,$this->public_api) || (isset($this->whitelist[$node_type]) && in_array($method,$this->whitelist[$node_type]))){

            return true;
        }else{
            return false;
        }
    }


    /**
    * 公共接口名单
    */

    public $public_api = array(

        'store.trade.fullinfo.get',//获取单笔交易的详细信息(直销订单)

        'store.fenxiao.trade.fullinfo.get',//获取单笔交易的详细信息(分销订单)

        'store.trades.sold.get', //获取卖家订单列表接口 (同步)

        'store.iframe.trade.edit', //订单编辑

        'iframe.tradeEdit',
        'store.user.trade.search',//支付宝交易记录实时获取
        'store.topats.user.accountreport.get',//支付宝交易任务号获取
        'store.topats.result.get',//支付宝交易任务号结果获取

    );



    /**
     * EC-STORE RPC服务接口名列表
     * @access private
     */
    private $ecosb2c = array(
        'store.trade.update',
        'store.trade.status.update',
        'store.trade.ship_status.update',
        'store.trade.pay_status.update',
        'store.trade.memo.add',
        'store.trade.memo.update',
        'store.trade.shippingaddress.update',
        'store.trade.payment.add',
        'store.trade.payment.status.update',
        'store.trade.refund.add',
        'store.trade.refund.status.update',
        'store.trade.reship.add',
        'store.trade.reship.status.update',
        'store.trade.shipping.add',
        'store.trade.shipping.update',
        'store.trade.shipping.status.update',
        'store.items.quantity.list.update',
        'store.trade.item.freezstore.update',
        'store.trade.aftersale.status.update',
        'store.trade.aftersale.add',
        'store.trade.buyer_message.add',
        'store.shop.payment_type.list.get',
    );

    /**
     * 店掌柜 RPC服务接口名列表
     * @access private
     */
    private $ecosdzg = array(
        'store.trade.update',
        'store.trade.status.update',
        'store.trade.ship_status.update',
        'store.trade.pay_status.update',
        'store.trade.memo.add',
        'store.trade.memo.update',
        'store.trade.shippingaddress.update',
        'store.trade.payment.add',
        'store.trade.payment.status.update',
        'store.trade.refund.add',
        'store.trade.refund.status.update',
        'store.trade.reship.add',
        'store.trade.reship.status.update',
        'store.trade.shipping.add',
        'store.trade.shipping.update',
        'store.trade.shipping.status.update',
        'store.items.quantity.list.update',
        'store.trade.item.freezstore.update',
        'store.trade.aftersale.status.update',
        'store.trade.aftersale.add',
        'store.trade.buyer_message.add',
        'store.shop.payment_type.list.get',
    );

    /**
     * SHOPEX485 RPC服务接口名列表
     * @access private
     */
    private $shopexb2c = array(
        'store.trade.update',
        'store.trade.shippingaddress.update',
        'store.trade.memo.add',
        'store.trade.memo.update',
        'store.trade.buyer_message.add',
        'store.trade.status.update',
        'store.trade.ship_status.update',
        'store.trade.shipping.add',
        'store.trade.shipping.update',
        'store.trade.shipping.status.update',
        'store.trade.reship.add',
        //'store.trade.reship.status.update',//TODO: 因发起的退货单本身是成功的，所以无需再同步退货单状态
        'store.trade.refund.add',
        'store.trade.payment.add',
        'store.items.quantity.list.update',
        'store.shop.payment_type.list.get',
        'store.trade.aftersale.status.update',
        'store.trade.aftersale.add',
    );

    /**
     * 亚马逊 RPC服务接口名列表
     * @access private
     */
    private $amazon = array(
        'store.logistics.offline.send',        /*发货接口*/
    );

    /**
     * 银泰 RPC服务接口名列表
     * @access private
     */
    private $yintai = array(
    	'store.items.quantity.list.update',
        'store.logistics.offline.send',        /*发货接口*/
    );

    /**
     * 当当 RPC服务接口名列表
     * @access private
     */
    private $dangdang = array(
        //'store.item.approve_status.update', //单个商品上下架  (矩阵已开放,但淘管目前不支持)
        'store.items.quantity.list.update',     /*更新库存*/  // store.items.quantity.update
        //'store.item.sku_list.price.update', // 批量更新价格 (矩阵已开放,但淘管目前不支持)
        //'store.trade.close',               /* 卖家关闭交易接口 因业务逻辑断档 暂不适用*/
        'store.logistics.offline.send',        /*发货接口*/
    );

    /**
     * 一号店 RPC服务接口名列表
     * @access private
     */
    private $yihaodian = array(
        'store.items.quantity.list.update',
        'store.logistics.offline.send',
        'store.trade.invoice.get',
        //'store.item.sku_list.price.update',//add by lymz at 2012-2-6 15:49:59 批量更新价格
        //'store.item.approve_status_list.update',//add by lymz at 2012-2-6 16:20:36 批量更新上下架
        //'store.items.list.get',//add by lymz at 2012-2-8 14:24:08 批量获取商品数据
    );

    /**
     * 淘宝 RPC服务接口名列表
     * @access private
     */
    private $taobao = array(
        'store.items.quantity.list.update',
        //'store.trade.delivery.send',
        'store.logistics.offline.send',             /* 自己联系物流（线下物流）发货 (同步和异步)*/
        'store.logistics.online.send',              /* 在线下单*/
        'store.items.all.get',                      /* 同步下载商品 */
        'store.items.list.get',                     /* 同步下载商品 根据IID*/
        'store.item.approve_status.update',         /* 单个商品上下架*/
        'store.item.approve_status_list.update',    /* 批量商品上下架*/
        'store.item.sku.get',                       /* 获取SKU  根据SKU_ID*/
        'store.item.get',                           /* 获取单个商品*/    
        'store.bill.accounts.get',                  /* 获取淘宝的财务科目明细*/
        'store.bills.get',                          /* 获取费用明细*/
        'store.bill.book.bills.get',             /* 获取虚拟账户明细数据*/
    );

    /**
     * 拍拍 RPC服务接口名列表
     * @access private
     */
    private $paipai = array(
        'store.items.quantity.list.update',
        'store.trade.delivery.send',
        'store.logistics.offline.send',          /*接口名称变更，此接口同：store.trade.delivery.send*/
        'store.items.all.get',                  /* 同步下载商品 */
        'store.item.approve_status.update',     /*单个商品上下架*/
        'store.item.approve_status_list.update',   /*批量商品上下架*/
        'store.item.sku.get',          /* 获取SKU  根据SKU_ID*/
        'store.item.get',                /* 获取单个商品*/
    );

    /**
     * qq网购 RPC服务接口名列表
     * @access private
     */
    private $qqbuy = array(
        //'store.items.quantity.list.update',
        'store.trade.delivery.send',
        'store.logistics.offline.send',          /*接口名称变更，此接口同：store.trade.delivery.send*/
    );

    /**
     * SHOPEX B2B RPC服务接口名列表
     * @access private
     */
    private $shopexb2b = array(
        'store.trade.update',
        'store.trade.shippingaddress.update',
        //'store.trade.shipper.update',暂时不同步，B2B不需要做修改。
        'store.trade.memo.add',
        'store.trade.memo.update',
        'store.trade.buyer_message.add',
        'store.trade.status.update',
        'store.trade.shipping.add',
        'store.trade.reship.add',
        'store.trade.reship.status.update',
        'store.trade.refund.add',
        'store.trade.payment.add',
        'store.trade.aftersale.add',
        'store.trade.aftersale.status.update',
        'store.items.quantity.list.update',
        'store.shop.payment_type.list.get',
    );


    /**
     * 京东 RPC服务接口名列表
     * @access private
     */
    private $jingdong = array(
        'store.items.quantity.list.update',
        'store.logistics.offline.send',
        'store.trade.outstorage',//出库
        'store.items.all.get',                  /* 同步下载商品 */
        'store.items.list.get',                 /* 同步下载商品 根据IID*/
        'store.item.get',                      /* 获取单个商品*/
        'store.item.sku.get',               /* 获取SKU  根据SKU_ID*/
        'store.item.approve_status.update',     /*单个商品上下架*/
        'store.item.approve_status_list.update',   /*批量商品上下架*/
        'store.items.quantity.list.update' /* 批量更新库存数量 */
    );

    /**
     * ECSHOP RPC服务接口名列表
     * @access private
     */
    private $ecshop = array();

    /**
     * 有啊 RPC服务接口名列表
     * @access private
     */
    private $youa = array();

}