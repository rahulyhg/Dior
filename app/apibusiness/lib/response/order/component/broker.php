<?php
/**
* 订单组装厂
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: broker.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_broker extends apibusiness_response_order_component_abstract
{
    // 组件集合
    private $_components = array();

    /**
     * 清组件
     *
     * @return void
     * @author 
     **/
    public function clearComponents()
    {
        $this->_components = array();
        return $this;
    }

    /**
     * 转标准格式
     *
     * @return Array
     * @author 
     **/
    public function convert()
    {
        $newOrder = array();
        foreach ($this->_components as $_component) {
            $tmp = $_component->setPlatform($this->_platform)->convert();
        }
    }

    /**
     * 更新订单
     *
     * @return Array
     * @author 
     **/
    public function update()
    {
        $newOrder = array();
        foreach ($this->_components as $_component) {
            $tmp = $_component->setPlatform($this->_platform)->update();
        }
    }

    /**
     * 注册一个组件
     *
     * @return void
     * @author 
     **/
    public function registerComponent($component_name, $stackIndex = null)
    {
        $component = kernel::single('apibusiness_response_order_component_'.$component_name);

        if (false !== array_search($component, $this->_components, true)) {
            trigger_error('订单组件已经注册过',E_USER_ERROR);
        }

        $stackIndex = (int) $stackIndex;

        if ($stackIndex) {
            if (isset($this->_components[$stackIndex])) {
                trigger_error('组件键值已经存在',E_USER_NOTICE);   
            }
            $this->_components[$stackIndex] = $component;
        } else {
            $stackIndex = count($this->_components);
            while (isset($this->_components[$stackIndex])) {
                ++$stackIndex;
            }
            $this->_components[$stackIndex] = $component;
        }
        
        ksort($this->_components);

        return $this;
    }

    /**
     * 加组件
     *
     * @return void
     * @author 
     **/
    public function unregisterComponent($component)
    {

        if ($component instanceof apibusiness_response_order_component_abstract) {

            $key = array_search($component, $this->_components, true);

            unset($this->_components[$key]);

        } elseif (is_string($component)) {
            foreach ($this->_components as $key => $_component) {
                $type = get_class($_component);
                if ($component == $type) {
                    unset($this->_components[$key]);
                }
            }
        }
        return $this;
    }
}