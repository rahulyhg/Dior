<?php
class wms_mdl_orders extends ome_mdl_orders{

    public function __construct(&$app)
    {
        parent::__construct(app::get('ome'));
    }
}

