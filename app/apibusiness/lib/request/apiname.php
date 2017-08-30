<?php
// 添加发货单
define("ADD_PAYMENT_RPC",'store.trade.payment.add');
//更改支付状态
define("UPDATE_PAYMENT_STATUS_RPC",'store.trade.payment.status.update');
//添加售后申请单
define("ADD_AFTERSALE_RPC",'store.trade.aftersale.add');
// 更新售后申请状态
define("UPDATE_AFTERSALE_STATUS_RPC",'store.trade.aftersale.status.update');
// 添加退款单
define("ADD_REFUND_RPC",'store.trade.refund.add');
// 更改退款单状态
define("UPDATE_REFUND_STATUS_RPC",'store.trade.refund.status.update');
// 添加退货单
define("ADD_RESHIP_RPC",'store.trade.reship.add');
// 更改退货单状态
define("UPDATE_RESHIP_STATUS_RPC",'store.trade.reship.status.update');
// 线上物流
define("LOGISTICS_ONLINE_RPC",'store.logistics.online.send');
// 线下物流
define("LOGISTICS_OFFLINE_RPC",'store.logistics.offline.send');
// 添加发货单
define("ADD_SHIPPING_RPC",'store.trade.shipping.add');
// 更新物流信息
define("UPDATE_LOGISTICS_RPC",'store.trade.shipping.update');
// 拍拍发货接口
define("DELIVERY_SEND_RPC",'store.trade.delivery.send');
// 更新支付单状态
define("UPDATE_DELIVERY_STATUS_RPC",'store.trade.shipping.status.update');
// 订单编辑
define("IFRAME_TRADE_EDIT_RPC",'iframe.tradeEdit');
// 订单更新
define("UPDATE_TRADE_RPC",'store.trade.update');
// 订单状态更新
define("UPDATE_TRADE_STATUS_RPC",'store.trade.status.update');
// 状态订单
define("CLOSE_TRADE_RPC",'store.trade.close');
// 更新订单发票信息
define("UPDATE_TRADE_TAX_RPC",'store.trade.tax.update');
// 更新订单发货状态
define("UPDATE_TRADE_SHIP_STATUS_RPC",'store.trade.ship_status.update');
// 更新订单支付状态
define("UPDATE_TRADE_PAY_STATUS_RPC",'store.trade.pay_status.update');
// 更新订单备注
define("UPDATE_TRADE_MEMO_RPC",'store.trade.memo.update');
// 添加订单备注
define("ADD_TRADE_MEMO_RPC",'store.trade.memo.add');
// 添加买家留言
define("ADD_TRADE_BUYER_MESSAGE_RPC",'store.trade.buyer_message.add');
// 更新交易收货人信息
define("UPDATE_TRADE_SHIPPING_ADDRESS_RPC",'store.trade.shippingaddress.update');
// 更新发货人信息
define("UPDATE_TRADE_SHIPPER_RPC",'store.trade.shipper.update');
// 更新代销人信息
define("UPDATE_TRADE_SELLING_AGENT_RPC",'store.trade.selling_agent.update');
// 更新订单失效时间
define("UPDATE_TRADE_ORDER_LIMITTIME_RPC",'store.trade.order_limit_time.update');
// 单拉订单接口
define("GET_TRADE_FULLINFO_RPC",'store.trade.fullinfo.get');
// 拉取某个时间段的订单
define("GET_TRADES_SOLD_RPC",'store.trades.sold.get');
// 获取支付方式
define("GET_PAYMETHOD_RPC",'store.shop.payment_type.list.get');
// 更新预占
define("UPDATE_TRADE_ITEM_FREEZSTORE_RPC",'store.trade.item.freezstore.update');
// 更新库存 直销
define("UPDATE_ITEMS_QUANTITY_LIST_RPC",'store.items.quantity.list.update');
// 更新库存 分销
define("UPDATE_FENXIAO_ITEMS_QUANTITY_LIST_RPC",'store.fenxiao.items.quantity.list.update');
// 获取前端商品
define("GET_ITEMS_ALL_RPC", 'store.items.all.get');
// 通过IID获取前端商品
define("GET_ITEMS_LIST_RPC", 'store.items.list.get');
// 获取单个商品
define("GET_ITEM_RPC", 'store.item.get');
// 获取前端SKU
define("GET_ITEM_SKU_RPC", 'store.item.sku.get');
// 单个上下架
define("UPDATE_ITEM_APPROVE_STATUS_RPC", 'store.item.approve_status.update');
// 批量上下架
define("UPDATE_ITEM_APPROVE_STATUS_LIST_RPC", 'store.item.approve_status_list.update');
// 获取发票抬头
define("GET_TRADE_INVOICE_RPC", 'store.trade.invoice.get');
// 淘分销单拉
define("GET_FENXIAO_TRADE_FULLINFO_RPC", 'store.fenxiao.trade.fullinfo.get');
// 获取店铺session
define("GET_SERVICE_USER_SESSION_RPC", 'service.user.session.get');
// 发货出库
define("DELIVERY_OUT_STORAGE", 'store.trade.outstorage');
// 修改配送信息
define("DELIVERY_CONSIGN_RESEND", 'store.logistics.consign.resend');
// 发货出库确认
define("DELIVERY_OUT_STORAGE_CONFIRM", 'store.logistics.resend.confirm');
// 分销商品
define("GET_FENXIAO_PRODUCTS",'store.fenxiao.products.get');
// 更新分销商品
define("UPDATE_FENXIAO_PRODUCT",'store.fenxiao.product.update');
//退款凭证获取
define('GET_REFUND_MESSAGE','store.refund.message.get');
//拒绝退款单
define('REFUSE_REFUND','store.refund.refuse');

