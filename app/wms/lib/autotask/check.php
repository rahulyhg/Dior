<?PHP
/**
 * 批量校验CRONTAB
 *
 * @author chenping@shopex.cn
 * @version $2012-8-13 15:33Z
 */
class wms_autotask_check
{
    const BATCH_DIR = 'wms/delivery/check/batch';

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
        $batchLog = app::get('wms')->model('batch_log');
        $batchLog->update(array('fail_number'=>$failNum),array('log_id'=>$log_id));

        $batchDetailLog = app::get('wms')->model('batch_detail_log');
        $batchDetailLog->insert($result[$log_id]['failLogiNo'][0]);
        //$this->store_result($result);
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
        $batchDetailLog = app::get('wms')->model('batch_detail_log');
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

        $deliBatchLog = app::get('wms')->model('batch_log');
        $deliModel = app::get('wms')->model('delivery');
        $dlyBillObj = kernel::single('wms_delivery_bill');
        $dlyCheckLib = kernel::single('wms_delivery_check');
        $dlyProcessLib = kernel::single('wms_delivery_process');

        $fail = $succ = 0;
        foreach ($logiNoList as $logi_no) {
            $logi_no = trim($logi_no);

            $delivery = $dlyCheckLib->checkAllow($logi_no, $msg, true);
            if ($delivery === false) {
                $fail++;
                $this->error($log_id,$logi_no,$msg,$fail);
                continue;
            }

            //$transaction = $this->db->beginTransaction();
            $delivery_id = $dlyBillObj->getDeliveryIdByPrimaryLogi($logi_no);
            $verify = $dlyProcessLib->verifyDelivery($delivery_id);
            if ( !$verify ){
                $msg = '物流单号:'.$delivery['logi_no'].'-发货单号:'.$delivery['delivery_bn'].'::校验失败';
                $fail++;
                $this->error($log_id,$logi_no,$msg,$fail);
                //$this->db->rollback();
            }else{
                $succ++;
                $this->success($log_id,$logi_no,$succ);
                //$this->db->commit($transaction);
            }
            usleep(200000);
        }

        $deliBatchLog->update(array('status'=>'1','fail_number'=>$fail),array('log_id'=>$log_id));
    }

}