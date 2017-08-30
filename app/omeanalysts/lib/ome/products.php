<?php
class omeanalysts_ome_products extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $detail_options = array(
        'hidden' => false,
    );

    public $graph_options = array(
        'hidden' => true,
    );

	public $type_options = array(
        'display' => 'true',
    );

    public $analysts_options = array(
        'display' => false,
        'description' => '总成本 = 销售成本 - 售后商品成本之和&nbsp;&nbsp;
                          销售毛利 = 销售额 - 总成本销售&nbsp;&nbsp;
                          平均毛利 = 销售毛利 / 销售量&nbsp;&nbsp;
                          销售毛利率 = 销售毛利 / 销售额&nbsp;&nbsp;
                          销售单价 = 商品销售之和 / 销售量',
    );

    public $logs_options = array(
        '1' => array(
            'name' => '销售量',
            'flag' => array(),
            'memo' => '销售量',
            'icon' => 'money.gif',
            'col'  => '1',
        ),
        '2' => array(
            'name' => '销售额',
            'flag' => array(),
            'memo' => '销售额',
            'icon' => 'coins.gif',
            'col'  => '2',
        ),
        '3' => array(
            'name' => '日均销售额',
            'flag' => array(),
            'memo' => '日均销售额',
            'icon' => 'coins.gif',
            'col'  => '3',
        ),
        '4' => array(
            'name' => '日均销售量',
            'flag' => array(),
            'memo' => '日均销售量',
            'icon' => 'money.gif',
            'col'  => '4',
        ),
        '5' => array(
            'name' => '销售毛利',
            'flag' => array(),
            'memo' => '销售毛利',
            'icon' => 'coins.gif',
            'col'  => '5',
        ),
        '6' => array(
            'name' => '销售毛利率',
            'flag' => array(),
            'memo' => '销售毛利率',
            'icon' => 'coins.gif',
            'col'  => '6',
        ),
        '7' => array(
            'name' => '退货量',
            'flag' => array(),
            'memo' => '退货量',
            'icon' => 'money.gif',
            'col'  => '7',
        ),
        '8' => array(
            'name' => '退货总额',
            'flag' => array(),
            'memo' => '退货总额',
            'icon' => 'coins.gif',
            'col'  => '8',
        ),
    );

    public function get_type(){
        $lab = '店铺';
        $typeObj = $this->app->model('ome_type');
        $shop_data = $typeObj->get_shop();
        $brand_data = $typeObj->get_brand();
        $gtype_data = $typeObj->get_gtype();
        
        $obj_type =  $typeObj->getProductType();
        
        $return = array(
          'shop_id'=>array(
            'lab' => '店铺',
            'data' => $shop_data,
          ),
          'brand_id'=>array(
            'lab' => '品牌',
            'data' => $brand_data,
          ),
          'goods_type_id'=>array(
            'lab' => '商品类型',
            'data' => $gtype_data,
          ),
        'obj_type'=>array(
                'lab' => '类型',
                'data' => $obj_type,
        ),
        );

        return $return;
    }

    public function headers(){
        $this->_render->pagedata['title'] = $this->_title;
        $this->_render->pagedata['time_from'] = $this->_params['time_from'];
        $this->_render->pagedata['time_to'] = $this->_params['time_to'];
        $this->_render->pagedata['today'] = date("Y-m-d");
        $this->_render->pagedata['yesterday'] = date("Y-m-d", time()-86400);
        if(isset($this->analysis_config)){
            $this->_render->pagedata['this_week_from'] = date("Y-m-d", time()-(date('w')?date('w')-$this->analysis_config['setting']['week']:7-$this->analysis_config['setting']['week'])*86400);
        }else{
            $this->_render->pagedata['this_week_from'] = date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400);
        }
        $this->_render->pagedata['this_week_to'] = date("Y-m-d", strtotime($this->_render->pagedata['this_week_from'])+86400*7-1);
        $this->_render->pagedata['last_week_from'] = date("Y-m-d", strtotime($this->_render->pagedata['this_week_from'])-7*86400);
        $this->_render->pagedata['last_week_to'] = date("Y-m-d", strtotime($this->_render->pagedata['last_week_from'])+86400*7-1);
        $this_month_t = date('t');
        $this->_render->pagedata['this_month_from'] = date("Y-m-" . 01);
        $this->_render->pagedata['this_month_to'] = date("Y-m-" . $this_month_t);
        $last_month_t = date('t', strtotime("last month"));
        $this->_render->pagedata['last_month_from'] = date("Y-m-" . 01, strtotime("last month"));
        $this->_render->pagedata['last_month_to'] = date("Y-m-" . $last_month_t, strtotime("last month"));
        $this->_render->pagedata['layout'] = $this->layout;

        if($this->report_type == 'true'){
            $this->_render->pagedata['report'] = $this->_params['report'];
            $this->_render->pagedata['report_type'] = $this->report_type;
            $this->_render->pagedata['month'] = array(1,2,3,4,5,6,7,8,9,10,11,12);
            for($i = 2000;$i<=date("Y",time());$i++){
                $year[] = $i;
            }
            $this->_render->pagedata['year'] = $year;
            $this->_render->pagedata['from_selected'] = explode('-',$this->_params['time_from']);
            $this->_render->pagedata['to_selected'] = explode('-',$this->_params['time_to']);
        }

        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $type_selected = array(
                                'shop_id'=>$this->_params['shop_id'],
                                'brand_id'=>$this->_params['brand_id'],
                                'goods_type_id'=>$this->_params['goods_type_id'],
                                'obj_type'=>$this->_params['obj_type'],
                            );
            $this->_render->pagedata['type_selected'] = $type_selected;
        }

        $this->_render->pagedata['analysts_options'] = $this->analysts_options;

    }

    public function finder(){
        $this->_render->pagedata['act'] = 'product';

        $_extra_view = array(
            'omeanalysts' => 'ome/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        if(!$_GET['action']){//保存筛选器的信息,用于做导出条件
            kernel::single('omeanalysts_func')->save_search_filter($this->_params);
        }

       $params =  array(
            'model' => 'omeanalysts_mdl_ome_products',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('omeanalysts')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=products&action=export',
                        'target'=>'{width:600,height:300,title:\'生成报表\'}'),
                ),
                'title'=>app::get('omeanalysts')->_('货品销售情况<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=products&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                 'use_buildin_filter'=>true,
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

        $Oproducts = $this->app->model('ome_products');
        $products = $Oproducts->get_products($filter);

        $detail['销售额']['value'] = "￥".number_format($products['sale_amount'],2,"."," ");//销售额
        $detail['销售量']['value'] = $products['salenums']?$products['salenums']:0;//销售量
        $detail['日均销售额']['value'] = "￥".number_format($products['day_amounts'],2,"."," ");//日均销售额
        $detail['日均销售量']['value'] = $products['day_nums'] ? round($products['day_nums'],2):0;//日均销售量
        $detail['销售毛利']['value'] = "￥".($products['gross_sales'] ? round($products['gross_sales'],2) : 0);//销售毛利
        $detail['销售毛利率']['value'] = ($products['gross_sales_rate'] ? round($products['gross_sales_rate']*100,2) : 0)."%";//销售毛利率
        $detail['退货量']['value'] = $products['reship_nums'] ? round($products['reship_nums'],2):0;//退货量
        $detail['退货总额']['value'] = "￥".number_format($products['reship_amounts'],2,"."," ");//退货总额

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