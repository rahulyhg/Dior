<?php

class ome_mdl_goods_type_spec extends dbeav_model{
    var $has_many = array(
    );

    function get_type_spec($type_id){
        return $this->getList('*',array('type_id'=>$type_id));
    }
}
