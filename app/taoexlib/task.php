<?php
class taoexlib_task{

    function post_install(){
    	kernel::single('base_initial', 'taoexlib')->init();
    }
   

    function install_options(){
        return array(
                
            );
    }
}
