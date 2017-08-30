<?php
/**
 * 出货单模板
 *
 */
class ome_delivery_template_delivery {
    protected $elements = array (
        'consignee_name' => '收货人-姓名',
        'consignee_province' => '收货人-地区1级',
        'consignee_city' => '收货人-地区2级',
        'consignee_district' => '收货人-地区3级',
        'consignee_addr' => '收货人-地址',
        'consignee_zip' => '收货人-邮编',
        'consignee_telephone' => '收货人-联系电话',
        'consignee_mobile' => '收货人-手机',
        'consignee_email' => '收货人-Email',
        'buyWord' => '会员备注',
        'orderMark' => '订单附言',
        'delivery_bn' => '收货人-发货单号',

        'sender_name' => '发货人-姓名',
        'sender_province' => '发货人-地区1级',
        'sender_city' => '发货人-地区2级',
        'sender_district' => '发货人-地区3级',
        'sender_addr' => '发货人-地址',
        'sender_tel' => '发货人-联系电话',
        'sender_mobile' => '发货人-手机',

        'shop_name' => '店铺名称',
        'member_name' => '会员名',
        'member_tel' => '会员联系方式',
        'op_name' => '操作员',

        'date_y'      => '当日日期-年',
        'date_m'      => '当日日期-月',
        'date_d'      => '当日日期-日',
        'date_ymd'      => '当日日期-年月日',

        'order_bn'    => '订单-订单号',
        'net_weight' => '商品重量',
        'delivery_cost_expect' => '预计物流费用',
        'logi_name' => '物流公司',
        'batch_number' => '批次号',
    );
    
    /**
     * 默认选项列表
     * Enter description here ...
     */
    public function defaultElements() {
        return $this->elements;
    }
}