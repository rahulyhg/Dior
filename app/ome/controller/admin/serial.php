<?php
class ome_ctl_admin_serial extends desktop_controller{
    var $name = "收货";
    var $workground = "aftersale_center";

    function search(){
        $this->pagedata['tag'] = false;
        if($_POST['serial_number']){
            $serial_number = $_POST['serial_number'];
            $serial['merge'] = $this->app->getConf('ome.product.serial.merge');//false
            $serial['separate'] = $this->app->getConf('ome.product.serial.separate');//null
            if($serial['merge']=='true' && $pos = strpos($serial_number,$serial['separate'])){
                $serial_number = substr($serial_number,$pos+1);
            }

            $userObj = app::get('desktop')->model('users');
            $productObj = $this->app->model('products');
            $branchObj = $this->app->model('branch');
            $serialObj = $this->app->model('product_serial');
            $serialLogObj = $this->app->model('product_serial_log');

            $data = $serialObj->dump(array('serial_number'=>$serial_number));
            if($data && $data['item_id']>0){
                $product = $productObj->dump($data['product_id'],'name');
                $branch = $branchObj->dump($data['branch_id'],'name');
                $data['product_name'] = $product['name'];
                $data['branch_name'] = $branch['name'];
                switch($data['status']){
                    case 0:
                        $data['status'] = '已入库';
                        break;
                    case 1:
                        $data['status'] = '已出库';
                        break;
                    case 2:
                        $result['serial_status'] = '无效';
                        break;
                }

                $logData = $serialLogObj->getList('*',array('item_id'=>$data['item_id']),0,-1,'act_time DESC');
                foreach($logData as $key=>$val){
                    $logStatus = array();
                    $logStatus = $this->log_status($val);
                    $logData[$key]['act_type'] = $logStatus['act_type'];
                    $logData[$key]['bill_type'] = $logStatus['bill_type'];
                    $logData[$key]['bill_no'] = $logStatus['bill_no'];
                    $logData[$key]['orderBn'] = $logStatus['orderBn'];
                    $logData[$key]['serial_status'] = $logStatus['serial_status'];

                    if($val['act_owner'] == 16777215){
                        $logData[$key]['act_owner'] = 'system';
                    }else{
                        $user = $userObj->dump($val['act_owner'],'name');
                        $logData[$key]['act_owner'] = $user['name'];
                    }
                }
            }

            $this->pagedata['serial_number'] = $_POST['serial_number'];
            $this->pagedata['data'] = $data;
            $this->pagedata['tag'] = true;
            $this->pagedata['logData'] = $logData;
        }
        $this->page("admin/serial/search.html");
    }

    function log_status($data){
        if($data['act_type']>=0){
            switch($data['act_type']){
                case 0:
                    $result['act_type'] = '出库效验';
                    break;
                case 1:
                    $result['act_type'] = '入库效验';
                    break;
            }
        }

        if($data['bill_type']>=0){
            $orderObj = $this->app->model('orders');
            switch($data['bill_type']){
                case 0:
                    $result['bill_type'] = '发货单';
                    if($data['bill_no'] && $data['bill_no'] != ''){
                        $deliveryObj = $this->app->model('delivery');
                        $delivery = $deliveryObj->dump($data['bill_no'],'delivery_bn,process');
                        $result['bill_no'] = $delivery['delivery_bn'];
                        if($delivery['process']=='true'){
                            $orderIds = $deliveryObj->getOrderIdByDeliveryId($data['bill_no']);
                            $orders = $orderObj->getList('order_id,order_bn',array('order_id'=>$orderIds));
                            foreach($orders as $key=>$val){
                                $orderBn[$val['order_id']] = $val['order_bn'];
                            }
                            $result['orderBn'] = $orderBn;
                        }
                    }
                    break;
                case 1:
                    $result['bill_type'] = '售后申请单';
                    if($data['bill_no'] && $data['bill_no'] != ''){
                        $processObj = $this->app->model('reship');
                        $process = $processObj->dump($data['bill_no'],'order_id,reship_bn');
                        $order = $orderObj->dump($process['order_id'],'order_bn');
                        $result['orderBn'] = $order['order_bn'];
                        $result['bill_no'] = $process['process'];
                    }
                    break;
            }
        }

        if($data['serial_status']>=0){
            switch($data['serial_status']){
                case 0:
                    $result['serial_status'] = '入库';
                    break;
                case 1:
                    $result['serial_status'] = '出库';
                    break;
                case 2:
                    $result['serial_status'] = '无效';
                    break;
            }
        }

        return $result;
    }

    function ajaxCheckSerial(){
        $serialObj = &$this->app->model('product_serial');
        $filter['serial_number'] = $_POST['serial'];
        //$filter['bn'] = $_POST['bn'];
        $serialData = $serialObj->dump($filter);
        if($serialData['item_id']>0 && $serialData['status']==1){
            echo json_encode(array('result' => 'false', 'msg'=>'此唯一码的货品已经出库，无法通过效验'));
        }else{
            echo json_encode(array('result' => 'true', 'msg'=>'OK'));
        }
    }

    function ajaxSerialData(){
        $serialObj = &$this->app->model('product_serial');
        $serialLogObj = $this->app->model('product_serial_log');
        $deliveryObj = $this->app->model('delivery');

        $filter['serial_number'] = $_POST['serial'];
        $order_id = $_POST['order_id'];
        $serialData = $serialObj->dump($filter);
        if($serialData['item_id']>0 && $serialData['status']==1){
            $logData = $serialLogObj->getList('*',array('item_id'=>$serialData['item_id'],'act_type'=>0,'bill_type'=>0),0,1,'act_time DESC');
            $deliveryIds = $deliveryObj->getDeliverIdByOrderId($order_id);
            if($logData[0]['bill_no']>0 && count($deliveryIds)>0 && in_array($logData[0]['bill_no'],$deliveryIds)){
                echo json_encode($serialData);
            }else{
                echo '';
            }
        }else{
            echo '';
        }
    }

    

    /**
     *唯一码列表
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function serial_list()
    {
        $base_filter = array('serarch_like'=>'1');
            
        $params = array(
            'use_buildin_recycle'=>false, 
            'use_buildin_export'=>true,
            'title'=>'唯一码列表',
            'use_buildin_filter' => true,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'base_filter' => $base_filter,
        );
        $this->filter_use_like = true;
        $this->finder('ome_mdl_product_serial',$params);
    }

    

    
}