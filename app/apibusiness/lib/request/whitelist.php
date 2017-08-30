<?php
/**
 * RPC同步白名单
 * 所有发起请求的最后都需要经过白名单，拒绝或许可
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class apibusiness_request_whitelist {

    /**
     * 构造 初始化白名单
     * @access public
     * @return void
     */
    function __construct(){
        $this->whitelist = array(
            'shopex_b2c' => $this->shopexb2c,
            'shopex_b2b' => $this->shopexb2b,
            'ecos.b2c'   => $this->ecosb2c,
            'ecos.dzg'   => $this->ecosdzg,
            'taobao'     => $this->taobao,
            'ecshop_b2c' => $this->ecshop,
            'youa'       => $this->youa,
            'paipai'     => $this->paipai,
            '360buy'     => $this->jingdong,
            'yihaodian'  => $this->yihaodian,
            'qq_buy'     => $this->qqbuy,
            'dangdang'   => $this->dangdang,
            'amazon'     => $this->amazon,
            'vjia'       => $this->vjia,
            'alibaba'    => $this->alibaba,
            'suning'     => $this->suning,
            'yintai'   => $this->yintai,
            'icbc'   => $this->icbc,
             'mogujie'   => $this->mogujie,
             'gome'   => $this->gome,
             'wx'   => $this->wx,
             'ccb'   => $this->ccb,#建设银行
             'meilishuo'=>$this->meilishuo,
             'feiniu'=>$this->feiniu,
             'youzan'=>$this->youzan
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

        GET_TRADE_FULLINFO_RPC,//获取单笔交易的详细信息

        GET_TRADES_SOLD_RPC, //获取卖家订单列表接口 (同步)

        IFRAME_TRADE_EDIT_RPC,

        GET_FENXIAO_TRADE_FULLINFO_RPC, //获取单笔交易的详细信息(分销订单)
        
        GET_HQEPAY_LOGISTICS,  #订阅华强宝物流信息
        
        GET_CLOUD_STACK_PRINT_TAG,   //获取云栈大头笔

    );



    /**
     * EC-STORE RPC服务接口名列表
     * @access private
     */
    private $ecosb2c = array(
        UPDATE_TRADE_RPC,
        UPDATE_TRADE_STATUS_RPC,
        UPDATE_TRADE_SHIP_STATUS_RPC,
        UPDATE_TRADE_PAY_STATUS_RPC,
        ADD_TRADE_MEMO_RPC,
        UPDATE_TRADE_MEMO_RPC,
        UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        ADD_PAYMENT_RPC,
        UPDATE_PAYMENT_STATUS_RPC,
        ADD_REFUND_RPC,
        UPDATE_REFUND_STATUS_RPC,
        ADD_RESHIP_RPC,
        UPDATE_RESHIP_STATUS_RPC,
        ADD_SHIPPING_RPC,
        UPDATE_LOGISTICS_RPC,
        UPDATE_DELIVERY_STATUS_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        UPDATE_TRADE_ITEM_FREEZSTORE_RPC,
        UPDATE_AFTERSALE_STATUS_RPC,
        ADD_AFTERSALE_RPC,
        ADD_TRADE_BUYER_MESSAGE_RPC,
        GET_PAYMETHOD_RPC,
    );

    /**
     * 店掌柜 RPC服务接口名列表
     * @access private
     */
    private $ecosdzg = array(
        UPDATE_TRADE_RPC,
        UPDATE_TRADE_STATUS_RPC,
        UPDATE_TRADE_SHIP_STATUS_RPC,
        UPDATE_TRADE_PAY_STATUS_RPC,
        ADD_TRADE_MEMO_RPC,
        UPDATE_TRADE_MEMO_RPC,
        UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        ADD_PAYMENT_RPC,
        UPDATE_PAYMENT_STATUS_RPC,
        ADD_REFUND_RPC,
        UPDATE_REFUND_STATUS_RPC,
        ADD_RESHIP_RPC,
        UPDATE_RESHIP_STATUS_RPC,
        ADD_SHIPPING_RPC,
        UPDATE_LOGISTICS_RPC,
        UPDATE_DELIVERY_STATUS_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        UPDATE_TRADE_ITEM_FREEZSTORE_RPC,
        UPDATE_AFTERSALE_STATUS_RPC,
        ADD_AFTERSALE_RPC,
        ADD_TRADE_BUYER_MESSAGE_RPC,
        GET_PAYMETHOD_RPC,
    );
    
    /**
     * SHOPEX485 RPC服务接口名列表
     * @access private
     */
    private $shopexb2c = array(
        UPDATE_TRADE_RPC,
        UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        ADD_TRADE_MEMO_RPC,
        UPDATE_TRADE_MEMO_RPC,
        ADD_TRADE_BUYER_MESSAGE_RPC,
        UPDATE_TRADE_STATUS_RPC,
        UPDATE_TRADE_SHIP_STATUS_RPC,
        ADD_SHIPPING_RPC,
        UPDATE_LOGISTICS_RPC,
        UPDATE_DELIVERY_STATUS_RPC,
        ADD_RESHIP_RPC,
        //UPDATE_RESHIP_STATUS_RPC,//TODO: 因发起的退货单本身是成功的，所以无需再同步退货单状态
        ADD_REFUND_RPC,
        ADD_PAYMENT_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        GET_PAYMETHOD_RPC,
        UPDATE_AFTERSALE_STATUS_RPC,
        ADD_AFTERSALE_RPC,
    );

    /**
     * 当当 RPC服务接口名列表
     * @access private
     */
    private $dangdang = array(
        //UPDATE_ITEM_APPROVE_STATUS_RPC, //单个商品上下架  (矩阵已开放,但淘管目前不支持)
        UPDATE_ITEMS_QUANTITY_LIST_RPC,     /*更新库存*/  // store.items.quantity.update
        //'store.item.sku_list.price.update', // 批量更新价格 (矩阵已开放,但淘管目前不支持)
        //CLOSE_TRADE_RPC,               /* 卖家关闭交易接口 因业务逻辑断档 暂不适用*/  
        LOGISTICS_OFFLINE_RPC,        /*发货接口*/
    );

    /**
     * 一号店 RPC服务接口名列表
     * @access private
     */
    private $yihaodian = array(
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        LOGISTICS_OFFLINE_RPC,
        GET_TRADE_INVOICE_RPC,
        AGREE_RETURN_GOOD,
        REFUSE_RETURN_GOOD,
        CHECK_REFUND_GOOD,
        //'store.item.sku_list.price.update',//add by lymz at 2012-2-6 15:49:59 批量更新价格
        //UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,//add by lymz at 2012-2-6 16:20:36 批量更新上下架
        //'GET_ITEMS_LIST_RPC',//add by lymz at 2012-2-8 14:24:08 批量获取商品数据
    );

    /**
     * 淘宝 RPC服务接口名列表
     * @access private
     */
    private $taobao = array(
        UPDATE_ITEMS_QUANTITY_LIST_RPC,  /*直销库存更新接口*/
        UPDATE_FENXIAO_ITEMS_QUANTITY_LIST_RPC,  /*分销库存更新接口*/
        //DELIVERY_SEND_RPC,
        LOGISTICS_OFFLINE_RPC,          /*自己联系物流（线下物流）发货 (同步和异步)*/

		//接口
        LOGISTICS_ONLINE_RPC,          /*在线下单*/
        GET_ITEMS_ALL_RPC,                  /* 同步下载商品 */
        GET_ITEMS_LIST_RPC,                 /* 同步下载商品 根据IID*/
        UPDATE_ITEM_APPROVE_STATUS_RPC,     /*单个商品上下架*/
        UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,   /*批量商品上下架*/
        GET_ITEM_SKU_RPC,          /* 获取SKU  根据SKU_ID*/
        GET_ITEM_RPC,                /* 获取单个商品*/    
        GET_FENXIAO_PRODUCTS,
        UPDATE_FENXIAO_PRODUCT,
        GET_REFUND_MESSAGE,/*退款凭证*/
        REFUSE_REFUND,/*拒绝退款*/
        ADD_REFUND_MESSAGE,/*回写留言和凭证*/
        AGREE_RETURN_GOOD,/*同意退货*/
        REFUSE_RETURN_GOOD_TMALL,
        GET_REFUND_MESSAGE_TMALL,
        REFUNSE_REFUND_TMALL,
        AGREE_REFUND_TMALL,
        AGREE_RETURN_GOOD_TMALL,//天猫同意退货申请
        LOGISTICS_ADDRESS_SEARCH,
        REFUND_GOOD_RETURN_CHECK,
        GET_WAYBILL_NUMBER, /* 获取电子面单 */
        UPDATE_TRADE_SHIPPING_ADDRESS_RPC,//回写淘宝收货地址
        GET_TRADE_REFUND_RPC,//单拉售后退款单
        ADD_TMC_MESSAGE_PRODUCE,//淘宝全链路接口
        
        
        GET_TRADE_REFUND_I_RPC,
        AGREE_RETURN_I_GOOD_TMALL,
        REFUSE_RETURN_I_GOOD_TMALL,
        GET_REFUND_I_MESSAGE_TMALL,
        REFUNSE_REFUND_I_TMALL,
        AGREE_REFUND_I_TMALL,
        QUERY_JZPARTNER,
        CONSIGN_JZWIGHINS,
        );

    /**
     * 拍拍 RPC服务接口名列表
     * @access private
     */
    private $paipai = array(
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        DELIVERY_SEND_RPC,
        LOGISTICS_OFFLINE_RPC,          /*接口名称变更，此接口同：store.trade.delivery.send*/
        GET_ITEMS_ALL_RPC,                  /* 同步下载商品 */
        UPDATE_ITEM_APPROVE_STATUS_RPC,     /*单个商品上下架*/
        UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,   /*批量商品上下架*/
        GET_ITEM_SKU_RPC,          /* 获取SKU  根据SKU_ID*/
        GET_ITEM_RPC,                /* 获取单个商品*/
    );

    /**
     * qq网购 RPC服务接口名列表
     * @access private
     */
    private $qqbuy = array(
        //UPDATE_ITEMS_QUANTITY_LIST_RPC,
        DELIVERY_SEND_RPC,
        LOGISTICS_OFFLINE_RPC,          /*接口名称变更，此接口同：store.trade.delivery.send*/
    );

    /**
     * SHOPEX B2B RPC服务接口名列表
     * @access private
     */
    private $shopexb2b = array(
        UPDATE_TRADE_RPC,
        UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        //UPDATE_TRADE_SHIPPER_RPC,暂时不同步，B2B不需要做修改。
        ADD_TRADE_MEMO_RPC,
        UPDATE_TRADE_MEMO_RPC,
        ADD_TRADE_BUYER_MESSAGE_RPC,
        UPDATE_TRADE_STATUS_RPC,
        ADD_SHIPPING_RPC,
        ADD_RESHIP_RPC,
        UPDATE_RESHIP_STATUS_RPC,
        ADD_REFUND_RPC,
        ADD_PAYMENT_RPC,
        ADD_AFTERSALE_RPC,
        UPDATE_AFTERSALE_STATUS_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        GET_PAYMETHOD_RPC,
    );


    /**
     * 京东 RPC服务接口名列表
     * @access private
     */
    private $jingdong = array(
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        LOGISTICS_OFFLINE_RPC,
        GET_ITEMS_ALL_RPC,                  /* 同步下载商品 */
        GET_ITEMS_LIST_RPC,                 /* 同步下载商品 根据IID*/
        GET_ITEM_RPC,                      /* 获取单个商品*/
        GET_ITEM_SKU_RPC,               /* 获取SKU  根据SKU_ID*/
        UPDATE_ITEM_APPROVE_STATUS_RPC,     /*单个商品上下架*/
        UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,   /*批量商品上下架*/
        UPDATE_ITEMS_QUANTITY_LIST_RPC, /* 批量更新库存数量 */
        CHECK_REFUND_GOOD,
    );

    /**
     * 亚马逊 RPC服务接口名列表
     * @access private
     */
    private $amazon = array(
        LOGISTICS_OFFLINE_RPC,        /*发货接口*/
        UPDATE_ITEMS_QUANTITY_LIST_RPC  #更新库存
    );

    /**
     * 一号店 RPC服务接口名列表
     * @access private
     */
    private $vjia = array(
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        LOGISTICS_OFFLINE_RPC,
        GET_TRADE_INVOICE_RPC,
        DELIVERY_OUT_STORAGE,
        DELIVERY_OUT_STORAGE_CONFIRM,
        DELIVERY_CONSIGN_RESEND,
    );

    /**
     * 阿里巴巴 RPC服务接口名列表
     * @access private
     */
    private $alibaba = array(
        GET_ITEM_RPC,               /* 获取单个商品*/
        LOGISTICS_OFFLINE_RPC,      /* 线下发货接口*/
    );

    /**
     * 苏宁 RPC服务接口名列表
     * @access private
     */
    private $suning = array(
        GET_ITEM_RPC,               /* 获取单个商品*/
        LOGISTICS_OFFLINE_RPC,      /* 线下发货接口*/
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        GET_ITEMS_CUSTOM,
    );
        /**
        * 银泰 RPC服务接口列表
        * @access private
        */
    private $yintai = array(
        
        LOGISTICS_OFFLINE_RPC,
        GET_ITEMS_CUSTOM,
        UPDATE_ITEMS_QUANTITY_LIST_RPC
    
    );
    #工行RPC服务接口
    private $icbc = array(
        LOGISTICS_OFFLINE_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC
    );    
    #蘑菇街RPC服务接口
    private $mogujie = array(
        LOGISTICS_OFFLINE_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );  
    #国美RPC服务接口
    private $gome= array(
        LOGISTICS_OFFLINE_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #微信RPC服务接口
    private $wx= array(
        GET_ITEM_RPC,               /* 获取单个商品*/
        LOGISTICS_OFFLINE_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #建设银行RPC服务接口
    private $ccb = array(
        LOGISTICS_OFFLINE_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #meilishuoRPC服务接口
    private $meilishuo = array(
        LOGISTICS_OFFLINE_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        MEILISHUO_REFUND_GOOD_RETURN_AGREE
    );
     #飞牛RPC服务接口
    private $feiniu = array(
        LOGISTICS_OFFLINE_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #有赞RPC服务接口
    private $youzan = array(
        LOGISTICS_OFFLINE_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );    
    /**
     * prismb2c RPC服务接口名列表
     * @access private
     */
    private $prismb2c = array(
        UPDATE_TRADE_RPC,
        UPDATE_TRADE_STATUS_RPC,
        UPDATE_TRADE_SHIP_STATUS_RPC,
        UPDATE_TRADE_PAY_STATUS_RPC,
        ADD_TRADE_MEMO_RPC,
        UPDATE_TRADE_MEMO_RPC,
        UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        ADD_PAYMENT_RPC,
        UPDATE_PAYMENT_STATUS_RPC,
        ADD_REFUND_RPC,
        UPDATE_REFUND_STATUS_RPC,
        ADD_RESHIP_RPC,
        UPDATE_RESHIP_STATUS_RPC,
        ADD_SHIPPING_RPC,
        UPDATE_LOGISTICS_RPC,
        UPDATE_DELIVERY_STATUS_RPC,
        UPDATE_ITEMS_QUANTITY_LIST_RPC,
        UPDATE_TRADE_ITEM_FREEZSTORE_RPC,
        UPDATE_AFTERSALE_STATUS_RPC,
        ADD_AFTERSALE_RPC,
        ADD_TRADE_BUYER_MESSAGE_RPC,
        GET_PAYMETHOD_RPC,
    );

}