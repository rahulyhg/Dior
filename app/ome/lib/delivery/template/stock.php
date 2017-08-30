<?php

/**
 * 出货单模板
 *
 */
class ome_delivery_template_stock {
    protected $elements = array (
        'date_y'      => '当日日期-年',
        'date_m'      => '当日日期-月',
        'date_d'      => '当日日期-日',
        'date_ymd'      => '当日日期-年月日',
        'batch_number' => '批次号',
    );
    
    /**
     * 默认选项列表
     * Enter description here ...
     */
    public function defaultElements() {
        return $this->elements;
    }
}