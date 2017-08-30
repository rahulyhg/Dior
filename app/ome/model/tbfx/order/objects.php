<?php

class ome_mdl_tbfx_order_objects extends dbeav_model{
    var $has_many = array(
       'tbfx_order_items' => 'tbfx_order_items',
    );

}
?>