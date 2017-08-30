<?php
abstract class erpapi_channel_abstract
{
    /**
     * 路由 matrix|openapi|prism
     *
     * @var string
     **/
    protected $__adapter = '';


    /**
     * 请求平台
     *
     * @var string
     **/
    protected $__platform = '';

    /**
     * 平台版本
     *
     * @var string
     **/
    protected $__ver = '1';

    /**
     * 
     *
     * @return void
     * @author 
     **/
    public function get_adapter()
    {
        return $this->__adapter;
    }

    /**
     * 请求平台
     *
     * @return void
     * @author 
     **/
    public function get_platform()
    {
        return $this->__platform;
    }

    /**
     * 版本号
     *
     * @return void
     * @author 
     **/
    public function get_ver()
    {
        return $this->__ver;
    }

    /**
     * 初始化请求配置
     *
     * @return void
     * @author 
     **/
    abstract public function init($node_id,$channel_id);
}