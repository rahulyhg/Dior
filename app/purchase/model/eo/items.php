<?php

class purchase_mdl_eo_items extends dbeav_model{


    /*
     * 返回可退值
     * @param item_id
     */
    function Get_num($item_id){
        $item = $this->dump($item_id,'entry_num,out_num');
        return $item['entry_num']-$item['out_num'];
    }
}

?>
