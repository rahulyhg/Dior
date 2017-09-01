<?php
/**
 * 
 * @author chenjun
 * @copyright  Copyright (c) 2005-2011 ShopEx Technologies Inc. (http://www.shopex.cn)
 */
class omeio_public_config{
    private $file_type = array(
        'csv' => '.csv',
       // 'xls' => '.xls',
       // 'xlsx' => '.xlsx',
    );
    
    private $task_past_day = 7;
    
    //文件类型
    public function get_filetype(){
        return $this->file_type;
    }

    //任务超时天数 单位:天
    public function get_task_past_day(){
        return $this->task_past_day;
    }

}