<?php
/**
 * CONFIG
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
abstract class erpapi_config
{
    protected $__channelObj = null;

    protected $__whitelist = array();
    private $__global_whitelist = array(
        WMS_SALEORDER_CREATE,
        WMS_SALEORDER_CANCEL,
        WMS_SALEORDER_GET,
        WMS_ITEM_ADD,
        WMS_ITEM_UPDATE,
        WMS_RETURNORDER_CREATE,
        WMS_RETURNORDER_CANCEL,
        WMS_RETURNORDER_GET,
        WMS_TRANSFERORDER_CREATE,
        WMS_TRANSFERORDER_CANCEL,
        WMS_INORDER_CREATE,
        WMS_INORDER_CANCEL,
        WMS_INORDER_GET,
        WMS_OUTORDER_CREATE,
        WMS_OUTORDER_CANCEL,
        WMS_OUTORDER_GET,
        WMS_WAREHOUSE_LIST_GET,
        WMS_LOGISTICS_COMPANIES_GET,
        WMS_VENDORS_GET,
        WMS_ORDER_CANCEL, 
    );

    public function init(erpapi_channel_abstract $channel)
    {
        $this->__channelObj = $channel;

        return $this;
    }

    public function get_channel()
    {
        return $this->__channelObj;
    }

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
        $row = app::get('base')->model('network')->getlist('node_url,node_api', array('node_id'=>1));
        if($row){
            if(substr($row[0]['node_url'],-1,1)!='/'){
                $row[0]['node_url'] = $row[0]['node_url'].'/';
            }
            if($row[0]['node_api']{0}=='/'){
                $row[0]['node_api'] = substr($row[0]['node_api'],1);
            }
            $url = $row[0]['node_url'].$row[0]['node_api'];

            if ($realtime==true) $url .= 'sync';
        }

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

    }

    /**
     * 签名
     *
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    public function gen_sign($params){
        if (!base_shopnode::token('ome'))
            $sign = base_certificate::gen_sign($params);
        else
            $sign = base_shopnode::gen_sign($params,'ome');

        return $sign;
    }

    /**
     * 定义应用参数
     *
     * @return void
     * @author 
     **/
    public function define_query_params(){}

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function format($params)
    {
        return $params;
    }

    /**
     * 白名单
     *
     * @return void
     * @author 
     **/
    public function whitelist($apiname)
    {
        $whitelist = array_merge($this->__global_whitelist, $this->__whitelist);

        return (!$whitelist || in_array($apiname, $whitelist)) ? true : false;
    }

    static function assemble($params)
    {
        if(!is_array($params))  return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params AS $key=>$val){
            if(is_null($val))   continue;
            if(is_bool($val))   $val = ($val) ? 1 : 0;
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;       
    }
}