<?php
/**
* RPC接口实现类
*
* @author chenping<chenping@shopex.cn>
* @version 2012-6-25
*/
class inventorydepth_taog_rpc_request_shop_items extends ome_rpc_request
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
     * 实时下载店铺商品
     *
     * @param Array $filter 筛选条件(approve_status)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array $items
     **/
    public function items_all_get($filter,$shop_id,$offset=0,$limit=100)
    {
        $timeout = 20;

        if(!$shop_id) return false;
        
        /*
        $param = array(
                'page_no'        => $offset,
                'page_size'      => $limit,
                'fields'         => 'iid,outer_id,bn,num,title,default_img_url,modified,detail_url,approve_status,skus,price,barcode ',
            );

        $param = array_merge((array)$param,(array)$filter);

        $api_name = 'store.items.all.get';

        $result = $this->call($api_name,$param,$shop_id,$timeout);
        if ($result === false) {
            for ($i=0;$i<3;$i++) {
                $result = $this->call($api_name,$param,$shop_id,$timeout);
                if ($result !== false) {
                    break;
                }
            }
        }*/
        $result = $this->router->setShopId($shop_id)->items_all_get($filter,$shop_id,$offset,$limit);

        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result->rsp !== 'succ'){
            $this->set_err_msg('请求失败：'.$result->err_msg . '('. $result->msg_id .')');
            return false;
        }

        return json_decode($result->data,true);
    }

    /**
     * 实时下载店铺商品
     *
     * @param Array $filter 筛选条件(approve_status)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array $items
     **/
    public function fenxiao_products_get($filter,$shop_id,$offset=0,$limit=100)
    {
        $timeout = 20;

        if(!$shop_id) return false;

        $result = $this->router->setShopId($shop_id)->fenxiao_products_get($filter,$shop_id,$offset,$limit);

        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result->rsp !== 'succ'){
            $this->set_err_msg('请求失败：'.$result->err_msg . '('. $result->msg_id .')');
            return false;
        }

        return json_decode($result->data,true);
    }

    /**
     * 根据IID，实时下载店铺商品
     *
     * @param Array $iids 商品ID(不要超过限度20个)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array
     **/
    public function items_list_get($iids,$shop_id)
    {

        if(!$iids || !$shop_id) return false;
        
        /*
        if(is_array($iids)) $iids = implode(',', $iids);

        $timeout = 10;

        $param = array(
            'iids' => $iids,
        );

        $api_name = 'store.items.list.get';

        $result = $this->call($api_name,$param,$shop_id,$timeout);
        if ($result === false) {
            for ($i=0;$i<3;$i++) {
                $result = $this->call($api_name,$param,$shop_id,$timeout);
                if ($result !== false) {
                    break;
                }
            }
        }*/
        $result = $this->router->setShopId($shop_id)->items_list_get($iids,$shop_id);

        if($result === false){
            $this->set_err_msg('请求失败!');
            return false;
         }elseif ($result->rsp !== 'succ') {
            $this->set_err_msg('请求失败：'.$result->err_msg . '('. $result->msg_id .')');
            return false;
         }
        return json_decode($result->data,true);
    }

    /**
     * 获取单个商品明细
     *
     * @param Int $iid商品ID
     * @param String $shop_id 店铺ID
     * @return void
     * @author
     **/
    public function item_get($iid,$shop_id)
    {
        if(!$iid || !$shop_id) return false;
        /*
        $timeout = 20;

        $param = array(
            'iid' => $iid,
        );

        $api_name = 'store.item.get';

         $result = $this->call($api_name,$param,$shop_id,$timeout);
        if ($result === false) {
            for ($i=0;$i<3;$i++) {
                $result = $this->call($api_name,$param,$shop_id,$timeout);
                if ($result !== false) {
                    break;
                }
            }
        }*/
        
        $result = $this->router->setShopId($shop_id)->item_get($iid,$shop_id);

        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result->rsp !== 'succ') {
            $this->set_err_msg('请求失败：'.$result->err_msg . '('. $result->msg_id .')');
            return false;
        }
        return json_decode($result->data,true);
    }

    public function fenxiao_product_update($product,$shop_id)
    {
        if(!$product || !$shop_id) return false;

        $result = $this->router->setShopId($shop_id)->fenxiao_product_update($product);
        

        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result->rsp !== 'succ') {
            $this->set_err_msg('请求失败：'.$result->err_msg . '('. $result->msg_id .')');
            return false;
        }

        return true;
    }

    /**
     * 测试数据
     *
     * @return void
     * @author
     **/
    private function test_data($iids = '')
    {
        require_once(ROOT_DIR.'/app/inventorydepth/testcase/data.php');
        $data = json_decode($data,true);
        $data['data'] = json_encode($data['data']);
        $data = json_encode($data);
        $data = json_decode($data);
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