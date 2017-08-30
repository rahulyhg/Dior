<?php
 
class erpapi_oms_member
{

	public function update($params){
		$post=json_decode($params['member'],true);
		
		$m_memeber_num=$post['m_memeber_num'];
		$m_memeber_card=$post['m_memeber_card'];
		if(empty($m_memeber_num)||empty($m_memeber_card)){
			return $this->send_error('m_memeber_num或m_memeber_card必须填写');
		}
		$mObj = kernel::single("ome_mdl_members");
		$arrMember=$mObj->getList("member_id,m_memeber_num",array('m_memeber_num'=>$m_memeber_num));
		if(empty($arrMember['0']['m_memeber_num'])){
			return $this->send_error('m_memeber_num不存在');
		}
		$post['member_id']=$arrMember['0']['member_id'];
		if($mObj->update($post,array('member_id'=>$post['member_id']))){
			return $this->send_succ('修改成功');
		}else{
			return $this->send_error('修改失败');
		}
		//echo "<pre>";print_r($arrMember);exit();
	}
	
	public function send_succ($msg=''){
        // return $this->_response->output('succ',$msg);
        $rs = array(
            'rsp'      => 'succ',
            'msg'      => $msg,
            'msg_code' => null,
            'data'     => null,
        );
        return $rs;
    }

    public function send_error($msg, $msg_code='', $data=''){
        // return $this->_response->output($rsp='fail', $msg, $msg_code, $data);

        $rs = array(
            'rsp'      => 'fail',
            'msg'      => $msg,
            'msg_code' => $msg_code,
            'data'     => $data,
        );
        return $rs;
    }
}
