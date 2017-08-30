<?php
/**
 * CONFIG
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_ftp_config extends erpapi_wms_config
{
    /**
     * 获取请求地址
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @param Boolean $realtime 同步|异步
     * @return void
     * @author 
     **/
    public function get_url($method, $params, $realtime){
        $config = $this->__channelObj->wms['adapter']['config'];

        $url = $config['url'] ? $config['url'] : app::get('wmsmgr')->getConf('api_url'.$this->__channelObj->wms['node_id']);

        return $url;
    }

    /**
     * 应用级参数
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return void
     * @author 
     **/
    public function get_query_params($method, $params){
        $query_params = array(
            'to_node_id' => $this->__channelObj->wms['node_id'],
            'method'     => $method,
        );

        return $query_params;
    }

    /**
     * 签名
     *
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    public function gen_sign($params){
        return strtoupper(md5(strtoupper(md5(self::assemble($params)))));
    }

    /**
     * 定义应用参数
     *
     * @return void
     * @author 
     **/
    public function define_query_params(){
        $params = array(
                'label' => 'ftp联接',
                'params' => array(
                    'url'     => '请求地址',
                    'node_id' => '节点',
                ));
        return $params;
    }
}