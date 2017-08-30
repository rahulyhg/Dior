<?PHP
/**
 * 系统自动审单
 *
 * @Author: ExBOY
 * @Time: 2015-03-09
 * @version 0.1
 */

class ome_autotask_combine
{
    function __construct($app)
    {
        $this->app = $app;
        $this->db = kernel::database();
    }

    /**
     * @description 执行批量自动审单
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
        //set_error_handler(array($this,'combine_error_handler'),E_USER_ERROR | E_ERROR);
        
        $this->exec_combine($params['log_id'], $params['log_text']);
        return  true;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function exec_combine($log_id, $logiNoList, $loginfo = array()) 
    {
        if (empty($logiNoList) || !is_array($logiNoList) || !$log_id)
        {
            return false;
        }
        $logiNoList = array_filter($logiNoList);
        
        
        #[批量日志]处理中
        $deliBatchLog = $this->app->model('batch_log');
        $deliBatchLog->update(array('status'=>'2'),array('log_id'=>$log_id));
        
        
        /*------------------------------------------------------ */
        //-- 系统自动审单处理
        /*------------------------------------------------------ */
        #数据参数处理
        $params    = array();
        foreach ($logiNoList as $key => $val)
        {
            $order_id   = intval($val);
            
            //[获取所有可操作的订单组]合并识别号_合并索引号[order_combine_hash、order_combine_idx]
            $sql        = '';
            $row        = kernel::database()->selectrow("SELECT order_id, process_status, shop_type, is_fail FROM sdb_ome_orders WHERE order_id='".$order_id."' AND op_id IS NULL AND group_id IS NULL");
            
            #只处理未确认订单 && 失败订单不处理
            if($row['process_status'] != 'unconfirmed' || $row['is_fail'] == 'true')
            {
                #[批量日志]已处理
                $fail    = 1;
                $deliBatchLog->update(array('status'=>'1','fail_number'=>$fail),array('log_id'=>$log_id));
                
                return false;
            }
            
            if($row['shop_type'] == 'taobao')
            {
                //淘宝代销订单不合并
                $sql    = "UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',IF(order_source='tbdx',order_id,order_source),'-',ship_tel,'-',shop_type)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type))";
            }
            elseif($row['shop_type'] == 'shopex_b2b')
            {
                //生成一下分销订单 hash
                $sql    = "UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type))";
            }
            elseif($row['shop_type'] == 'dangdang')
            {
                //当当订单如果是货到付款不合并
                $sql    = "UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',IF(is_cod='true',order_id,is_cod),'-',ship_tel,'-',shop_type)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type))";
            }
            elseif($row['shop_type'] == 'amazon')
            {
                //亚马逊如果是非自发货订单不合并
                $sql    = "UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',IF(self_delivery='false',order_id,self_delivery),'-',ship_tel,'-',shop_type)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type))";
            }
            else
            {
                //区分分销类型，生成不同的HASH。生成一下直销订单 hash
                $sql    = "UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod))";
            }
            $sql    .= " WHERE order_id=".$order_id;
            kernel::database()->exec($sql);//订单属性检查:omeauto_auto_plugin_abnormal
            
            $params[]['orders'][]    = $order_id;
        }
        
        //订单预处理
        $msg           = '';
        $preProcessLib = new ome_preprocess_entrance();
        $preProcessLib->process($params, $msg);
        
        //开始自动确认
        $orderAuto = new omeauto_auto_auditing();//new omeauto_auto_combine();
        $result = $orderAuto->process($params);
        
        
        #[批量日志]已处理
        $fail    = $result['fail'];
        $deliBatchLog->update(array('status'=>'1','fail_number'=>$fail),array('log_id'=>$log_id));
        
        return $result;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function error($log_id,$logi_no,$msg,$failNum)
    {
    
    }
    
    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function success($log_id,$logi_no,$succNum)
    {
    
    }
}