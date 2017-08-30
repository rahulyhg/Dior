<?php
class omeanalysts_ome_branchdelivery extends eccommon_analysis_abstract implements eccommon_analysis_interface{

    public $graph_options = array(
        'hidden' => true,
    );
    public $type_options = array(
        'display' => 'true',
    );

    public $detail_options = array(
        'hidden' => false,
        'force_ext' => true,
    );

    public $logs_options = array(

        '1' => array(
            'name' =>'销售出库数',
            'value'=>0,
            'memo' =>'销售出库数',
            'col'  => '1',
            'icon' => 'money.gif',
        ),
        '2' => array(
            'name' =>'售后入库数',
            'value'=>0,
            'memo' =>'售后入库数',
            'col'  => '2',
            'icon' => 'money.gif',
        ),
        '3' => array(
            'name' =>'合计数量',
            'value'=>0,
            'memo' =>'合计数量',
            'col'  => '3',
            'icon' => 'coins.gif',
        ),
    );
    function __construct(&$app)
    {
        parent::__construct($app);
    }

    public function get_type(){
        $typeObj = $this->app->model('ome_type');
        $shop_data = $typeObj->get_shop();
        $brand_data = $typeObj->get_brand();
        $gtype_data = $typeObj->get_gtype();
        $branch_data = $typeObj->get_branch();
        $return = array(
          'shop_id'=>array(
            'lab' => '销售店铺',
            'data' => $shop_data,
          ),
          'branch_id'=>array(
            'lab' => '发货仓库',
            'data' => $branch_data,
          ),
          'brand_id'=>array(
            'lab' => '品牌',
            'data' => $brand_data,
          ),
          'goods_type_id'=>array(
            'lab' => '商品类型',
            'data' => $gtype_data,
          ),
        );

        return $return;
    }


    public function headers(){

        parent::headers();

        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $type_selected = array(
                                'shop_id'=>$this->_params['shop_id'],
                                'branch_id'=>$this->_params['branch_id'],
                                'brand_id'=>$this->_params['brand_id'],
                                'goods_type_id'=>$this->_params['goods_type_id'],
                            );
            $this->_render->pagedata['type_selected'] = $type_selected;
        }

    }


    public function finder(){

        $_extra_view = array(
            'omeanalysts' => 'ome/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        $params =  array(
            'model' => 'omeanalysts_mdl_ome_branchdelivery',
            'params' => array(
                'actions'=>array(
                     array(
                          'class' => 'export',
                         'label' => '生成报表',
                         'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=branchdelivery&action=export',
                         'target'=>'{width:600,height:300,title:\'生成报表\'}'
                     ),
                ),
                'title'=>app::get('omeanalysts')->_('仓库发货情况<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=branchdelivery&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
            ),
        );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($params['params']['actions']);
        }
        return $params;
    }

    public function ext_detail(&$detail){

        $filter = $this->_params;

        $branchdelivery_Mdl = $this->app->model('ome_branchdelivery');
        $data = $branchdelivery_Mdl->get_count($filter);

        $detail['销售出库数']['value'] = $data['total_sales'];
        $detail['售后入库数']['value'] = $data['total_aftersales'];
        $detail['合计数量']['value'] = $data['total_sales'] - $data['total_aftersales'];
    }

    public function detail()
    {
        if($this->detail_options['hidden'] == true){
            $this->_render->pagedata['detail_hidden'] = 1;
            return false;
        }
        $detail = array();

        foreach($this->logs_options AS $target=>$option){
            $detail[$option['name']]['value'] = ($tmp[$target]) ? $tmp[$target] : 0;
            $detail[$option['name']]['memo'] = $this->logs_options[$target]['memo'];
            $detail[$option['name']]['icon'] = $this->logs_options[$target]['icon'];
            $detail[$option['name']]['col'] = $target;
        }

        if(method_exists($this, 'ext_detail')){
            $this->ext_detail($detail);
        }
        foreach($detail AS $key=>$val){
            $name = $this->app->_($key);
            $data[$name]['value'] = $val['value'];
            $data[$name]['memo'] = $this->app->_($val['memo']);
            $data[$name]['icon'] = $val['icon'];
            $data[$name]['col'] = $val['col'];
        }
        $this->_render->pagedata['detail'] = $data;
        return true;
    }//End Function


}