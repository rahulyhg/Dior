<?PHP
/**
 * 自动发货
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_consign
{
    const BATCH_DIR = 'ome/delivery/consign/batch';

    function __construct($app)
    {
        $this->app = $app;
        $this->db = kernel::database();
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function error($log_id,$logi_no,$msg,$failNum) 
    {
        $detail_log = array(
            'createtime' => time(),
            'logi_no' => $logi_no,
            'memo' => $msg,
            'status' => 'fail',
            'log_id' => $log_id,
        );

        $batchLog = $this->app->model('batch_log');
        $batchLog->update(array('fail_number'=>$failNum),array('log_id'=>$log_id));
        
        $batchDetailLog = $this->app->model('batch_detail_log');
        $batchDetailLog->insert($detail_log);

        // 从数组中剔除
        $key = array_search($logi_no,(array) $this->_log_text);
        if ($key !== false) {
            unset($this->_log_text[$key]);
        }
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function success($log_id,$logi_no,$succNum) 
    {
        $data = array(
            'createtime' => time(),
            'logi_no' => $logi_no,
            'memo' => '发货成功',
            'status' => 'success',
            'log_id' => $log_id,
        );
        $batchDetailLog = $this->app->model('batch_detail_log');
        $batchDetailLog->insert($data);

        // 从数组中剔除
        $key = array_search($logi_no,(array) $this->_log_text);
        if ($key !== false) {
            unset($this->_log_text[$key]);
        }
    }

    /**
     * @description 执行批量发货
     * @access public
     * @param void
     * @return void
     */
    public function process($params, &$error_msg='') 
    {
        if( (!$params['log_id']) || (!$params['log_text']) ){
            return false;
        }else{
            $params['log_text'] = unserialize($params['log_text']);
        }
        
        set_time_limit(240);
        set_error_handler(array($this,'consign_error_handler'),E_USER_ERROR | E_ERROR);

        $this->exec_consign($params['log_id'],$params['log_text']);
        return  true;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function exec_consign($log_id,$logiNoList,$loginfo = array()) 
    {
        #[发货配置]是否启动拆单 ExBOY
        $deliModel      = $this->app->model('delivery');
        $split_seting   = $deliModel->get_delivery_seting();
        
        $minWeight = $this->app->getConf('ome.delivery.minWeight');
        #计算商品重量
        $orderObj = &$this->app->model('orders');
        $deliveryOrderObj = &$this->app->model('delivery_order');
        if (empty($logiNoList) || !is_array($logiNoList) || !$log_id) return false;

        $now = time();$logiNoList = array_filter($logiNoList);
        $productsObj = &$this->app->model('products');
        $deliBatchLog = $this->app->model('batch_log');
        $operation = $deliBatchLog->select()->columns('op_id,op_name')->where('log_id=?',$log_id)->instance()->fetch_row();
        $op_id = $operation['op_id'];

        # 更新状态
        //$deliBatchLog->update(array('status'=>'2'),array('log_id'=>$log_id));

        $userModel = app::get('desktop')->model('users');
        $user = $userModel->dump($op_id,'*',array( ':account@pam'=>array('*') ));
        kernel::single('desktop_user')->user_data = $user;
        kernel::single('desktop_user')->user_id = $op_id;
        if ($user['super']) {
            $branches = array('_ALL_');
        } else {
            $branches = $this->getBranchByOp($op_id);
        }
        $deliBillModel = $this->app->model('delivery_bill');
        //$deliModel = $this->app->model('delivery');
        $opLogModel = $this->app->model('operation_log');

        $fail = $loginfo['fail_number'] ? $loginfo['fail_number'] : 0;
        $succ = 0;
        foreach ($logiNoList as $logi_no) {
            $logi_no = trim($logi_no);
            $this->_logino = $logi_no;
            $this->_log_id = $log_id;
            $this->_fail = $fail;

            $delivery = kernel::single('ome_delivery_consign')->deliAllow($logi_no,$branches,$msg,$patch);
            if ($delivery === false) {
                $fail++;
                $this->error($log_id,$logi_no,$msg,$fail);
                continue;
            }
            $this->_delivery = $delivery;

            //$transaction = $this->db->beginTransaction();
            
            //-- 包裹重量:如果明细下有一个商品重量为0重量取系统设置重量,否则为商品明细累加
            $delivery_order = $deliveryOrderObj->getList('order_id',array('delivery_id'=>$delivery['delivery_id']));
            $weight = 0;
            foreach($delivery_order as $item){
                
              #[拆单]根据发货单中货品详细读取重量 ExBOY
              if(!empty($split_seting))
              {
                  $orderWeight  = $deliModel->getDeliveryWeight($item['order_id'], array(), $delivery['delivery_id']);
              }
              else 
              {
                $orderWeight = $orderObj->getOrderWeight($item['order_id']);
              }
              
                if($orderWeight==0) break;
                
                $weight += $orderWeight;
            }

            //-- 商品重量有取商品重量
            if ($weight <= 0) {
                $weight = $minWeight > 0 ? $minWeight : 0;
            }

            //-- 多包裹
            if ($delivery['logi_number'] > 1) {
                if ($delivery['delivery_logi_number'] == $delivery['logi_number'] || ($delivery['delivery_logi_number']+1) == $delivery['logi_number']) {
                    $patchWeight = kernel::single('ectools_math')->number_multiple(array(floatval($minWeight),($delivery['logi_number']-1)));
                    $weight = kernel::single('ectools_math')->number_minus(array($weight,$patchWeight));
                    $weight = $weight > 0 ? $weight : floatval($minWeight); 
                }
            }
            
            //-- 扫描包裹等于快递单数量
            if ($delivery['delivery_logi_number'] == $delivery['logi_number'] && $delivery['status']<>'succ') {
                if (!$deliModel->consignDelivery($delivery['delivery_id'], $weight , $msg)) {
                    $fail++;
                    $this->error($log_id,$logi_no,$msg,$fail);
                    //$this->db->rollback();
                } else {
                    $succ++;
                    $this->success($log_id,$logi_no,$succ);
                    //$this->db->commit($transaction);
                }
                continue;
            }
        
            //-- 更新发货包裹数
            $delivery_logi_number = $delivery['delivery_logi_number'] + 1;
            $deliUpdate = array(
                'delivery_logi_number'=>$delivery_logi_number,
            );
            $deliFilter = array('delivery_id'=>$delivery['delivery_id']);
            $deliModel->update($deliUpdate,$deliFilter);

            if ($patch) {
                # 计算物流费
                list($mainload,$ship_area,$area_id) = explode(':',$delivery['ship_area']);

                $delivery_cost_actual = $deliModel->getDeliveryFreight($area_id,$delivery['logi_id'],floatval($minWeight));
                $data = array(
                    'status'=>'1',
                    'weight'=>floatval($minWeight),
                    'delivery_cost_actual'=>$delivery_cost_actual,
                    'delivery_time'=>$now,
                );
                $filter = array('logi_no'=>$logi_no);
                $deliBillModel->update($data,$filter);

                # 日志
                $logstr = '批量发货,单号:'.$logi_no;
                $opLogModel->write_log('delivery_bill_express@ome', $delivery['delivery_id'], $logstr,$now,$operation);
    
                if($delivery['logi_number'] == $delivery_logi_number){
                    define('FRST_TRIGGER_OBJECT_TYPE','发货单：批量发货');
                    define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：batchConsign');
                    if (!$deliModel->consignDelivery($delivery['delivery_id'], $weight, $msg)) {
                        $msg = '物流单号:'.$delivery['logi_no'].'-发货单号:'.$delivery['delivery_bn'].'::'.$msg;
                        $fail++;
                        $this->error($log_id,$logi_no,$msg,$fail);
                        //$this->db->rollback();
                    } else {
                        $succ++;
                        $this->success($log_id,$logi_no,$succ);
                        //$this->db->commit($transaction);
                    }
                } else {
                    # 部分发货
                    $succ++;
                    $this->success($log_id,$logi_no,$succ);
                    //$this->db->commit($transaction);
                }
            } else {

                if($delivery['logi_number'] == $delivery_logi_number){
                    if (!$deliModel->consignDelivery($delivery['delivery_id'], $weight, $msg)) {
                        $fail++;
                        $this->error($log_id,$logi_no,$msg,$fail);
                        //$this->db->rollback();
                    } else {
                        $succ++;
                        $this->success($log_id,$logi_no,$succ);
                        //$this->db->commit($transaction);
                    }
                } else {
                    $succ++;
                    $this->success($log_id,$logi_no,$succ);
                    //$this->db->commit($transaction);
                }
            }
            usleep(200000);
        }

        $deliBatchLog->update(array('status'=>'1','fail_number'=>$fail),array('log_id'=>$log_id));
    }

    /** 
     * @description 获取操作员可执行的库存
     * @access public
     * @param void
     * @return void
     */
    public function getBranchByOp($op_id) 
    {
        $bps = array();
        $oBops = $this->app->model('branch_ops');
        $bops_list = $oBops->getList('branch_id', array('op_id' => $op_id), 0, -1);
        if ($bops_list){
            $bps = array_map('current',$bops_list);
        }

        return $bps;
    }
    
    /**
     * @description 捕获发货异常信息
     * @access public
     * @param void
     * @return void
     */
    public function consign_error_handler($errno, $errstr, $errfile, $errline) 
    {
        $batchLogModel = $this->app->model('batch_log');
        $batchLogModel->db->rollBack();

        $fail = $this->_fail+1;
        $this->error($this->_log_id,$this->_logino,$errstr,$fail);
        if ($this->_delivery['delivery_id']) {
            $deliModel = $this->app->model('delivery');
            $deliModel->update(array('delivery_logi_number'=>0),array('delivery_id'=>$this->_delivery['delivery_id']));
        }

        
        $log_text = serialize((array)$this->_log_text);
        if ($this->_log_id) {
            $data = array('log_text'=>$log_text);
            if (empty($this->_log_text)) {
                $data['status'] = '1';
            }
            $batchLogModel->update($data,array('log_id'=>$this->_log_id));
        }
        
        die(0);
    }
    
}