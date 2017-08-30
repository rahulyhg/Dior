<?php
class taoexlib_mdl_sms_bind extends dbeav_model 
{

    /**
     * 根据规则短信发送的内容
     *
     * @param  $rule_id 规则ID
     * @return string
     * @author 
     **/
    public function getSmsContentByRuleId($rule_id=null)
    {
       
        if($rule_id){
            $sql = "SELECT * FROM sdb_taoexlib_sms_bind AS b LEFT JOIN sdb_taoexlib_sms_sample AS s ON b.id = s.id WHERE b.status ='1' and b.is_send ='1' and b.tid =".$rule_id." ";
            $rows = $this->db->select($sql);
            if(!$rows[0]['bind_id']){
                $sql = "SELECT * FROM sdb_taoexlib_sms_bind AS b LEFT JOIN sdb_taoexlib_sms_sample AS s ON b.id = s.id WHERE b.status ='1' and b.is_send ='1' and b.is_default ='1' ";
                $rows = $this->db->select($sql);
            }
        }else{
            $sql = "SELECT * FROM sdb_taoexlib_sms_bind AS b LEFT JOIN sdb_taoexlib_sms_sample AS s ON b.id = s.id WHERE b.status ='1' and b.is_send ='1' and b.is_default ='1' ";
            $rows = $this->db->select($sql);
        }
        $id = $rows[0]['id'];

        $items = $this->db->selectrow('SELECT * FROM sdb_taoexlib_sms_sample_items WHERE id='.$id.' AND approved=\'1\' AND status=\'1\' ORDER BY iid DESC');

        return $items;
    }
     /**
     * 根据规则获取模板信息
     *
     * @param  $rule_id 规则ID
     * @return string
     * @author 
     **/
    public function getSampleByRuleId($rule_id)
    {
        if(!$rule_id){
            return false;
        }
        $sql = 'SELECT * FROM sdb_taoexlib_sms_bind AS rs LEFT JOIN sdb_taoexlib_sms_sample AS ss ON rs.id = ss.id WHERE rs.tid ='.$rule_id;
        $rows = $this->db->select($sql);
        return $rows[0];
    }
    /**
     * 获取所有已经有绑定关系的规则的ID
     *
     * @param  void
     * @return array
     * @author 
     **/
    public function getAllBindId($bind_id)
    {
        $bind_list = $this->getList('tid');
        foreach ($bind_list as $bind) {
            if($bind_id != $bind['tid']){
                $tids[] = $bind['tid'];
            }
        }
        return $tids;
    }
    function modifier_id($row)
    {
        $info = $this->app->model('sms_sample')->getList('sample_no',array('id'=>$row));
        $btn = "<a href='javascript:voide(0);' onclick=\"new Dialog('index.php?app=taoexlib&ctl=admin_sms_sample&act=edit_sample&p[0]=".$row."&finder_id={$_GET[_finder][finder_id]}',{width:600,height:500,title:'编辑模板'}); \">".$info[0]['sample_no']."</a>";
        return $btn;
    }


    /**
     * 根据规则id获取绑定关系信息
     *
     * @param  $rule_id
     * @return void
     * @author 
     **/
    public function getBindByRuleId($rule_id)
    {
    	$res = app::get('taoexlib')->model('sms_bind')->select()->columns()->where('tid=?',$rule_id)->instance()->fetch_row();
    	return $res;
    }
}
