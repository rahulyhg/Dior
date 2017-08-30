<?php

/**
 * 更新短信模板
 * @package     main
 * @subpackage  classes
 * @author cyyr24@sina.cn
 */
class taoexlib_rpc_sms
{
    
    /**
     * 更新短信审核状态
     * @param   array result
     * @return bool
     * @access  public
     * @author cyyr24@sina.cn
     */
    function sms_callback($result)
    {
        $result = $_POST;

        $reason = $result['reason'];
        $tplid= $result['tplid'];
        $status = $result['status'];
        $db = kernel::database();
        // status=0｜1｜2(拒绝｜通过｜等待审核),
        $sqlstr=array();
        if (in_array($status,array('0','1'))) {
            if ($status == '0') {
                $approved = '2';
                $sqlstr[]="sync_reason='".$reason."'";
            }else if($status == '1'){
                $approved = '1';
            }
            $approved_at = $re['approved_at'];
             $sqlstr[]="approved='".$approved."',approvedtime=".$approved_at;
            if ($sqlstr) {
                $sqlstr = implode(',',$sqlstr);
                $db->exec("UPDATE sdb_taoexlib_sms_sample_items SET ".$sqlstr." WHERE tplid='".$tplid."'");
                $db->exec("UPDATE sdb_taoexlib_sms_sample SET approved='".$approved."' WHERE tplid='".$tplid."'");
            }
            
        }
        echo 'OK';
        return true;
    }
} 

?>