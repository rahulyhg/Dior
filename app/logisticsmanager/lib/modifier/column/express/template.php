<?php

class logisticsmanager_modifier_column_express_template {
    public function columns_modifier(&$columns) {
        $tmpDetail=array();
        foreach($columns as $key=>$value){
            if (method_exists($this, $value[1] . '_display')) {
                  $displayMethod=$value[1] . '_display';
                  $isDisplay = $this->{$displayMethod}();
                        if ($isDisplay) {
                            $tmpDetail[$key]=$value;
                        }
            }else{
                $tmpDetail[$key]=$value;
            }
        }
        $columns=$tmpDetail;
    }

    /**
     * 是否默认
     */
    public function column_isdefault_display() {
        $display = false;
        if (in_array($_GET['act'], array('delivery', 'stock'))) {
            $display = true;
        }
        return $display;
    }
}
