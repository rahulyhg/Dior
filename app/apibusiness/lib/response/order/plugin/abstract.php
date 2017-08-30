<?php
/**
* 订单插件抽象类
*
* @category apibusiness
* @package apibusiness/response/order/plugin
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_plugin_abstract
{
    // 平台
    protected $_platform = null;

    /**
     * 订单保存之前处理
     *
     * @return void
     * @author 
     **/
    public function preCreate()
    {}

    /**
     * 订单保存之后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {}

    /**
     * 订单更新之前处理
     *
     * @return void
     * @author 
     **/
    public function preUpdate()
    {}

    /**
     * 订单更新之后处理
     *
     * @return void
     * @author 
     **/
    public function postUpdate()
    {}

    /**
     * 平台
     *
     * @return void
     * @author 
     **/
    public function setPlatform($platform)
    {
        $this->_platform = $platform;

        return $this;
    }
}