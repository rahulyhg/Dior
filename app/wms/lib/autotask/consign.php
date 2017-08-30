<?PHP
/**
 * 批量发货CRONTAB
 *
 * @author chenping@shopex.cn
 * @version $2012-8-13 15:33Z
 */
class wms_autotask_consign
{
    const BATCH_DIR = 'wms/delivery/consign/batch';

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

        $batchLog = app::get('wms')->model('batch_log');
        $batchLog->update(array('fail_number'=>$failNum),array('log_id'=>$log_id));

        $batchDetailLog = app::get('wms')->model('batch_detail_log');
        $batchDetailLog->insert($detail_log);

        // 从数组中剔除
        $key = array_search($logi_no,(array)$this->_log_text);
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
        $batchDetailLog = app::get('wms')->model('batch_detail_log');
        $batchDetailLog->insert($data);

        // 从数组中剔除
        $key = array_search($logi_no,(array)$this->_log_text);
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
        $minWeight = app::get('wms')->getConf('wms.delivery.minWeight');
        #计算商品重量
        $orderObj = &app::get('ome')->model('orders');
        $deliveryOrderObj = &app::get('ome')->model('delivery_order');
        if (empty($logiNoList) || !is_array($logiNoList) || !$log_id) return false;

        $now = time();$logiNoList = array_filter($logiNoList);
        $productsObj = &app::get('ome')->model('products');
        $deliBatchLog = app::get('wms')->model('batch_log');

        $operation = $deliBatchLog->select()->columns('op_id,op_name')->where('log_id=?',$log_id)->instance()->fetch_row();
        $op_id = $operation['op_id'];

        $deliBillModel = app::get('wms')->model('delivery_bill');
        $deliModel = app::get('wms')->model('delivery');
        $opLogModel = app::get('ome')->model('operation_log');

        $dlyCheckLib = kernel::single('wms_delivery_check');
        $dlyProcessLib = kernel::single('wms_delivery_process');
        $dlyBillLib = kernel::single('wms_delivery_bill');
        $wmsCommonLib = kernel::single('wms_common');


        $fail = $loginfo['fail_number'] ? $loginfo['fail_number'] : 0;
        $succ = 0;
        foreach ($logiNoList as $logi_no) {
            $logi_no = trim($logi_no);
            $this->_logino = $logi_no;
            $this->_log_id = $log_id;
            $this->_fail = $fail;

            $delivery_id = $dlyBillLib->getDeliveryIdByPrimaryLogi($logi_no);
            if(!is_null($delivery_id)){
                $primary = true;
                $dly = $deliModel->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
            }else{
                $delivery_id = $dlyBillLib->getDeliveryIdBySecondaryLogi($logi_no);
                if(!is_null($delivery_id)){
                    $secondary = true;
                    $dly = $deliModel->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
                }
            }

            $weight = $dly['net_weight'] ? $dly['net_weight'] : 0.00;
            $weight = $weight ? $weight : $minWeight;

            if($primary){
                //计算实际运费
                $area = $dly['consignee']['area'];
                $arrArea = explode(':', $area);
                $area_id = $arrArea[2];
                $delivery_cost_actual = $wmsCommonLib->getDeliveryFreight($area_id,$dly['logi_id'],$minWeight);
            }

            $delivery = $dlyCheckLib->consignAllow('', $logi_no, false, true);
            if ($delivery) {
                $fail++;
                $this->error($log_id,$logi_no,$delivery,$fail);
                continue;
            }
	    
	    $this->_delivery = $dly;

            //-- 扫描包裹等于快递单数量
            if ($dly['delivery_logi_number'] == $dly['logi_number'] && $dly['status']<> 3) {
                if (!$dlyProcessLib->consignDelivery($dly['delivery_id'])) {
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

            //更新发货包裹数
            $delivery_logi_number = $dly['delivery_logi_number'] + 1;
            $deliUpdate = array(
                'delivery_logi_number'=>$delivery_logi_number,
            );

            $deliFilter = array('delivery_id'=>$dly['delivery_id']);
            $deliModel->update($deliUpdate,$deliFilter);

            if ($secondary) {

                $data = array(
                    'status'=> 1,
                    'weight'=> 0.00,
                    'delivery_cost_actual'=> 0.00,
                    'delivery_time'=>$now,
                );
                $filter = array('logi_no'=>$logi_no);
                $deliBillModel->update($data,$filter);

                # 日志
                $logstr = '批量发货,单号:'.$logi_no;
                $opLogModel->write_log('delivery_bill_express@wms', $dly['delivery_id'], $logstr,$now,$operation);

                if($dly['logi_number'] == $delivery_logi_number){
                    define('FRST_TRIGGER_OBJECT_TYPE','发货单：批量发货');
                    define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：batchConsign');
                    if (!$dlyProcessLib->consignDelivery($dly['delivery_id'])) {
                        $msg = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'].'::'.$msg;
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
                $data = array(
                    'status'=> 1,
                    'weight'=> $weight,
                    'delivery_cost_actual'=> $delivery_cost_actual,
                    'delivery_time'=>$now,
                );
                $filter = array('logi_no'=>$logi_no);
                $deliBillModel->update($data,$filter);

                $deliUpdate = array(
    				'weight'=> $weight,
                    'delivery_cost_actual'=> $delivery_cost_actual,
                );
                $deliFilter = array('delivery_id'=>$dly['delivery_id']);
                $deliModel->update($deliUpdate,$deliFilter);

                if($dly['logi_number'] == $delivery_logi_number){
                    if (!$dlyProcessLib->consignDelivery($dly['delivery_id'])) {
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