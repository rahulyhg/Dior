<?php
/**
* 京东商品处理
* 
* chenping<chenping@shopex.cn>
*/
class inventorydepth_service_shop_360buy extends inventorydepth_service_shop_common
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
        $data = parent::downloadList($filter,$shop_id,$offset,$limit,$errormsg);
        # 数据重组
        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                # SKU
                if ($value['skus']['sku']) {
                    $value['num'] = 0;
                    foreach ($value['skus']['sku'] as $key=>$sku) {
                        $value['skus']['sku'][$key]['quantity'] = $sku['num'];
                        $value['num'] += $sku['num'];
                    }
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
                    'simple' => 'true',
                    'skus' => $value['skus'] ? $value['skus'] : '',
                );
            }
            $data = $tmpData;unset($tmpData);
        }

        return $data;
    }

    public function downloadByIId($iid,$shop_id,&$errormsg)
    {
        $data = parent::downloadByIId($iid,$shop_id,$errormsg);
        if ($data) {
            # SKU
            if ($data['skus']['sku']) {
                $data['num'] = 0;
                foreach ($data['skus']['sku'] as $key=>$sku) {
                    $data['skus']['sku'][$key]['quantity'] = $sku['num'];
                    $data['num'] += $sku['num'];
                }
            }

            $tmpData = array(
                'outer_id' => $data['outer_id'] ? $data['outer_id'] : '',
                'iid' => $data['iid'] ? $data['iid'] : '',
                'title' => $data['title'] ? $data['title'] : '',
                'approve_status' => $data['approve_status'] ? $data['approve_status'] : '',
                'price' => $data['price'],
                'num' => $data['num'],
                'detail_url' => '',
                'default_img_url' => $data['default_img_url'],
                'props' => $data['props'],
                'simple' => 'true',
                'skus' => $data['skus'] ? $data['skus'] : '',
            );
            
            $data = $tmpData;unset($tmpData);
        }

        return $data;
    }

    public function downloadByIIds($iids,$shop_id,&$errormsg)
    {
        $data = parent::downloadByIIds($iids,$shop_id,$errormsg);
        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                # SKU
                if ($value['skus']['sku']) {
                    $value['num'] = 0;
                    foreach ($value['skus']['sku'] as $key=>$sku) {
                        $value['skus']['sku'][$key]['quantity'] = $sku['num'];
                        $value['num'] += $sku['num'];
                    }
                }

                $tmpData[] = array(
                    'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                    'iid' => $value['iid'] ? $value['iid'] : '',
                    'title' => $value['title'] ? $value['title'] : '',
                    'approve_status' => $value['approve_status'] ? $value['approve_status'] : '',
                    'price' => $value['price'],
                    'num' => $value['num'],
                    'skus' => $value['skus'] ? $value['skus'] : '',
                    'simple' => 'true',
                );
            }
            $data = $tmpData;unset($tmpData);
        }
        return $data;
    }

    public function doApproveBatch($approve_status,$shop_id,$check_status=true){
        $request = kernel::single('inventorydepth_shop')->getFrameConf($shop_id);

        if($check_status == true && $request !== 'true'){ 
            $msg = $this->app->_('店铺上下架功能未开启');
            return false;
        }
        
        set_time_limit(0);
        $shop = $this->app->model('shop')->getList('shop_id,name,shop_bn',array('shop_id'=>$shop_id),0,1);
        
        $apiLogModel = app::get('ome')->model('api_log');
        $time = time();
        foreach ($approve_status as $key=>$value) {
            #$result = $this->doApproveSync($value,$shop_id,$msg);
            $approve_status_msg = $value['approve_status']=='onsale' ? '上架' : '下架';

            $result = kernel::single('inventorydepth_rpc_request_shop_frame')->approve_status_update($value,$shop_id);

            $log_status = 'fail';
            if ($result->rsp == 'succ') {
                $msg = $approve_status_msg.'成功！';
                $log_status = 'success';
            }else{
                $msg = $approve_status_msg.'失败！';
                $log_status = 'fail';
            }
            
            $log_id = $apiLogModel->gen_id();
            $params = array($value,$shop_id);
            $data[] = array(
                'log_id' => $log_id,
                'task_name' => $approve_status_msg.'店铺('.$shop[0]['name'].')的【'.($value['bn'] ? $value['bn'] : $value['iid']).'】商品',
                'status' => $log_status,
                'worker' => 'inventorydepth_rpc_request_shop_frame:approve_status_update',
                'params' => serialize($params),
                'msg' => $msg,
                'api_type' => 'request',
                'createtime' => $time,
                'last_modified' => $time,
                'msg_id' => $result->msg_id,
            );

            if ($log_status == 'success') {
                $onsale[] = $value['iid'];
            } else {
                $instock[] = $value['iid'];
            }
        }

        $sql = inventorydepth_func::get_insert_sql($apiLogModel,$data);
        $apiLogModel->db->exec($sql);

        $itemModel = app::get('inventorydepth')->model('shop_items');
        if ($onsale) {
            $itemModel->update(array('approve_status'=>'onsale'),array('iid'=>$onsale,'shop_id'=>$shop_id));
        } elseif($instock) {
            $itemModel->update(array('approve_status'=>'instock'),array('iid'=>$instock,'shop_id'=>$shop_id));
        }
    }
}