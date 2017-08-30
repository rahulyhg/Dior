<?php 
/**
* RPC接收请求路由
*
* @category apibusiness
* @package apibusiness/lib/router
* @author chenping<chenping@shopex.cn>
* @version $Id: response.php 2013-3-12 14:37Z
*/
class apibusiness_router_response
{
    private $_respservice = null;


    /**
     * 路由转发
     *
     * @param String $type 文件类型 可选值(order:订单,payment:支付单,refund:退款单,aftersale:售后单)
     * @return Array
     **/
    public function dispatch($type,$method,$sdf)
    {
        $class_name = sprintf('apibusiness_response_%s',$type);
        if (!class_exists($class_name)) {
            trigger_error("class `{$class_name}` is not exist");
        }

        if (is_null($this->_respservice)) {
            $this->_respservice = kernel::single('base_rpc_service');
        }
        
        $rs = kernel::single($class_name)->setRespservice($this->_respservice)->dispatch($method,$sdf);
        return $rs;
    }

    public function setRespservice($respservice)
    {
        $this->_respservice = $respservice;

        return  $this;
    }

    public function getRespservice()
    {
        return $this->_respservice;
    }


}