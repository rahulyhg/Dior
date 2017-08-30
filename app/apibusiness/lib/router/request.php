<?php
/**
* RPC向外请求路由
*
* @category apibusiness
* @package apibusiness/lib/router
* @author chenping<chenping@shopex.cn>
* @version $Id: request.php 2013-3-12 14:37Z
*/
class apibusiness_router_request
{
    const _APP_NAME = 'ome';

    private $shop_id = '';

    /**
     * 设置店铺ID
     *
     * @param String $shop_id
     * @return Object
     * @author 
     **/
    public function setShopId($shop_id)
    {
        $this->shop_id = $shop_id;

        return $this;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function __call($method,$args)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        
        if (!$this->shop_id) {
            $rs['msg'] = 'set shop id first';
            return $rs;
        }

        $shopModel = app::get(self::_APP_NAME)->model('shop');
        $shop = $shopModel->dump($this->shop_id);
        
        if (!$shop) {
            $rs['msg'] = 'noShop';
            return $rs;
        } elseif ($shop && !$shop['node_id']) {
            $rs['msg'] = 'noNodeId';
            return $rs;
        }
        $tbbusiness_type = $shop['tbbusiness_type'];
        $platform = $this->getPlatform($shop['node_type'],$shop['api_version'],$tbbusiness_type);
        
        if (!$platform) {
            $rs['msg'] = 'initial platform error';
            return $rs;
        }
        $platform->setShop($shop);

        return call_user_func_array(array($platform,$method), $args);
    }


    /**
     * 平台类名
     *
     * @param String $node_type 店铺类型
     * @param String $node_version 店铺版本
     * @param String $tgver 淘管版本
     * @return String
     **/
    private function getClassName($node_type,$node_version,&$tgver,$tbbusiness_type)
    {
        # 获取版本
        $tgver = kernel::single('apibusiness_router_mapping')->getVersion($node_type,$node_version);

        $dirname = $node_type;
        if (isset(apibusiness_router_mapping::$shopex[$node_type])) {
            $dirname = 'shopex_'.apibusiness_router_mapping::$shopex[$node_type];  
        } elseif (isset(apibusiness_router_mapping::$party[$node_type])) {
            $dirname = apibusiness_router_mapping::$party[$node_type];
        }
        if ($dirname == 'taobao') {
            if ($tbbusiness_type == 'B') {
                $dirname = 'tmall';
            }
        }
        do {
            # 如果版本号小于0，直接报错
            if ($tgver<=0) return false;

            $class_name = sprintf('apibusiness_request_v%s_%s',$tgver,$dirname);

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

    /**
     * 平台类初始化
     *
     * @param String $node_type 店铺类型
     * @param String $node_version 店铺版本
     * @return Object
     * @author 
     **/
    private function getPlatform($node_type,$node_version,$tbbusiness_type='')
    {
        $class_name = $this->getClassName($node_type,$node_version,$tgver,$tbbusiness_type);

        if ($class_name === false) {
            return false;
        }

        $platform = kernel::single($class_name);
       	if (!$platform instanceof apibusiness_request_abstract) {
            return false;
        }

        $platform->setTgVer($tgver);

        return $platform;
    }
}