<?php
class omeanalysts_ome_cod extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $type_options = array(
        'display' => 'true',
    );
    public $logs_options = array(
        '1' => array(
            'name' => '包裹数量',
            'flag' => array(),
            'memo' => '发出的包裹总数',
            'icon' => 'money.gif',
        ),
        '2' => array(
            'name' => '快递费用',
            'flag' => array(),
            'memo' => '产生的快递费用总计',
            'icon' => 'money_delete.gif',
        ),
        '3' => array(
            'name' => '应收货款',
            'flag' => array(),
            'memo' => '由物流公司代收的订单应收款',
            'icon' => 'coins.gif',
        ),
    );

    public function get_type(){
        $lab = '发货仓库';
        $typeObj = $this->app->model('ome_type');
        $data = $typeObj->get_branch();
        $return = array(
            'lab' => $lab,
            'data' => $data,
        );
        return $return;
    }

    public $graph_options = array(
        'hidden' => true,
    );

    public function headers(){
        parent::headers();
        $shopObj = &app::get('ome')->model('shop');
        $shopList = $shopObj->getList('shop_id,name');
        $this->_render->pagedata['shopList'] = $shopList;
        $this->_render->pagedata['shop_selected'] = $this->_params['shop_id'];
    }

    public function ext_detail(&$detail){
        $params = $this->_params;
        $filter = array(
            'time_from' => $params['time_from'],
            'time_to' => $params['time_to'],
            'type_id' => $params['type_id'],
        );
        if($params['shop_id']){
            $filter['shop_id'] = $params['shop_id'];
        }

        $codObj = $this->app->model('ome_cod');
        $delivery = $codObj->get_cod($filter);

        $detail['包裹数量']['value'] = $delivery['num'];
        $detail['快递费用']['value'] = number_format($delivery['cost'],2,"."," ");
        $detail['应收货款']['value'] = number_format($delivery['receivables'],2,"."," ");
    }

    public function finder(){
        $params = array(
            'actions'=>array(
                array(
                    'label'=>app::get('omeanalysts')->_('生成报表'),
                    'class'=>'export',
                    'icon'=>'add.gif',
                    'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=cod&action=export',
                    'target'=>'{width:600,height:300,title:\'生成报表\'}'),
            ),
            'title'=>app::get('omeanalysts')->_('货到付款统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=cod&action=export\');}</script>'),
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>false,
            'use_buildin_filter'=>true,
        );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($params['actions']);
        }

        return array(
            'model' => 'omeanalysts_mdl_ome_cod',
            'params' => $params,
        );
    }
}