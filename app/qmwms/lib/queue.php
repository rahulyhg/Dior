<?php
class qmwms_queue{
    
    private $_queue;
    
    private $_ome;
    
    private $_limit = 1000;
    
    private $_huddle = 1500;
    
    private $_method = array(
        'deliveryOrderConfirm' => 'do_delivery',//发货确认
        'returnOrderConfirm'   => 'do_finish',//退货确认
        'deliveryorder.create' =>'deliveryorder.create',//发货单创建
        'returnorder.create'   =>'returnorder.create',//退货单创建
    );
    
    public function __construct(&$app)
    {
        $this->_queue = app::get('qmwms')->model('queue');
        $this->_ome   = app::get('ome');
        $this->objectReship = app::get('ome')->model('reship');
        $this->objectOrder  = app::get('ome')->model('orders');
    }
    
    public function doQueue()
    {   
        if(! $this->canRun()) {
            return true;
        }
        // 设置脚本执行时间
        set_time_limit(0);
        //获取状态为"睡眠"的数据
        $datas = $this->_queue->getList('*', array('status'=>0), 0, $this->_limit);
        //无可执行数据时,将队列状态置为2(未运行)
        if(empty($datas)) {
            return $this->clearCache();
        }
        //批量将状态更新为运行中
        foreach($datas as $ids) {
            $this->begin($ids['id']);
        }
            
        foreach($datas as $data) {
            $id = $data['id'];
            $method     = $data['api_method'];

            $api_params = $data['api_params'];
            list($worker, $api_method) = explode('.', $data['worker']);
            $obj_work = kernel::single($worker);

            if(!isset($this->_method[$method])) {
                $this->end($id, 3);
                continue;
            }

            // 查询发货单创建的对应订单是否是取消状态
            if($this->_method[$method] == 'deliveryorder.create'){
                $sql = "select order_id from sdb_ome_orders where order_id='{$api_params['order_id']}' and (process_status='cancel' or pay_status='6')";
                $orderStatus = $this->objectOrder->db->select($sql);
                if($orderStatus){
                    $this->end($id, 3);
                    continue;
                }
            }
            try{
                if(! $obj_work->$api_method($api_params,$this->_method[$method])) {
                    $this->end($id, 2);    
                    continue;
                }
                $this->end($id, 3);
                // 修改发货单创建的对应订单wms_status
                if($this->_method[$method] == 'deliveryorder.create'){
                    $this->objectOrder->update(array('wms_status' => 'true'), array('order_id'=>$api_params['order_id']));
                }
            }catch(Exception $e) {
                $this->end($id, 2, $e->getMessage());
            }
        }
        
        $this->clearCache();
    }

    /**
     * 重打wms接口
     * @return bool
     */
    public function repeat_push_wms($queueData){

        // 设置脚本执行时间
        set_time_limit(0);

        foreach ($queueData as $item=>$value){
            // 获取需要执行的任务
            $taskInfo = $this->_queue->dump(array('id'=>$value['id']), '*');
            if(empty($taskInfo)){
                continue;
            }
            $id     = $taskInfo['id'];
            $params = $taskInfo['api_params'];
            // 将数据修改为运行中
            //$this->begin($id);
            // 数据处理
            list($worker, $method) = explode('.', $taskInfo['worker']);
            $obj_work = kernel::single($worker);

            if(!method_exists($obj_work, $method)){
                $this->end($id, 3);
                continue;
            }

            if(!isset($this->_method[$taskInfo['api_method']])) {
                $this->end($id, 3);
                continue;
            }

            // 查询发货单创建的对应订单是否是取消状态
            if($this->_method[$taskInfo['api_method']] == 'deliveryorder.create'){
                $sql = "select order_id from sdb_ome_orders where order_id='{$params['order_id']}' and (process_status='cancel' or pay_status='6')";
                $orderStatus = $this->objectOrder->db->select($sql);
                if($orderStatus){
                    $this->end($id, 3);
                    continue;
                }
            }

            try{
                // 请求失败
                if(!$obj_work->$method($params,$taskInfo['api_method'])) {
                    $this->begin($taskInfo['id'], 'failure', '', $taskInfo['repeat_num']);
                    continue;
                }
                // 删除数据
                $this->end($id, 3);
                // 修改发货单创建的对应订单wms_status
                if($this->_method[$taskInfo['api_method']] == 'deliveryorder.create'){
                    $this->objectOrder->update(array('wms_status' => 'true'), array('order_id'=>$params['order_id']));
                }
            }catch(Exception $e) {
                $this->begin($taskInfo['id'], 'failure', $e->getMessage(), $taskInfo['repeat_num']);
            }
        }
    }

    
    public function begin($id,$status='',$msg='',$repeat_num=0)
    {
        if($status == 'failure'){
            $repeat_num = $repeat_num + 1;
            $this->_queue->update(array('status' => '2', 'msg' => $msg, 'repeat_num' => $repeat_num), array('id'=>$id));
        }else{
            $this->_queue->update(array('status'=>1), array('id'=>$id));
        }
    }
    
    public function end($id, $status='2', $msg='') 
    {
        if($status == '2') {
            $this->_queue->update(array('status'=>2, 'msg'=>$msg), array('id'=>$id));
        }else{
            $this->_queue->delete(array('id'=>$id));
        }
    }
    
    public function canRun() {
        $run = $this->getCache();
        
        if(empty($run)) {
            return $this->setCache();
        }else{
            if($run == '1') {
                $count = $this->_queue->count(array('status'=>0));
                if($count >= $this->_huddle) {
                    kernel::single("emailsetting_send")->send("jinrong.zhang@d1m.cn;jun.li@d1m.cn", 'QM队列过于臃肿', 'QM队列过于臃肿');
                }
                return false;
            }else{        
                return $this->setCache();
            }
        }
    }
    
    public function clearCache() {
        $this->_ome->setConf('running', 2);
        return false;
    }
    
    public function setCache() {
        $this->_ome->setConf('running', 1);
        return true;
    }
    
    public function getCache() {
        return $this->_ome->getConf('running');
    }

    
}
?>