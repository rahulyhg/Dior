<?php
class omeanalysts_ome_delivery extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $type_options = array(
        'display' => 'true',
    );
    public $logs_options = array(
        '1' => array(
            'name' => '买家支付运费',
            'flag' => array(),
            'memo' => '买家支付运费',
            'icon' => 'money_delete.gif',
        ),
        '2' => array(
            'name' => '包裹数量',
            'flag' => array(),
            'memo' => '发出的包裹总数',
            'icon' => 'money.gif',
        ),
        '3' => array(
            'name' => '快递成本',
            'flag' => array(),
            'memo' => '快递成本',
            'icon' => 'money_delete.gif',
        ),
        '4' => array(
            'name' => '差额',
            'flag' => array(),
            'memo' => '差额',
            'icon' => 'money_delete.gif',
        ),
    );

    public $graph_options = array(
        'hidden' => true,
    );


    public function get_type(){

        $shopObj = &app::get('ome')->model('shop');
        $typeObj = $this->app->model('ome_type');

        #店铺
        $shop_datas = $shopObj->getList('shop_id,name');

        $shop_data[0] = '全部';
        foreach($shop_datas as $v){
           $shop_data[$v['shop_id']] = $v['name'];
        }

        #发货仓库
        $branch_datas = $typeObj->get_branch();
        $branch_data[0] = '全部';
        foreach($branch_datas as $v){
           $branch_data[$v['type_id']] = $v['name'];
        }

        #物流公司
        $dlycorp_data = $typeObj->get_dly_corp();


        $return = array(
          'shop_id'=>array(
            'lab' => '店铺',
            'data' => $shop_data,
            'type' => 'select',
          ),
          'branch_id'=>array(
            'lab' => '发货仓库',
            'data' => $branch_data,
            'type' => 'select',
          ),
          'logi_id'=>array(
            'lab' => '物流公司',
            'data' => $dlycorp_data,
            'type' => 'select',
          ),
        );

        return $return;
    }

    public function headers(){
        parent::headers();
        $this->_render->pagedata['typeData'] = $this->get_type();
        $type_selected = array(
                            'shop_id'=>$this->_params['shop_id'],
                            'branch_id'=>$this->_params['branch_id'],
                            'logi_id'=>$this->_params['logi_id'],
                        );
        $this->_render->pagedata['type_selected'] = $type_selected;
    }

    public function ext_detail(&$detail){
        $params = $this->_params;
        $filter = array(
            'time_from' => $params['time_from'],
            'time_to' => $params['time_to'],
            'branch_id' => $params['branch_id'],
            'own_branches'=>$params['own_branches'],
        );
        if($params['shop_id']){
            $filter['shop_id'] = $params['shop_id'];
        }

        if($params['logi_id']){
            $filter['logi_id'] = $params['logi_id'];
        }

        $deliveryObj = $this->app->model('ome_delivery');
        $delivery = $deliveryObj->get_delivery($filter);
        $diff_money = $delivery['costfreight'] - $delivery['cost'];
        $detail['包裹数量']['value'] = $delivery['num'];
        $detail['快递成本']['value'] = number_format($delivery['cost'],2,"."," ");
        $detail['买家支付运费']['value'] = number_format($delivery['costfreight'],2,"."," ");
        $detail['差额']['value'] = number_format($diff_money,2,"."," ");
    }

    public function finder(){

        $_extra_view = array(
            'omeanalysts' => 'ome/delivery/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        $actions['export'] = array(
            'label'=>app::get('omeanalysts')->_('生成报表<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=delivery&action=export\');}</script>'),
            'class'=>'export',
            'icon'=>'add.gif',
            'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=delivery&action=export',
            'target'=>'{width:600,height:300,title:\'生成报表\'}'
        );
        $user = kernel::single('desktop_user');
        if ($user->has_permission('order_export') && $servicelist = kernel::servicelist('ietask.service.actionbar')) {
            foreach ($servicelist as $object => $instance){
                if (method_exists($instance, 'getOmeanalystsDelivery')){
                    $actionBars = $instance->getOmeanalystsDelivery();
                    foreach($actionBars as $actionBar){
                        $actions[] = $actionBar;
                    }
                }
            }
        }
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($actions['export']);
        }

        $params = array(
            'actions'=>$actions,
            'title'=>app::get('omeanalysts')->_('快递费统计'),
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>false,
            'use_buildin_filter'=>true,
        );

        return array(
            'model' => 'omeanalysts_mdl_ome_delivery',
            'params' => $params,
        );
    }
}