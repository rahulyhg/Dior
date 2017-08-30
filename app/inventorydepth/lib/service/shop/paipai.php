<?php
/**
* 拍拍商品处理
* 
* chenping<chenping@shopex.cn>
*/
class inventorydepth_service_shop_paipai extends inventorydepth_service_shop_common
{
    
    function __construct(&$app)
    {
        $this->app = $app;
    }

    public function downloadListNOSku($filter,$shop_id,$offset=0,$limit=200,&$errormsg) {
        $data = parent::downloadListNOSku($filter,$shop_id,$offset,$limit,$errormsg);
        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                // 过滤掉假删除商品
                if (empty($value['approve_status'])) {
                    continue;
                }

                $tmpData[] = array(
                    'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                    'iid' => $value['iid'] ? $value['iid'] : '',
                    'title' => $value['title'] ? $value['title'] : '',
                    'approve_status' => $value['approve_status'] ? $value['approve_status'] : '',
                    'price' => $value['price'],
                    'num' => $value['num'],
                    'detail_url' => '',
                    'default_img_url' => $value['default_img_url'],
                    'props' => $value['props'],
                );
            }
            $data = $tmpData; unset($tmpData);
        }

        return $data;
    }

    public function downloadList($filter,$shop_id,$offset=0,$limit=200,&$errormsg)
    {
        //$data = parent::downloadList($filter,$shop_id,$offset,$limit,$errormsg);
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');

        # 请求商品
        $result = $shopService->items_all_get($filter,$shop_id,$offset,$limit);
        if($result === false){ 
            $errormsg = $shopService->get_err_msg();
            return false;
        }
        
        # 数据为空
        if(empty($result['items']['item'])){
            $this->totalResults = 0;
            return array();
        }
        
        $this->totalResults = $result['totalResults'];
        
        $data = array();$time = time();
        foreach ($result['items']['item'] as $value) {
            
            // 过滤掉假删除商品
            if (empty($value['approve_status'])) {
                continue;
            }

             $item = $shopService->item_get($value['iid'],$shop_id);
             if ($item === false){ 
                 $errormsg[] = $value['iid'].'：'.$shopService->get_err_msg();
                 continue;
             } 
             if(empty($item['item'])){
                $errormsg[] = $value['iid'].'不存在！';
                 continue;
             }

             $data[] = $item['item'];
        }
        unset($result,$item);
        
        //return $data;

        # 数据重组
        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                if ($value['skus']['sku']) {
                    foreach ($value['skus']['sku'] as $key=>$sku) {
                        $value['skus']['sku'][$key]['properties_name'] = $sku['properties'];

                        // 判断是否是单商品
                        if(count($value['skus']['sku']) == 1 && $sku['sku_id'] == '0' && !$sku['outer_id']){
                            $value['skus']['sku'][$key]['outer_id'] = $value['outer_id'];
                            $value['skus']['sku'][$key]['sku_id'] = $value['iid'];
                        }
                    }
                }

                $tmpData[] = array(
                    'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                    'iid' => $value['iid'] ? $value['iid'] : '',
                    'title' => $value['title'] ? $value['title'] : '',
                    'approve_status' => $value['status'] ? $value['status'] : '',
                    'price' => $value['price'],
                    'num' => $value['num'],
                    'detail_url' => '',
                    'default_img_url' => $value['default_img_url'],
                    'props' => $value['props'],
                    'simple' => 'true',
                    'skus' => $value['skus'] ? $value['skus'] : '',
                );
            }
            $data = $tmpData;
        }

        return $data;
    }

    public function downloadByIId($iid,$shop_id,&$errormsg)
    {
        $data = parent::downloadByIId($iid,$shop_id,$errormsg);
        if ($data) {
            if ($data['skus']['sku']) {
                foreach ($data['skus']['sku'] as $key=>$sku) {
                    $data['skus']['sku'][$key]['properties_name'] = $sku['properties'];

                    // 判断是否是单商品
                    if(count($data['skus']['sku']) == 1 && $sku['sku_id'] == '0' && !$sku['outer_id']){
                        $data['skus']['sku'][$key]['outer_id'] = $data['outer_id'];
                        $data['skus']['sku'][$key]['sku_id'] = $data['iid'];
                    }
                }
            }

            $tmpData = array(
                'outer_id' => $data['outer_id'] ? $data['outer_id'] : '',
                'iid' => $data['iid'] ? $data['iid'] : '',
                'title' => $data['title'] ? $data['title'] : '',
                'approve_status' => $data['status'] ? $data['status'] : '',
                'price' => $data['price'],
                'num' => $data['num'],
                'detail_url' => '',
                'default_img_url' => $data['default_img_url'],
                'props' => $data['props'],
                'simple' => 'true',
                'skus' => $data['skus'] ? $data['skus'] : '',
            );
            
            $data = $tmpData;
        }

        return $data;
    }

    public function downloadByIIds($iids,$shop_id,&$errormsg)
    {
        $data = parent::downloadByIIds($iids,$shop_id,$errormsg);
        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                if ($value['skus']['sku']) {
                    foreach ($value['skus']['sku'] as $key=>$sku) {
                        $value['skus']['sku'][$key]['properties_name'] = $sku['properties'];

                        // 判断是否是单商品
                        if(count($value['skus']['sku']) == 1 && $sku['sku_id'] == '0' && !$sku['outer_id']){
                            $value['skus']['sku'][$key]['outer_id'] = $value['outer_id'];
                            $value['skus']['sku'][$key]['sku_id'] = $value['iid'];
                        }
                    }
                }

                $tmpData[] = array(
                    'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                    'iid' => $value['iid'] ? $value['iid'] : '',
                    'title' => $value['title'] ? $value['title'] : '',
                    'approve_status' => $value['status'] ? $value['status'] : '',
                    'price' => $value['price'],
                    'num' => $value['num'],
                    'skus' => $value['skus'] ? $value['skus'] : '',
                    'simple' => 'true',
                );
            }
            $data = $tmpData;
        }
        return $data;
    }
}