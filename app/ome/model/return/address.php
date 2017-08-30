<?php
class ome_mdl_return_address extends dbeav_model{

    
    /**
     * 获取默认退货店址
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getDefaultAddress($shop_id)
    {
        $address = $this->dump(array('shop_id'=>$shop_id,'cancel_def'=>'true'));
        $phone = explode('-',$address['phone']);#将电话处理一下
        $address['tel'] = $phone[0].$phone[1];
        $address['address'] = $address['province'].$address['city'].$address['country'].$address['addr'];
        return $address;
    }
}

?>