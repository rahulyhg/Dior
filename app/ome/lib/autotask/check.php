<?PHP
/**
 * 自动校验
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_check
{
    const BATCH_DIR = 'ome/delivery/check/batch';

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
        $result[$log_id] = array(
            'failNum' => $failNum,
            'failLogiNo' => array(
                array(
                    'createtime' => time(),
                    'logi_no' => $logi_no,
                    'memo' => $msg,
                    'status' => 'fail',
                    'log_id' => $log_id,
                )
            ), 
        );
        $batchLog = $this->app->model('batch_log');
        $batchLog->update(array('fail_number'=>$failNum),array('log_id'=>$log_id));
        
        $batchDetailLog = $this->app->model('batch_detail_log');
        $batchDetailLog->insert($result[$log_id]['failLogiNo'][0]);
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function success($log_id,$logi_no,$succNum) 
    {
        $result[$log_id] = array(
            'succNum' => $succNum,
            'succLogiNo' => array($logi_no), 
        );

        $data = array(
                    'createtime' => time(),
                    'logi_no' => $logi_no,
                    'memo' => '校验成功',
                    'status' => 'success',
                    'log_id' => $log_id,
        );
        $batchDetailLog = $this->app->model('batch_detail_log');
        $batchDetailLog->insert($data);
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

        $this->exec_check($params['log_id'],$params['log_text']);
        return true;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function exec_check($log_id,$logiNoList) 
    {
        if (empty($logiNoList) || !is_array($logiNoList) || !$log_id) return false;

        $now = time();$logiNoList = array_filter($logiNoList);

        $deliBatchLog = $this->app->model('batch_log');
        $op_id = $deliBatchLog->select()->columns('op_id')->where('log_id=?',$log_id)->instance()->fetch_one();
        # 更新状态
        $deliBatchLog->update(array('status'=>'2'),array('log_id'=>$log_id));

        $userModel = app::get('desktop')->model('users');
        $user = $userModel->dump($op_id,'*',array( ':account@pam'=>array('*') ));
        kernel::single('desktop_user')->user_data = $user;
        kernel::single('desktop_user')->user_id = $op_id;
        if ($user['super']) {
            $branches = array('_ALL_');
        } else {
            $branches = $this->getBranchByOp($op_id);
        }

        $deliModel = $this->app->model('delivery');
        $fail = $succ = 0;
        foreach ($logiNoList as $logi_no) {
            $logi_no = trim($logi_no);

            $delivery = kernel::single('ome_delivery_check')->checkAllow($logi_no,$branches,$msg);
            if ($delivery === false) {
                $fail++;
                $this->error($log_id,$logi_no,$msg,$fail);
                continue;
            }

            $transaction = $this->db->beginTransaction();
            $verify = $deliModel->verifyDelivery($delivery);
            if ( !$verify ){
                $msg = '物流单号:'.$delivery['logi_no'].'-发货单号:'.$delivery['delivery_bn'].'::校验失败';
                $fail++;
                $this->error($log_id,$logi_no,$msg,$fail);
                $this->db->rollback();
            }else{
                $succ++;
                $this->success($log_id,$logi_no,$succ);
                $this->db->commit($transaction);
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

}