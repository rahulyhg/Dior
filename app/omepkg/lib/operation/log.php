<?php
class omepkg_operation_log{
    	function get_operations(){
	       $operations = array(
             'omepkg_modify' => array('name'=> '捆绑商品编辑','type' => 'pkg_goods@omepkg'),
        );
        return array('omepkg'=>$operations);
     }
}
?>