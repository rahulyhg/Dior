<?php
class ome_api_log_to_stock {

    //新发起的同步请求
    function save($stocks,$shop_id){
        if(!$stocks) {
            return false;
        }
		$oApiStockLog = &app::get('ome')->model('api_stock_log');
        $task_name = '同步库存';
        if($shop_id){
			$shop_info = app::get('ome')->model('shop')->dump($shop_id,'name');
		}
        foreach($stocks as $v) {
            if($shop_info) {
                $task_name = '同步店铺('.$shop_info['name'].')的库存('.$v['bn'].')';
            }
            $product_info = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'product_id,name');

            //$data['params'] = json_encode($stocks);
            $data['store'] = $v['quantity'];
            $data['status'] = 'running';
            $data['createtime'] = time();

            $tmp_crc32_code = sprintf('%u', crc32($shop_id."-".$v['bn']));

            $tmp_info = $oApiStockLog->dump(array('crc32_code'=>$tmp_crc32_code),'product_id');
            if(!$tmp_info){
                $data['shop_id'] = $shop_id;
                $data['shop_name'] = $shop_info['name'];
                $data['task_name'] = $task_name;
                $data['product_id'] = $product_info['product_id'];
                $data['product_name'] = $product_info['name'];
                $data['product_bn'] = $v['bn'];
                $data['worker'] = 'ome_sync_product.sync_stock';
                $data['crc32_code'] = $tmp_crc32_code;

                $oApiStockLog->save($data);
            }else{
                $data['msg'] = '';
                $data['msg_id'] = '';
                $data['memo'] = '';
                // $data['last_modified'] = '';
                $oApiStockLog->update($data,array('crc32_code'=>$tmp_crc32_code));
            }
            unset($tmp_info);
            unset($data);
        }
	}

    //同步请求返回的数据
    function save_callback($bn,$status,$shop_id,$msg,$log_detail){
        $oApiStockLog = &app::get('ome')->model('api_stock_log');

        if($msg == '更新部分库存失败'){
            $status = 'success';
        }
        if($status=='success') {
            $msg = '更新成功';
        }
        $data['msg_id'] = $log_detail['msg_id'];
        //$data['memo'] = $log_detail['params'];
        $data['msg'] = $msg;
        $data['status'] = $status;
        $data['last_modified'] = time();

        //组织要更新的货号，一次性更新
        foreach($bn as $v) {
            $crc32_code[] = sprintf('%u', crc32($shop_id."-".$v['bn']));
        }

        $oApiStockLog->update($data,array('crc32_code'=>$crc32_code));
    }
}
