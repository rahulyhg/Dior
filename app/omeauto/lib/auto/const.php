<?php
/**
 * 定义一些常量
 *
 * @author hzjsq@msn.com
 * @version 0.1
 */

class omeauto_auto_const {

    //有未付订单
    const __PAY_CODE        = 0x00000001;
    //备注和留言
    const __FLAG_CODE       = 0x00000002;
    //物流公司标记
    const __LOGI_CODE       = 0x00000004;
    //产品不匹配
    const __PRODUCT_CODE    = 0x00000008;
    //用户多订单
    const __MEMBER_CODE     = 0x00000010;
    //乡村物流标记
    const __LOGI_LITE_CODE  = 0x00000020;
    //单订单
    const __SINGLE_CODE     = 0x00000040;
    //多订单
    const __MUTI_CODE       = 0x00000080;
    //仓库
    const __BRANCH_CODE     = 0x00000100;
    //库存
    const __STORE_CODE      = 0x00000200;
    //异常
    const __ABNORMAL_CODE   = 0x00000400;
    //单订单且有备注
    const __EXAMINE_CODE    = 0x00000800;
    //超卖订单
    const __OVERSOLD_CODE   = 0x00001000;
    //淘宝订单优惠中有赠品信息
    const __PMTGIFT_CODE    = 0x00002000;
    const __COMBINE_CODE    = 0x00004000;
    //CRM赠品信息
    const __CRMGIFT_CODE    = 0x00008000;
    //检测订单是否开发票
    const __TAX_CODE        = 0x00010000;
    //检查物流到不到
    const _LOGIST_ARRIVED = 0x00040000;
}