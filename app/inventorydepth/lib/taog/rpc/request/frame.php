<?php
/**
 * 更新商品上下架，RPC实现类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_taog_rpc_request_frame extends ome_rpc_request {

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

    /**
     * 更新商品上下架
     *
     * @param Array $approve_status 上下架参数
     * @param String $shop_id 店铺ID
     * @param Array $addon 附加参数
     * @return Array
     **/
    public function approve_status_list_update($approve_status,$shop_id)
    {
        if(!$approve_status || !$shop_id) return false;
        $this->router->setShopId($shop_id)->approve_status_list_update($approve_status);
/*
        $approve_status_msg = '';
        switch ($approve_status[0]['approve_status']) {
            case 'onsale':
                $approve_status_msg = app::get('inventorydepth')->_('上架');
                break;
            case 'instock':
                $approve_status_msg = app::get('inventorydepth')->_('下架');
                break;
            case 'is_pre_delete':
                $approve_status_msg = app::get('inventorydepth')->_('预删除');
                break;
        }

        $shop_info = app::get('ome')->model('shop')->getList('name',array('shop_id'=>$shop_id),0,1);

        $title = '批量'.$approve_status_msg.'店铺('.$shop_info[0]['name'].')的商品(共'.count($approve_status).'个)';

        //$timeout = 60;

        $params = array(
            'list_quantity' => json_encode($approve_status),
        );

        $callback = array(
            'class' => 'inventorydepth_taog_rpc_request_frame',
            'method' => 'approve_status_update_callback',
        );

        $api_name = 'store.item.approve_status_list.update';

        $return = $this->request($api_name,$params,$callback,$title,$shop_id,10,false,$addon);
        */
        /*
        if ($return !== false){
            app::get('ome')->model('shop')->update(array('last_store_sync_time'=>time()),array('shop_id'=>$shop_id));
        }*/
    }

    /**
     * 上下架回调方法
     *
     * @param Object $result
     * @return void
     * @author
     **/
    public function approve_status_update_callback($result)
    {
        $callback_params = $result->get_callback_params();  // 请求时的参数
        $status          = $result->get_status();           // 返回状态
        $res             = $result->get_result();           // 错误码
        $data            = $result->get_data();             // 返回信息
        $request_params = $result->get_request_params();
        $msg_id = $result->get_msg_id();

        if ($status != 'succ' && $status != 'fail' ){
            $res = $status . ome_api_func::api_code2msg('re001', '', 'public');
        }

        $api_status = ($status == 'succ') ? 'success' : 'fail';

        $log_id = $callback_params['log_id'];
        $oApi_log = app::get('ome')->model('api_log');
        //$log_info = $oApi_log->dump($log_id);
        //$log_params = unserialize($log_info['params']);
        //$log_params = $request_params;

        $list_quantity = json_decode($request_params['list_quantity'],true);
        $approve_status = $list_quantity[0]['approve_status'];
        $approve_status_msg = '';
        switch ($approve_status) {
            case 'onsale':
                $approve_status_msg = app::get('inventorydepth')->_('上架');
                break;
            case 'instock':
                $approve_status_msg = app::get('inventorydepth')->_('下架');
                break;
            case 'is_pre_delete':
                $approve_status_msg = app::get('inventorydepth')->_('预删除');
                break;
        }

        # 错误BN
        $error_bn = $data['error_bn'];
        # 错误结果
        $error_response = $data['error_response'];
        # 无BN
        $no_bn = $data['no_bn'];
        # 成功上下架的BN
        $true_bn = $data['true_bn'];

        # 更新状态
        if ($true_bn) {
            $itemFilter = array(
                'bn' => $true_bn,
                'shop_id' => $callback_params['shop_id'],
            );
            app::get('inventorydepth')->model('shop_items')->update(array('approve_status'=>$approve_status),$itemFilter);
        }

        $msg = array();
        if ($error_bn) {
            $msg[] = '错误货号【'.implode('、', $error_bn).'】';
        }

        if ($no_bn) {
            $msg[] = '无货号【'.implode('、', $no_bn).'】';
        }

        if ($true_bn) {
            $msg[] = '上下架成功货号【'.implode('、', $true_bn).'】';
        }

        $msg = $res ? $res : implode('<br>', $msg);
        $oApi_log->update_log($log_id,$msg,$api_status);

        return array('rsp'=>$status,'res'=>$res,'msg_id'=>$msg_id);
    }

    /**
     * 单个更新上下架
     *
     * @return void
     * @author
     **/
    public function approve_status_update($approve,$shop_id)
    {
        if(!$approve || !$shop_id) return false;
        return $this->router->setShopId($shop_id)->approve_status_update($approve);
        /*        $timeout = 60;

        $shop_info = app::get('ome')->model('shop')->getList('name',array('shop_id'=>$shop_id),0,1);

        $title = '更新店铺('.$shop_info[0]['name'].')的('.$approve['title'].')商品上下架状态';

        $params['iid'] = $approve['iid'];
        $params['approve_status'] = $approve['approve_status'];

        if($approve['approve_status'] == 'onsale') $params['num'] = $approve['num'];

        if($approve['outer_id']) $params['outer_id'] = $approve['outer_id'];

        $api_name = 'store.item.approve_status.update';

        return $this->call($api_name,$params,$shop_id,$timeout); */
    }
}
