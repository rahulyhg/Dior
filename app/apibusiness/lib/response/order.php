<?php
/**
* 订单路由
*
* @category apibusiness
* @package apibusiness/response/
* @author chenping<chenping@shopex.cn>
* @version $Id: order.php 2013-3-12 17:23Z
*/
define('FRST_OPER_ID','88');
define('FRST_OPER_NAME','system');
define('FRST_TRIGGER_OBJECT_TYPE','订单：系统接口收订');
define('FRST_TRIGGER_ACTION_TYPE','apibusiness_response_order：add');
class apibusiness_response_order
{
    private $_respservice = null;

    const _APP_NAME = 'ome';

    /**
     * 订单方法跳转
     *
     * @return void
     * @author 
     **/
    public function dispatch($method,$sdf)
    {
        $data = array('tid' => $sdf['order_bn']);

        if($sdf['mark_text']==='0') {
            $sdf['mark_text'] = str_replace('0','０',$sdf['mark_text']);
        }
        if($sdf['custom_mark']==='0') {
            $sdf['custom_mark'] = str_replace('0','０',$sdf['custom_mark']);
        }

        if (!base_rpc_service::$node_id) {
            $this->_respservice->send_user_error('no node id',$data);
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
        $sdf['shop'] = $shop;

        $class_name = $this->getClassName($sdf,$shop,$tgver);
        $obj = kernel::single($class_name,$sdf);

        if (!$obj instanceof apibusiness_response_order_abstractbase) {
            $this->_respservice->send_user_error("Class `{$class_name}` is not instance of apibusiness_response_order_abstractbase");
        }
        if (!method_exists($obj, $method)) {
            $this->_respservice->send_user_error("method `{$method}` is not exist",$data);
        }

        $obj->setRespservice($this->_respservice)
            ->setTgVer($tgver)
            ->setShop($shop)
            ->$method();

        $api_name = ($method == 'payment_update') ? 'api.store.trade.payment' : 'api.store.trade';


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
                             implode('<hr/>',(array)$obj->_apiLog['info']),
                             '',
                             $api_name,
                             $sdf['order_bn']);

        return $data;
    }

    public function setRespservice($respservice)
    {
        $this->_respservice = $respservice;

        return  $this;
    }

    public function getClassName($sdf,$shop,&$tgver)
    {
        # 获取版本
        $tgver = kernel::single('apibusiness_router_mapping')->getVersion($shop['node_type'],$shop['node_version']);

        $dirname = $shop['shop_type'];
        if (isset(apibusiness_router_mapping::$shopex[$shop['shop_type']])) {
            $dirname = 'shopex_'.apibusiness_router_mapping::$shopex[$shop['shop_type']];  
        } elseif (isset(apibusiness_router_mapping::$party[$shop['shop_type']])){
            $dirname = apibusiness_router_mapping::$party[$shop['shop_type']];
        }

        if ($sdf['t_type'] == 'fenxiao' || $sdf['order_source'] == 'taofenxiao') {
            $order_type = 'b2b';
        } else {
            $order_type = 'b2c';
        }
        
        do {
            # 如果版本号小于0，直接报错
            if ($tgver<=0) {
                $this->_respservice->send_user_error('no version matched',array('tid' => $sdf['order_bn'])); exit;
            }

            $class_name = sprintf('apibusiness_response_order_%s_%sv%s',$dirname,$order_type,$tgver);
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