<?php
class ome_shop_type{

    /**
     * 节点类型
     * @access public
     * @return Array
     */
    static function get_shop_type(){
        $shop_type = array (
            'shopex_b2c' => '48体系网店',
            'shopex_b2b' => '分销王',
            'ecos.ome' => '后端业务处理系统',
            'ecos.b2c' => 'ec-store',
            'ecos.dzg' => '直销平台',
            'taobao' => '淘宝',
            'paipai' => '拍拍',
            '360buy' => '京东',
            'ecshop_b2c' => 'ecshop',
            'yihaodian' => '一号店',
            'qq_buy' => 'qq网购',
            'dangdang' => '当当',
            'amazon' => '亚马逊',
            'yintai' => '银泰',
            'vjia' => '凡客',
            'alibaba' => '阿里巴巴',
            'suning' => '苏宁',
            'wx' => '微信',
            'gome' => '国美',
            'mogujie' => '蘑菇街',
            'icbc'=>'工行',
            'ccb'=>'建行',
            'meilishuo'=>'美丽说',
             'feiniu'=>'飞牛',
             'youzan'=>'有赞'   
          );
        return $shop_type;
    }

    /**
     * 获取节点类型名称
     * @param string $shop_type 店铺类型
     * @return 店铺类型名称
     */
    static function shop_name($shop_type=''){
        $types = self::get_shop_type();
        return $types[$shop_type];
    }

    /**
     * C2C前端店铺列表
     * @return array
     */
    static function shop_list(){
        $shop = array('taobao','paipai','youa','360buy','yihaodian','qq_buy','dangdang','amazon','yintai','vjia','alibaba','suning','icbc','mogujie','gome','wx','ccb','meilishuo','feiniu','youzan');
        return $shop;
    }

    /**
     * B2B前端店铺列表
     * @return array
     */
    static function b2b_shop_list(){
        $shop = array('shopex_b2b');
        return $shop;
    }
    /**
     * 库存回写是否增加本店铺冻结库存
     * @access public
     * @return Array
     */
    static function get_store_config(){
        $store_config = array (
            'shopex_b2b' => 'off',
            'shopex_b2c' => 'off',
            'ecos.b2c' => 'off',
            'ecos.dzg' => 'off',
            'ecshop_b2c' => 'off',
            'taobao' => 'off',
            'paipai' => 'off',
            'qq_buy' => 'off',
            '360buy' => 'on',
            'yihaodian' => 'on',
            'dangdang' => 'off',
            'amazon' => 'off',
            'yintai' => 'off',
            'vjia' => 'off',
            'alibaba' => 'off',
            'suning' => 'off',
        );
        return $store_config;
    }

    /**
     * 京东类型
     * 京东类型
     * @return array
     */
    static function jingdong_type(){
        $shop = array('360buy');
        return $shop;
    }

    /**
     * shopex前端店铺列表
     * @author yangminsheng
     * @return array
     **/
    static function shopex_shop_type(){
        $shop = array('shopex_b2b','shopex_b2c','ecos.b2c','ecshop_b2c','ecos.dzg');
        return $shop;
    }

    /**
     * (已弃用) chenping 2014-1-9
     * 前端店铺是否需要发货明细
     * @params on 需要发货明细 off 不需要
     * @return void
     * @author
     **/
    static function is_shop_deliveryitem($shop_type = null)
    {
        $shop_list = array(
            'shopex_b2c' => 'on',
            'shopex_b2b' => 'on',
            'ecos.b2c' => 'on',
            'ecos.dzg' => 'on',
            'taobao' => 'off',
            'paipai' => 'off',
            '360buy' => 'off',
            'ecshop_b2c' => 'off',
            'yihaodian' => 'off',
            'qq_buy' => 'off',
            'dangdang' => 'on',
            'amazon' => 'on',
            'yintai' => 'off',
            'vjia' => 'off',
            'alibaba' => 'off',
        );

        return $shop_list[$shop_type];
    }

    /**
     * 是否允许开启单独获取店铺订单配置
     * @param shop_type
     * @return void
     * @author
     **/
    static function get_shoporder_config($shop_type = null)
    {
        $shop_config = array(
            'shopex_b2b' => 'on',
            'shopex_b2c' => 'on',
            'ecos.b2c' => 'on',
            'ecos.dzg' => 'on',
            'taobao' => 'on',
            'paipai' => 'on',
            'qq_buy' => 'on',
            '360buy' => 'on',
            'yihaodian' => 'on',
            'dangdang' => 'off',
            'amazon' => 'off',
            'yintai' => 'off',
            'vjia' => 'off',
            'alibaba' => 'off',
            'suning' => 'on',
            'icbc' => 'on',
        );

        if(!empty($shop_type)){
            return $shop_config[$shop_type];
        }

        return $shop_config;
    }

}