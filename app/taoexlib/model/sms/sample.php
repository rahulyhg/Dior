<?php

class taoexlib_mdl_sms_sample extends dbeav_model 
{

    /**
     * 根据模板id获取模板信息
     *
     * @param  $smaple_id 模板id
     * @return array
     * @author 
     **/
    public function getBindBySampleId($sample_id)
    {
    	$res = app::get('taoexlib')->model('sms_bind')->select()->columns()->where('id=?',$sample_id)->instance()->fetch_row();
    	return $res;
    }
    /**
     * 根据模板id获取banding信息
     *
     * @param  $smaple_id 模板id
     * @return array
     * @author 
     **/
    public function getOpenBindBySampleId($sample_id)
    {
        $res = app::get('taoexlib')->model('sms_bind')->select()->columns()->where('id=?',$sample_id)->where('status=?','1')->instance()->fetch_row();
        return $res;
    }


    
    /**
     * 保存短信模板.
     * @param  array $params
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function save_sample($param)
    {
        $template_type = 'register';
        $db = kernel::database();
        $transaction = $db->beginTransaction();
        $op_name = kernel::single('desktop_user')->get_name();
        $param['createtime'] = time();
        $param['op_name'] = $op_name;
        $result = $this->save($param);
        if ($param['id']) {
            
            $items_result = app::get('taoexlib')->model('sms_sample_items')->save($param);
            $api_results = kernel::single('taoexlib_request_sms')->sms_request($template_type,'post',$param);
            if ($api_results['res'] == 'succ') {
                $db->commit($transaction);
                //后续操作
                $tplid = $api_results['data']['tplid'];
                $iid = $param['iid'];
                 if ($tplid) {
                    $db->exec("UPDATE sdb_taoexlib_sms_sample_items SET tplid='".$tplid."' WHERE id=".$param['id']." AND iid=".$iid);
                    $db->exec("UPDATE sdb_taoexlib_sms_sample SET tplid='".$tplid."',approved='0' WHERE id=".$param['id']);
                }
                return true;
            }else{
                 $db->rollBack();
                 return false;
            }
        }
        
    }

     /**
     * 模板状态
     * @param   
     * @return  string
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_status($row)
    {
        $reason = sprintf("<div style='background-color:#a7a7a7;;float:left;'><span style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", '关闭');
        if ($row=='1') {
            $reason = sprintf("<div style='background-color:green;float:left;'><span style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>",  '开启');
           
        }
         return $reason;
        
    }
}