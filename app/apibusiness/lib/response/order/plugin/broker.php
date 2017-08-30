<?php
/**
* 订单插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: broker.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_broker extends apibusiness_response_order_plugin_abstract
{
    protected $_plugins = array();

    /**
     * 清插件
     *
     * @return void
     * @author 
     **/
    public function clearPlugins()
    {
        $this->_plugins = array();
        return $this;
    }

    /**
     * 订单保存之前处理
     *
     * @return void
     * @author 
     **/
    public function preCreate()
    {
        foreach ($this->_plugins as $plugin) {
            $plugin->setPlatform($this->_platform)->preCreate();
        }
    }

    /**
     * 订单保存之后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        foreach ($this->_plugins as $plugin) {
            $plugin->setPlatform($this->_platform)->postCreate();
        }
    }

    /**
     * 订单更新之前处理
     *
     * @return void
     * @author 
     **/
    public function preUpdate()
    {
        foreach ($this->_plugins as $plugin) {
            $plugin->setPlatform($this->_platform)->preUpdate();
        }
    }

    /**
     * 订单更新之后处理
     *
     * @return void
     * @author 
     **/
    public function postUpdate()
    {
        foreach ($this->_plugins as $plugin) {
            $plugin->setPlatform($this->_platform)->postUpdate();
        }
    }

    /**
     * 注册一个插件
     *
     * @return void
     * @author 
     **/
    public function registerPlugin($plugin_name, $stackIndex = null)
    {
        $plugin = kernel::single('apibusiness_response_order_plugin_'.$plugin_name);

        if (false !== array_search($plugin, $this->_plugins, true)) {
            trigger_error('插件已经存在',E_USER_ERROR);
        }

        $stackIndex = (int) $stackIndex;

        if ($stackIndex) {
            if (isset($this->_plugins[$stackIndex])) {
                trigger_error('插件键值已经存在',E_USER_NOTICE);   
            }
            $this->_plugins[$stackIndex] = $plugin;
        } else {
            $stackIndex = count($this->_plugins);
            while (isset($this->_plugins[$stackIndex])) {
                ++$stackIndex;
            }
            $this->_plugins[$stackIndex] = $plugin;
        }
        
        ksort($this->_plugins);

        return $this;
    }

    public function unregisterPlugin($plugin)
    {
        if ($plugin instanceof apibusiness_response_order_plugin_abstract) {

            $key = array_search($plugin, $this->_plugins, true);

            unset($this->_plugins[$key]);
        } elseif (is_string($plugin)) {
            foreach ($this->_plugins as $key => $_plugin) {
                $type = get_class($_plugin);
                if ($plugin == $type) {
                    unset($this->_plugins[$key]);
                }
            }
        }
        return $this;
    }

}