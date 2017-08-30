<?php
/**
 * 数学逻辑处理类
 *
 * @author chenping<chenping@shopex.cn>
 */
class inventorydepth_math {

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function get_show_comparison($key)
    {
        $return = array(
                'equal'   => $this->app->_('等于'),
                'than'    => $this->app->_('大于'),
                'lthan'   => $this->app->_('小于'),
                'bthan'   => $this->app->_('大于等于'),
                'sthan'   => $this->app->_('小于等于'),
                'between' => $this->app->_('介于'),
            );
        
        return $key ? $return[$key] : $return;
    }

    public function get_comparison($key)
    {
        $return = array(
                'equal' => '==', 
                'than'  => '>', 
                'lthan' => '<', 
                'bthan' => '>=', 
                'sthan' => '<=',
            );

        return $key ? $return[$key] : $return;
    }

    public function get_show_calculation($key)
    {
        $return = array(
                'subjoin'  => $this->app->_('加'),
                'subtract' => $this->app->_('减'),
                'multiply' => $this->app->_('乘'),
                'divide'   => $this->app->_('除'),
            );

        return $key ? $return[$key] : $return;
    }

    public function get_calculation($key)
    {
        $return = array(
                'subjoin'  => '+', 
                'subtract' => '-', 
                'multiply' => '*', 
                'divide'   => '/',
            );

        return $key ? $return[$key] : $return;
    }

}
