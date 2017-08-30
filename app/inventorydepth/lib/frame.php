<?php
/**
 * 商品上架处理类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_frame {
    const PUBL_LIMIT = 100;
    const SYNC_LIMIT = 50;
    const PUBL_TIME = 1440;//距离上次成功发布的发布间隔 单位 分钟

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function get_benchmark($key)
    {
        $return = array(
                'upper' => '上架',
                'lower' => '下架',
            );

        return $key ? $return[$key] : $return;
    }

    public function sku_option($key)
    {
        $return = array(
            'each' => '全部货品',
            'some' => '某一货品',
        );

        return $key ? $return[$key] : $return;
    }

}
