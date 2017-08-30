<?php
class taoguaninventory_task{

    function post_install($options){

        kernel::single('base_initial', 'taoguaninventory')->init();
    }
}