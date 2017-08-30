<?php
/**
* 版本文件映射关系
*
* @category apibusiness
* @package apibusiness/lib/router
* @author chenping<chenping@shopex.cn>
* @version $Id: mapping.php 2013-3-12 14:37Z
*/
class apibusiness_router_mapping
{
    public static $shopex = array(
        'shopex_b2b' => 'fxw',
        'shopex_b2c' => '485',
        'ecos.b2c'   => 'ecstore',
        'ecos.dzg'   => 'dzg',
        'ecos.taocrm' => 'taocrm',
        'prism_b2c'=>'prism_b2c',
    );

    public static $party = array(
        'qq_buy' => 'qqbuy',
    );

    public static $versionm = array(
        'shopex_b2c' => array(      // 485   前端版本 => 淘管版本
            '1' => '1',
            '2' => '2'
        ),
        'shopex_b2b' => array(      // b2b
            '1' => '1',
            '3.2' => '2',
        ),
        'ecos.b2c' => array(        // ecstore
            '1' => '1',
            '2' => '2',
        ),
        'ecos.dzg' => array(        // 店掌柜
            '1' => '1',
            '2' => '2',
        ),
        'prism_b2c' => array(        // prism
            '1' => '1',
            '2' => '2',
        ),
    );

    /**
     * 获取淘管对应版本
     * 
     * @param String $node_type 店铺类型
     * @param String $api_version 前端店铺版本
     **/
    public function getVersion($node_type,$api_version)
    {

        if(!isset(self::$versionm[$node_type])) return 1;

        $mapping = self::$versionm[$node_type];
        krsort($mapping);

        $tgver = 1;
        foreach ($mapping as $s_ver => $t_ver) {
            if (version_compare($api_version, $s_ver,'>=')) {
                $tgver = $t_ver; break;
            }
        }

        return $tgver;
    }

    private static $_rsp_service_mapping = array(
        'api.ome.order' => 'order',
        'api.ome.refund' => 'refund',
        'api.ome.payment' => 'payment',
        'api.ome.aftersale' => 'aftersale',
        'api.ome.aftersalev2' => 'aftersalev2',
        'api.ome.logistics' => 'logistics',
        'api.ome.remark' => 'remark',
    );

    /**
     * SERVICE映射
     *
     * @return void
     * @author 
     **/
    public static function getRspServiceMapping($service)
    {
        $type = isset(self::$_rsp_service_mapping[$service]) ? self::$_rsp_service_mapping[$service] : '';
        
        return $type;
    }

}