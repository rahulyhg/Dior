<?php
/**
 * CONFIG
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_config extends erpapi_config
{
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
            'app_id'       => 'ecos.ome',
            'method'       => $method,
            'date'         => date('Y-m-d H:i:s'),
            'format'       => 'json',
            'certi_id'     => base_certificate::certi_id(),
            'v'            => '1.1',
            'from_node_id' => base_shopnode::node_id('ome'),
            'to_node_id'   => $this->__channelObj->wms['node_id'],
            'to_api_v'     => $this->__channelObj->wms['api_version'],
            'node_type'    => $this->__channelObj->wms['node_type'],
        );

        return $query_params;
    }
}