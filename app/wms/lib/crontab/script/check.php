<?PHP
/**
 * 批量校验CRONTAB
 *
 * @author chenping@shopex.cn
 * @version $2012-8-13 15:33Z
 */
class wms_crontab_script_check
{
    const BATCH_DIR = 'wms/delivery/check/batch';

    function __construct($app)
    {
        $this->app = $app;
        $this->db = kernel::database();
    }

    /**
     * @description 获取批量发货单物流号
     * @access public
     * @param void
     * @return Array 返回一个批次提交的物流单号
     */
    public function get_logi_no(&$log_id='',&$logiNoList='')
    {
        //取出所有等处理
        $log_ids = array();
        $batchLogModel = app::get('wms')->model('batch_log');
        $rows = $batchLogModel->getList('*',array('log_type'=>'check','status'=>'0'),0,-1,'createtime asc');
        foreach ($rows as $key=>$row) {
            $log_ids[] = $row['log_id'];
            $rows[$key]['log_text'] = unserialize($row['log_text']);
        }

        if ($log_ids) {
            $batchLogModel->update(array('status'=>'2'),array('log_id'=>$log_ids));
        }

        return $rows ? $rows : false;
    }

    /**
     * @description 存储批量发货结果
     * @access public
     * @param void
     * @return void
     */
    public function store_result($result)
    {
        base_kvstore::instance(self::BATCH_DIR)->fetch('result',$data);
        if ($data) {
            $data = array_merge_recursive((array)$data,$result);
        } else {
            $data = $result;
        }

        base_kvstore::instance(self::BATCH_DIR)->store('result',$data);
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
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function deleteResult($log_id)
    {
        base_kvstore::instance(self::BATCH_DIR)->fetch('result',$data);
        $logi_no = $data[$log_id];
        unset($data[$log_id]);
        base_kvstore::instance(self::BATCH_DIR)->store('result',$data);
        return $logi_no;
    }

    /**
     * @description 执行批量发货
     * @access public
     * @param void
     * @return void
     */
    public function exec_batch()
    {
        $rows = $this->get_logi_no();

        if( !$rows ) return false;

        set_time_limit(0);

        foreach ($rows as $key=>$row) {
            $this->process($row['log_id'],$row['log_text']);
            usleep(500000);
        }
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function process($log_id,$logiNoList)
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

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function batchDetailLog($log_id)
    {
        $logiNoList = $this->deleteResult($log_id);
        if(!$logiNoList['failLogiNo']) return false;
        $sql = 'INSERT INTO `sdb_wms_batch_detail_log` (`log_id`,`createtime`,`logi_no`,`memo`,`status`) VALUES';
        $VALUES = array();
        foreach ($logiNoList['failLogiNo'] as $key=>$value) {
            $VALUES[] = <<<EOF
            ("{$log_id}","{$value['createtime']}","{$value['logi_no']}","{$value['memo']}","{$value['status']}")
EOF;
        }
        $sql .= implode(',', $VALUES);

        kernel::database()->exec($sql);
    }

}