<?php
/**
* RPC接口实现类
*
* @author chenping<chenping@shopex.cn>
* @version 2012-7-11     
*/
class inventorydepth_taog_rpc_request_shop_skus extends ome_rpc_request
{
    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function __construct() 
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    public function get_err_msg(){
        return $this->err_msg;
    }

    public function set_err_msg($err_msg){
        return $this->err_msg = $err_msg;
    }

    /**
     * 下载货品
     *
     * @param Array $sku
     * $sku = array(
     *  'sku_id' => {SKU的ID}
     *  'iid'    => {商品ID}
     *  'seller_uname' => {卖家帐号}
     * );
     * @param String $shop_id 店铺ID
     * @return void
     * @author 
     **/
    public function item_sku_get($sku,$shop_id)
    {
        if(!$sku || !$shop_id) return false;
        /*
        $timeout = 10;

        $params = array(
            'sku_id' => $sku['sku_id'],
            'iid' => $sku['iid'],
            'num_iid' => $sku['iid'],
        );

        if($sku['seller_uname']) $params['seller_uname'] = $sku['seller_uname'];

        $api_name = 'store.item.sku.get';

        $result = $this->call($api_name,$params,$shop_id,$timeout);
        if ($result === false) {
            for ($i=0;$i<3;$i++) {
                $result = $this->call($api_name,$param,$shop_id,$timeout);
                if ($result !== false) {
                    break;
                }
            }
        }*/
        
        $result = $this->router->setShopId($shop_id)->item_sku_get($sku,$shop_id);
        
        if ($result === false) {
            $this->set_err_msg('请求失败：数据错误或请求超时!');
            return false;
        } elseif ($result->rsp !== 'succ') {
            $this->set_err_msg('请求失败：'.$result->err_msg . '('. $result->msg_id .')');
            return false;
        }

        return json_decode($result->data,true);
    }

    /**
     * 测试数据
     *
     * @return void
     * @author 
     **/
    private function test_data($iids = '')
    {
        require_once(ROOT_DIR.'/app/inventorydepth/testcase/skus.php');
        $data = json_decode($data,true);
        if ($iids) {
            foreach ($data['data']['items']['item'] as &$value) {
                if (!in_array($value['iid'], $iids)) {
                    unset($value);
                }
            }
        }        
        return $data;
    }
}