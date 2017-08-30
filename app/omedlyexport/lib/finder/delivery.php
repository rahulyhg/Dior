<?php
class omedlyexport_finder_delivery{
    var $actions = array(
  
    );
    
    function __construct(){
        
       $user = kernel::single('desktop_user');

//	   if($user->has_permission('process_receipts_print_export')){
//	   		$this->actions[] =  array(
//            'label'=>'导出',
//            'submit'=>'index.php?app=omedlyexport&ctl=ome_delivery&act=index&action=export',
//            'target'=>'dialog::{width:400,height:170,title:\'导出\'}'
//        	);
//	   }
    }
}