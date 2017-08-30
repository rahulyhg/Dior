<?php
/**
 * 库存计算类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_stock_calculation {

    function __construct($app)
    {
        //$identity = $app->getConf('inventorydepth.system.identity');
        $identity = app::get('inventorydepth')->runtask('getIdentity');

        $this->object = kernel::single("inventorydepth_{$identity}_stock_calculation");
    }


    public function __call($method,$arguments)
    {
        return call_user_func_array(array($this->object,$method), $arguments);
    }

}
