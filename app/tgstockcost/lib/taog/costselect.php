<?php
class tgstockcost_taog_costselect extends eccommon_analysis_abstract implements eccommon_analysis_interface
{
    public $report_type = 'false';
    public $type_options = array(
        'display' => 'true',
    );
    public $logs_options = array(
        '1' => array(
            'name' => '库存数',
            'flag' => array(),
            'memo' => '库存数',
            #'icon' => 'money.gif',
        ),
        '2' => array(
            'name' => '单位成本',
            'flag' => array(),
            'memo' => '单位成本',
            #'icon' => 'money_delete.gif',
        ),
        '3' => array(
            'name' => '商品成本',
            'flag' => array(),
            'memo' => '商品成本',
            #'icon' => 'coins.gif',
        ),
    );
    public $graph_options = array(
        'hidden' => true,
    );

    function __consruct($app)
    {
        $this->app = $app;

        parent::__construct($app);
    }

    public function get_type(){

        #仓库
        $branch_mdl = app::get("ome")->model("branch");
        $branch_datas = $branch_mdl->getList("branch_id,name");
        $branch_data[0] = '全部';
        foreach($branch_datas as $v){
           $branch_data[$v['branch_id']] = $v['name'];
        }

        #品牌
        $brand_mdl = app::get("ome")->model("brand");
        $brand_datas = $brand_mdl->getList("brand_id,brand_name");
        $brand_data[0] = '全部';
        foreach($brand_datas as $v){
           $brand_data[$v['brand_id']] = $v['brand_name'];
        }

        #商品类型
        $gtype_mdl = app::get("ome")->model("goods_type");
        $gtype_datas = $gtype_mdl->getList("type_id,name");

        $gtype_data[0] = '全部';
        foreach($gtype_datas as $v){
           $gtype_data[$v['type_id']] = $v['name'];
        }

        $return = array(
          'branch_id'=>array(
            'lab' => '仓库',
            'data' => $branch_data,
            'type' => 'select',
          ),
          'p_bn'=>array(
            'lab' => '货号',
            'type' => 'text',
          ),
          'goods_bn'=>array(
            'lab' => '商品编号',
            'type' => 'text',
          ),
          'product_name'=>array(
            'lab' => '货品名称',
            'type' => 'text',
          ),
          'brand'=>array(
            'lab' => '品牌',
            'data' => $brand_data,
            'type' => 'select',
          ),
          'type_id'=>array(
            'lab' => '商品类型',
            'data' => $gtype_data,
            'type' => 'select',
          ),
        );
        return $return;
    }

    public function headers(){
        $this->_render->pagedata['title'] = $this->_title;

        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $this->_render->pagedata['type_selected'] = array(
                                            'branch_id'=>$this->_params['branch_id'],
                                            'p_bn'=>$this->_params['p_bn'],
                                            'goods_bn'=>$this->_params['goods_bn'],
                                            'product_name'=>$this->_params['product_name'],
                                            'brand'=>$this->_params['brand'],
                                            'type_id'=>$this->_params['type_id'],
                                            );

        }
    }

    public function finder(){
        $_GET['filter']['from'] = array(
            'branch_id'=>$_POST['branch_id'],
            'brand'=>$_POST['brand'],
            'type_id'=>$_POST['type_id'],
            'p_bn'=>$_POST['p_bn'],
            'goods_bn'=>$_POST['goods_bn'],
            'product_name'=>$_POST['product_name'],
       );        $_extra_view = array(
            'tgstockcost' => 'admin/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        $res = '';

        foreach((array)$_POST as $k=>$v){

            if($k!='_DTYPE_DATE'){
               $res .='&'.$k.'='.$v;
            }
        }

        $params = array(
                'actions'=>array(
                    array(
                        'label'=>app::get('tgstockcost')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=tgstockcost&ctl=costselect&act=index&action=export'.$res,
                        'target'=>'{width:600,height:300,title:\'生成报表\'}'),
                ),
                #'base_filter' => $base_filter,
                'title'=>app::get('tgstockcost')->_('库存成本统计'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
            );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($params['actions']);
        }

        return array(
            'model' => 'tgstockcost_mdl_costselect',
            'params' => $params,
        );
    }

    public function set_params($params)
    {
        $this->_params = $params;

        return $this;
    }//End Function

    public function ext_detail(&$detail){
        $filter = $this->_params;

        $total_store = $total_unit_cost = $total_inventory_cost = 0;

        $costs = $this->app->model('costselect')->header_getlist('sum(obp.store) as store,sum(obp.inventory_cost) as inventory_cost',$filter);

        $total_store = $costs[0]['store'];
        $total_inventory_cost = $costs[0]['inventory_cost'];

        $detail['库存数']['value'] = $total_store;
        $total_unit_cost = ($total_store!=0)?round($total_inventory_cost/$total_store,2):0;
        $detail['单位成本']['value'] = '￥'.$total_unit_cost;
        $detail['商品成本']['value'] = '￥'.$total_inventory_cost;
    }
}