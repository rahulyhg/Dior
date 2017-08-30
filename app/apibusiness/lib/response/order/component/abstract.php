<?php
/**
* 订单组件抽象
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_component_abstract
{
    // 平台
    protected $_platform = null;

    public function convert(){}

    public function update(){}

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

    /**
     * 比较数组值
     *
     * @return void
     * @author 
     **/
    public function comp_array_value($a,$b)
    {
        if ($a == $b) {
            return 0;
        }

        return $a > $b ? 1 : -1 ;
    }

    /**
     * 过滤空
     *
     * @return void
     * @author 
     **/
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
    }
}