<?php
class omeauto_mdl_branch extends ome_mdl_branch {
    function __construct($app){
        parent::__construct(app::get('ome'));
    }
}
?>