define('ADD_REFUND_MESSAGE','store.refund.message.add');
define('AGREE_RETURN_GOOD','store.refund.good.return.agree');//同意退货
define('CHECK_REFUND_GOOD','store.refund.good.return.check');//确认退货
define('REFUSE_RETURN_GOOD','store.refund.good.return.refuse');//拒绝退货
define('REFUSE_RETURN_GOOD_TMALL','store.tmall.refund.good.return.refuse');//
define('GET_REFUND_MESSAGE_TMALL','store.tmall.refund.message.get');
define('REFUNSE_REFUND_TMALL','store.tmall.refund.refuse');
define('AGREE_RETURN_GOOD_TMALL','store.tmall.refund.good.return.agree');
define('AGREE_REFUND_TMALL','store.tmall.trade.refund.examine');//同意退款
define('LOGISTICS_ADDRESS_SEARCH','store.logistics.address.search');//卖家地址库
define('REFUND_GOOD_RETURN_CHECK','store.eai.order.refund.good.return.check');//回填退货物流信息
define('GET_TRADE_REFUND_RPC','store.eai.order.refund.get');//单拉退款单信息

#天猫2015年新的售后接口
define('GET_TRADE_REFUND_I_RPC','store.eai.order.refund.i.get');#单拉退款单信息
define('AGREE_RETURN_I_GOOD_TMALL','store.tmall.refund.i.good.return.agree');#同意退货
define('REFUSE_RETURN_I_GOOD_TMALL','store.tmall.refund.i.good.return.refuse');#拒绝退货
define('GET_REFUND_I_MESSAGE_TMALL','store.tmall.refund.i.message.get');#获取凭证
define('REFUNSE_REFUND_I_TMALL','store.tmall.refund.i.refuse');#获取拒绝退款
define('AGREE_REFUND_I_TMALL','store.tmall.trade.refund.i.examine');#同意退款



// 获取电子面单
define("GET_WAYBILL_NUMBER",'store.waybillallocation.requestwaybillnum');

// 苏宁获取商品信息
define("GET_ITEMS_CUSTOM", 'store.items.custom.get');

//淘宝全链路接口
define("ADD_TMC_MESSAGE_PRODUCE", "store.tmc.message.produce");

#美丽说同意退款、退货（退款退货用的同一个接口）
define("MEILISHUO_REFUND_GOOD_RETURN_AGREE", "store.refund.good.return.agree");
#华强宝物流订阅
define("GET_HQEPAY_LOGISTICS", "store.logistics.pub");
//家装服务商
define('QUERY_JZPARTNER','store.wlb.order.jzpartner.query');
//家装发货
define('CONSIGN_JZWIGHINS','store.wlb.order.jzwithins.consign');
//获取云栈大头笔
define("GET_CLOUD_STACK_PRINT_TAG","store.wlb.waybill.i.distributeinfo");