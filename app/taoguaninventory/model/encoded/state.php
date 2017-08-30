<?php
class taoguaninventory_mdl_encoded_state extends dbeav_model {
    /**
    *获取编码状态信息
    */
    function get_state($name){
        $state = $this->dump(array('name'=>$name),'head,bhlen,currentno,eid');
        if($state){
            $currentno = $state['currentno'];
            $maxcurrentno = str_pad(9,$state['bhlen'],9,STR_PAD_LEFT);
            if($maxcurrentno==$currentno){
                    $currentno=0;
            }
            $currentno++;
            $state_bn = $state['head'].date('ymd').str_pad($currentno,$state['bhlen'],'0',STR_PAD_LEFT);
            $state['currentno'] = $currentno;
            $state['state_bn'] = $state_bn;
            return $state;
        }else{
            return false;
        }

    }


}
?>