<?php
/**
* 支付单路由
*
* @category apibusiness
* @package apibusiness/response/payment
* @author chenping<chenping@shopex.cn>
* @version $Id: payment.php 2013-3-12 17:23Z
*/
class apibusiness_response_payment
{
    private $_respservice = null;

    const _APP_NAME = 'ome';

    public function setRespservice($respservice)
    {
        $this->_respservice = $respservice;

        return  $this;
    }

    /**
     * 支付单调整方法跳转
     *
     * @param String $method 调用方法
     * @param Array $sdf 接收参数
     * @return void
     * @author 
     **/
    public function dispatch($method,$sdf)
    {
        $data = array('tid'=>$sdf['order_bn'],'payment_id'=>$sdf['payment_bn'],'retry'=>'false');

        if (!base_rpc_service::$node_id) {
            $this->_respservice->send_user_error('4007',$data);
        }

        $shopModel = app::get(self::_APP_NAME)->model('shop');
        $shop = $shopModel->dump(array('node_id'=>base_rpc_service::$node_id));
        if (!$shop) {
            $this->_respservice->send_user_error('shop is not exist',$data);
        }

        $shop['node_version'] = $sdf['node_version'] ? $sdf['node_version'] : $shop['api_version'];
        if (version_compare($shop['node_version'], $shop['api_version'],'>')) {
            $shopModel->update(array('api_version'=>$shop['node_version']),array('shop_id'=>$shop['shop_id']));
        }

        $class_name = $this->getClassName($sdf,$shop,$tgver);
        $obj = kernel::single($class_name,$sdf);
        if (!$obj instanceof apibusiness_response_payment_abstract) {
            $this->_respservice->send_user_error("Class `{$class_name}` is not a object of `apibusiness_response_payment_abstract`",$data);
        }
        if (!method_exists($obj, $method)) {
            $this->_respservice->send_user_error("method `{$method}` is not exist",$data);
        }

        $obj->setRespservice($this->_respservice)
            ->setTgVer($tgver)
            ->setShop($shop)
            ->$method();

        $logModel = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,
                             $obj->_apiLog['title'],
                             get_class($this), 
                             $method, 
                             '', 
                             '', 
                             'response', 
                             'success', 
                             implode('<hr/>',$obj->_apiLog['info']),
                             '',
                             'store.trade.payment',
                             $sdf['order_bn']);
        return $data;
    }

    public function getClassName($sdf,$shop,&$tgver)
    {
        $data = array('tid'=>$sdf['order_bn'],'payment_id'=>$sdf['payment_bn'],'retry'=>'false');

        # 获取版本
        $tgver = kernel::single('apibusiness_router_mapping')->getVersion($shop['node_type'],$shop['node_version']);
        
        do {
            # 如果版本号小于0，直接报错
            if ($tgver<=0) {
                $this->_respservice->send_user_error('no version matched',$data); exit;
            }

            $class_name = sprintf('apibusiness_response_payment_v%s',$tgver);
            try{
                # 版本文件存在跳出循环
                if (class_exists($class_name)) break;
            } catch (Exception $e) {
                // do nothing
            }

            # 当前版本文件不存在，取低版本的
            $tgver--;

        } while (true);

        return $class_name;
    }
}