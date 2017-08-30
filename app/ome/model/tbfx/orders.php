<?php

class ome_mdl_tbfx_orders extends dbeav_model{
    var $has_many = array(
       'tbfx_order_objects' => 'tbfx_order_objects',
    );

}
?>