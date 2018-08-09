<?php
/**
 * Created by PhpStorm.
 * User: august.yao
 * Date: 2018/08/07
 * Time: 10:15
 */
class ome_ctl_admin_kafka_log extends desktop_controller
{
    public function index()
    {
//        $obj_work = kernel::single('ome_kafka_kafkaQueueHandle');

//        $obj_work->getExcel('2016-03','2017-02');
//        $obj_work->getExcel('2017-03','2018-02');
//        $obj_work->getExcel('2018-03','2018-08');
//        $obj_work->getExcel('2018-07','2018-08');
//        $obj_work->order_history_xls('2018-07','2018-08');
//        $obj_work->orderStatusExcel('2018-07','2018-08');
//        die;
        
        $base_filter = array();
        $params = array(
            'title'                  => 'kafka api日志列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_filter'     => true,
            'base_filter'            => $base_filter,
            'use_buildin_recycle'    => false,
        );
        $this->finder('ome_mdl_kafka_api_log', $params);
    }

    /**
     * kafka接口重发
     */
    public function repeat(){

        $this->begin();
        // 数据id
        $id = (int)$_GET['id'];
        // 查询数据
        $log_data = app::get('ome')->model('kafka_api_log')->dump(array('id' => $id), '*');
        $params   = json_decode($log_data['http_request_data']['params'], true);

        // 判断请求数据
        if($log_data['api_status'] != 'fail'){
            $this->end(false, '接口已调用成功，请刷新页面');
        }

        if(!in_array($log_data['http_request_data']['method'], array('10001','10002'))){
            $this->end(false, '接口请求数据有误');
        }

        $orderInfo= app::get('ome')->model('orders')->dump(array('order_bn'=>$params['order_bn']), 'shop_id,order_id');
        // 引入文件
        $obj_work = kernel::single('ome_kafka_api');
        // 状态推送
        if($log_data['http_request_data']['method'] == '10002'){
            $response = $obj_work->sendOrderStatus($params['order_bn'], $params['status'], $params, $orderInfo['shop_id'], $id);
        }
        // 创建订单
        if($log_data['http_request_data']['method'] == '10001'){
            $response = $obj_work->createOrder($params['order_bn'], $params['status'], $params, $orderInfo['shop_id'], $id);
        }

        // 更新重发次数
        app::get('ome')->model('kafka_api_log')->db->exec(
            "update sdb_ome_kafka_api_log set repeat_num=repeat_num+1 where id='$id'"
        );

        if($response['success']){
            $this->end(true, '请求成功');
        }else{
            $this->end(true, '请求失败:' . $response['msg']);    // todo:如果有错误信息
        }
    }
}
