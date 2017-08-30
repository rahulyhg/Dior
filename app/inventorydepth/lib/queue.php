<?php
/**
 * 队列调用
 * 
 * @author chenping<chenping@shopex.cn>
 */
class inventorydepth_queue {

    function __construct($app)
    {
        //$identity = $app->getConf('inventorydepth.system.identity');
        $identity = app::get('inventorydepth')->runtask('getIdentity');

        $this->object = kernel::single("inventorydepth_{$identity}_queue");
    
    }

    public function __call($method,$arguments)
    {
        return call_user_func_array(array($this->object,$method), $arguments);
    }
}
