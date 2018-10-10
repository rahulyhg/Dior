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
            // 创建订单状态默认传create
            $response = $obj_work->createOrder($params['order_bn'], 'create', array('createOrder' => $params), $orderInfo['shop_id'], $id);
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

    /**
     * 导出功能
     */
    public function order_status_export(){

        if(empty($_POST)){  // 默认给3天的时间
            $date  = date("Y-m-d", time());
            $this->pagedata['time_from'] = strtotime("$date -3 day");
            $this->pagedata['time_to']   = strtotime($date);
        }else{
            $this->pagedata['time_from'] = strtotime($_POST['time_from']);
            $this->pagedata['time_to']   = strtotime($_POST['time_to']);
        }

        $this->pagedata['form_action'] = 'index.php?app=ome&ctl=admin_kafka_log&act=do_order_status_export';
        $this->pagedata['path']        = '订单历史状态';
        $this->page("admin/kafka/order_status.html");
    }

    public function do_order_status_export(){
        $this->begin();
        // 判断请求数据
        if(!isset($_GET['time_from']) || !isset($_GET['time_to'])){
            $this->end(false, '请提交查询日期');
        }
        if($_GET['time_from'] > $_GET['time_to']){
            $this->end(false, '开始时间不能大于结束时间');
        }
        // 执行导出任务
        kernel::single('ome_kafka_kafkaQueueHandle')->orderStatusExcel($_GET['time_from'], $_GET['time_to']);
        // csv格式
//        kernel::single('ome_kafka_kafkaQueueHandle')->orderStatusExcel('2018-7-11', '2018-7-14');
        // excel格式
//        kernel::single('ome_kafka_kafkaQueueHandle')->order_history_status_xls('2018-7-11', '2018-7-14');
    }

    public function set_conf(){
        // 获取缓存信息
        $KafkaConf = app::get('ome')->getConf('KafkaConf');
        $this->pagedata['form_action'] = 'index.php?app=ome&ctl=admin_kafka_log&act=do_set_conf';
        $this->pagedata['path']        = 'Kafka配置';
        $this->pagedata['conf']        = unserialize($KafkaConf);
        $this->page("admin/kafka/kafka_conf.html");
    }

    public function do_set_conf(){
        
        if(!$_POST){
            $this->splash(false, $this->_action_url, '保存失败-数据有误', 'redirect');
        }
        
        if(empty($_POST['app_key'])){
            $this->splash(false, $this->_action_url, 'app_key不能为空', 'redirect');
        }

        if(empty($_POST['secret_key'])){
            $this->splash(false, $this->_action_url, 'secret_key不能为空', 'redirect');
        }

        if(empty($_POST['api_url'])){
            $this->splash(false, $this->_action_url, '接口地址不能为空', 'redirect');
        }
        $confData = array(
            'app_key'    => $_POST['app_key'],
            'secret_key' => $_POST['secret_key'],
            'api_url'    => $_POST['api_url'],
        );
        // 存入缓存
        $res = app::get('ome')->setConf('KafkaConf', serialize($confData));

        if($res){
            $this->splash(true, $this->_action_url, '保存成功', 'redirect');
        }else{
            $this->splash(false, $this->_action_url, '保存失败', 'redirect');
        }
    }
}
