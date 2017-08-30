<?php
/**
    * ShopEx licence
    *
    * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
    * @license  http://ecos.shopex.cn/ ShopEx License
    * @version osc---hanbingshu sanow@126.com
    * @date 2012-07-26
*/
class tgstockcost_system_setting{
    
    private $tgstockcost_cost = array(
        '1' => '不计成本',
        '2' => '固定成本法',
        '3' => '平均成本法',
        '4' => '先进先出法',
    );

    private $tgstockcost_get_value_type = array(
        '1' => '取货品的固定成本', 
        '2' => '取货品的单位平均成本', 
        '3' => '取货品的最近一次出入库成本', 
        '4' => '取0', 
    );

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function get_tgstockcost_cost()
    {
        return $this->tgstockcost_cost;
    }

    public function get_tgstockcost_get_value_type()
    {
        return $this->tgstockcost_get_value_type;
    }

    public function get_setting_value($key = '')
    {
        $setting = array();

        foreach ($this->all_settings() as $k => $v) {
            $setting[$v] = app::get('ome')->getConf($v);
        }

        return $key ? $setting[$key] : $setting;
    }
    
    /**
     * 库存成本配置
     *
     * @return void
     * @author 
     **/
    public function get_setting_tab()
    {
        $settingTabs = array( 
            array(
                'name'      => '成本设置',
                'file_name' => 'admin/system/setting/tab_stockcost.html',
                'app'       => 'tgstockcost',
                // 'url'       => 'index.php?app=tgstockcost&ctl=setting&act=settingpage'
                'hidden_default_button' => true,
                'order' => 50,
            )
        );

        return $settingTabs;
    }

    public function get_pagedata(&$controller)
    {

        $controller->pagedata['tgstockcost']['setting']['cost']['options']           = $this->tgstockcost_cost;
        $controller->pagedata['tgstockcost']['setting']['get_value_type']['options'] = $this->tgstockcost_get_value_type; 

        $controller->pagedata['tgstockcost']['install_time'] = app::get('ome')->getConf('tgstockcost_install_time');

        $oplogModel = app::get('tgstockcost')->model('operation');
        $operationList = $oplogModel->getList('*',array(),0,10,'operation_id desc');
        
        if($operationList){
            foreach ((array) $operationList as $key => $value) {
                $operationList[$key]['tgstockcost_cost'] = $this->tgstockcost_cost[$value['tgstockcost_cost']];
                $operationList[$key]['tgstockcost_get_value_type'] = $this->tgstockcost_get_value_type[$value['tgstockcost_get_value_type']];
                $operationList[$key]['type'] = $value['type'] == '2' ? '成本设置期初' : '成本设置变更';
            }

            $controller->pagedata['tgstockcost']['operations'] = $operationList;
        }
    }

    function view()
    {
        $render = $this->app->render();
        $render->pagedata['stockcost_cost'] = app::get("ome")->getConf("tgstockcost.cost");
        $render->pagedata['stockcost_get_value_type'] = app::get("ome")->getConf("tgstockcost.get_value_type");
        return $render->fetch("admin/system/system_setting.html");
    }

    function all_settings(){
		
        $all_settings = array(
            'tgstockcost.cost',
            'tgstockcost.get_value_type',
			'tgstockcost.installed',
            'tgstockcost_install_time'
        );
        return $all_settings;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function get_setting_data()
    {
        $setData = array();

        $all_settings = $this->all_settings();

        foreach($all_settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = app::get('ome')->getConf($set);
        }

        return $setData;
    }

    function setting_save($setting,&$msg="")
    {
        $setting_stockcost_cost = app::get("ome")->getConf("tgstockcost.cost");
        $setting_stockcost_get_value_type = app::get("ome")->getConf("tgstockcost.get_value_type");
        if ($setting_stockcost_cost == $setting['tgstockcost_cost'] && $setting_stockcost_get_value_type == $setting['tgstockcost_get_value_type']) {
            $msg = '成本设置无变化，不需要修改';
            return false;
        }

        if(!$this->checkCost()){
            $msg = '商品成本价未设置,请先去商品管理中设置成本价';
            return false;
        }

        if ($setting['tgstockcost_cost'] == '1' && $setting['tgstockcost_get_value_type']) {
            $msg = '不计成本发不能设置盘点/调账成本取值';
            return false;
        } elseif ($setting['tgstockcost_cost'] != '1' && !$setting['tgstockcost_get_value_type']) {
            $msg = '请设置盘点/调账成本取值';
            return false;
        }

        $oplogModel = app::get('tgstockcost')->model('operation');
        $lastoplog = $oplogModel->getList('install_time',array(),0,1,'install_time desc');
        if ($lastoplog) {
            if (time() - 86400 < $lastoplog[0]['install_time']) {
                $msg = '不允许修改：这一之内不允许重复修改';
                return false;
            }
        }

        app::get("ome")->setConf("tgstockcost.cost",$setting['tgstockcost_cost']);
        app::get("ome")->setConf("tgstockcost.get_value_type",$setting['tgstockcost_get_value_type']);        

        if (!app::get('ome')->getConf('tgstockcost_install_time')) {
            app::get('ome')->setConf('tgstockcost_install_time',time());            
        }

        // 期初
        kernel::single("tgstockcost_instance_router")->create_queue();

        // 库存成本切换日志
        $now = time();
        $oplogModel->update(array('status' => '0','end_time' => $now),array('status' => '1'));

        $_tgcost['tgstockcost_cost'] = $setting['tgstockcost_cost'];
        $_tgcost['tgstockcost_get_value_type'] = $setting['tgstockcost_get_value_type'];
        $_tgcost['install_time'] = $now;
        $_tgcost['op_id'] = kernel::single('desktop_user')->get_id();
        $_tgcost['op_name'] = kernel::single('desktop_user')->get_name();
        $_tgcost['operate_time'] = $now;
        $_tgcost['status'] = '1';//当前成本法
        $_tgcost['type'] = '1';
        $oplogModel->save($_tgcost);

        //     if($setting['tgstockcost_cost'] == '1')
        //     {
        //         if(isset($setting['tgstockcost_get_value_type'])){
        //             $msg = "不计成本发不能设置盘点/调账成本取值";
        //             return false;
        //         }
        //         app::get("ome")->setConf("tgstockcost.cost",$setting['tgstockcost_cost']);
        //     }
        //     else
        //     {
        //         if(!isset($setting['tgstockcost_get_value_type'])){
        //             $msg = "请设置盘点/调账成本取值";
        //             return false;
        //         } else {
        //             app::get("ome")->setConf("tgstockcost.cost",$setting['tgstockcost_cost']);
        //             app::get("ome")->setConf("tgstockcost.get_value_type",$setting['tgstockcost_get_value_type']);
        //             app::get("ome")->setConf("tgstockcost_install_time",time()); //安装时间
        //             $router = kernel::single("tgstockcost_instance_router");
        //             $router->create_queue();
        //         }
        //     }
        // }
        return true;
    }

    function checkCost(){
        $Oproduct = app::get('ome')->model('products');
        $filter = array('visibility'=>'true');
        $rows = $Oproduct->getList('sum(cost) as sum_cost',$filter);
        if(intval($rows[0]['sum_cost'])==0){
           return false;
        }else{
           return true;
        }
           
    }
    
}
