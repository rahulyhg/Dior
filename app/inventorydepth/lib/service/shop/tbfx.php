<?php
/**
* 淘宝商品处理
* 
* chenping<chenping@shopex.cn>
*/
class inventorydepth_service_shop_tbfx extends inventorydepth_service_shop_common
{
    public $approve_status = array(
            array('filter'=>array(),'name'=>'全部','flag'=>'all','alias'=>'全部'),
    );

    function __construct(&$app)
    {
        $this->app = $app;
    }

    public function downloadListNOSku($filter,$shop_id,$offset=0,$limit=200,&$errormsg) {
        if ($filter['approve_status'] == 'onsale') {
            $filter['status'] = 'up'; unset($filter['approve_status']);
        }

        return $this->downloadList($filter,$shop_id,$offset,$limit,$errormsg);
    }

    /**
     * 下载全部
     *
     * @return void
     * @author 
     **/
    public function downloadList($filter,$shop_id,$offset=0,$limit=200,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');

        $count = 0;
        do {
            if ($count>60) {
                $errormsg = '超出最大循环次数';
                return false;
            }
            usleep(1000000);
            $result = $shopService->fenxiao_products_get($filter,$shop_id,$offset,$limit);

            if ($result === false) {
                $errormsg = $shopService->get_err_msg();
                # 临时做一下兼容，待明天矩阵更新后还原
                if (false !== strpos($errormsg,'请求失败：') && strtotime('2013-3-21')>=time()) {
                    $errormsg = '';
                } elseif (false !== strpos($errormsg,'This ban will last for 1 more seconds') ) {
                    $errormsg = '';
                } else {
                    return false;
                }
            } else {
                break;
            }

            $count++;
        }while(true);
        # 请求商品
        
        # 数据为空
        if(empty($result['products']['fenxiao_product'])){
            $this->totalResults = 0;
            return array();
        }
        
        $this->totalResults = $result['total_results'];
        
        $data = array();
        foreach ($result['products']['fenxiao_product'] as $value) {
            $product = array(
                'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                'iid' => $value['pid'] ? $value['pid'] : '',
                'title' => $value['name'] ? $value['name'] : '',
                'approve_status' => $value['status']=='up' ? 'onsale' : 'instock',
                'price' => $value['standard_price'],
                'num' => $value['quantity'] > 0 ? $value['quantity'] : 0,
                //'detail_url' => $value['desc_path'],
                'default_img_url' => $value['pictures'],
                'props' => $value['properties'],
                'simple' => 'true',
                'skus' => '',
            );
            
            if ($value['skus']['fenxiao_sku']) {
                $skus = array();
                foreach ((array) $value['skus']['fenxiao_sku'] as $v) {
                    $skus[] = array(
                        'sku_id' => $v['id'],
                        'properties' => $v['properties'],
                        'price' => $v['standard_price'],
                        'quantity' => $v['quantity'] > 0 ? $v['quantity'] : 0,
                        'properties_name' => $v['name'],
                        'outer_id' => $v['outer_id'],
                    );
                }

                $product['simple'] = 'false';
                $product['skus']['sku'] = $skus;
            }

            $data[] = $product;
        }
        
        return $data;
    }

    /**
     * 通过IID批量下载
     *
     * @return void
     * @author 
     **/
    public function downloadByIIds($iids,$shop_id,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        $filter = array(
            'pids' => implode(',',$iids),
        );
        $result = $shopService->fenxiao_products_get($filter,$shop_id);

        $data = array();
        foreach ($result['products']['fenxiao_product'] as $value) {
            $product = array(
                'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                'iid' => $value['pid'] ? $value['pid'] : '',
                'title' => $value['name'] ? $value['name'] : '',
                'approve_status' => $value['status']=='up' ? 'onsale' : 'instock',
                'price' => $value['standard_price'],
                'num' => $value['quantity'] > 0 ? $value['quantity'] : 0,
                //'detail_url' => $value['desc_path'],
                'default_img_url' => $value['pictures'],
                'props' => $value['properties'],
                'simple' => 'true',
                'skus' => '',
            );
            
            if ($value['skus']['fenxiao_sku']) {
                $skus = array();
                foreach ((array) $value['skus']['fenxiao_sku'] as $v) {
                    $skus[] = array(
                        'sku_id' => $v['id'],
                        'properties' => $v['properties'],
                        'price' => $v['standard_price'],
                        'quantity' => $v['quantity'] > 0 ? $v['quantity'] : 0,
                        'properties_name' => $v['name'],
                        'outer_id' => $v['outer_id'],
                    );
                }

                $product['simple'] = 'false';
                $product['skus']['sku'] = $skus;
            }

            $data[] = $product;
        }

        return $data;
    }

    public function downloadByIId($iid,$shop_id,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        $filter = array(
            'pids' => $iid,
        );
        $result = $shopService->fenxiao_products_get($filter,$shop_id);

        $data = array();
        foreach ($result['products']['fenxiao_product'] as $value) {
            $product = array(
                'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                'iid' => $value['pid'] ? $value['pid'] : '',
                'title' => $value['name'] ? $value['name'] : '',
                'approve_status' => $value['status']=='up' ? 'onsale' : 'instock',
                'price' => $value['standard_price'],
                'num' => $value['quantity'] > 0 ? $value['quantity'] : 0,
                //'detail_url' => $value['desc_path'],
                'default_img_url' => $value['pictures'],
                'props' => $value['properties'],
                'simple' => 'true',
                'skus' => array('price' => $value['standard_price']),
            );
            
            if ($value['skus']['fenxiao_sku']) {
                $skus = array();
                foreach ((array) $value['skus']['fenxiao_sku'] as $v) {
                    $skus[] = array(
                        'sku_id' => $v['id'],
                        'properties' => $v['properties'],
                        'price' => $v['standard_price'],
                        'quantity' => $v['quantity'] > 0 ? $v['quantity'] : 0,
                        'properties_name' => $v['name'],
                        'outer_id' => $v['outer_id'],
                    );
                }

                $product['simple'] = 'false';
                $product['skus']['sku'] = $skus;
            }

            //$data[] = $product;
        }

        return $product;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function doApproveSync($data,$shop_id,&$msg) 
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        
        $product['pid'] = $data['iid'];
        $product['status'] = ($data['approve_status'] == 'onsale') ? 'up' : 'down';

        $result = $shopService->fenxiao_product_update($product,$shop_id);

        if ($result === false) {
            $msg = $shopService->get_err_msg();
            return false;
        }

        $approve_status = ($data['approve_status'] == 'onsale') ? '上架' : '下架';

        if ($result == true) {
            $msg = $approve_status.'成功';
            return true;
        }else{
            $msg = $approve_status.'失败';
            return false;
        }
    }

}