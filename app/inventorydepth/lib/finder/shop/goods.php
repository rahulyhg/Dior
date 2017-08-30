<?php
class inventorydepth_finder_shop_goods {
    
    function __construct($app) {
        $this->app = $app;
        $this->_request = kernel::single('base_component_request');
    }

    var $column_control = '操作';
    var $column_control_order = 200;
    public function column_control($row) {

        $get = $_GET;
        $get['app'] = 'inventorydepth';$get['ctl'] = 'shop';$get['act'] = 'premove'; $get['p'][0] = $row['id'];
        $url = "index.php?".http_build_query($get);

        $finder_id = $get['_finder']['finder_id'];
        $control = <<<EOF
    <a href='javascript:void(0);' onclick='javascript:W.page("$url",{
        async:false
    });finderGroup["$finder_id"].refresh();'>移除</a>
EOF;
        return $control;
    }
}
