<?php
class ome_mdl_api_stock_log extends dbeav_model{
    
    /**
     * 将更新部分库存失败的消息替换
     */
    public function modifier_msg($rows) {
        return str_replace('部分','',$rows);
    }
}
?>