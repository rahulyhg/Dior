<?php
class wms_system_setting{

    public $tab_name = '发货校验';

    public $tab_key = 'delivery';

    private $_setting_tab = array(
        array('name' => '发货校验', 'file_name' => 'admin/system/setting/tab_delivery.html', 'app' => 'wms', 'order' => 40),
    );

    public function get_setting_tab()
    {
        return $this->_setting_tab;
    }

    public function getView(){
        $settings = $this->all_settings();
        foreach($settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = &app::get('wms')->getConf($set);
        }

        $render = kernel::single('base_render');


        $render->pagedata['tab_key'] = $this->tab_key;
        $render->pagedata['setData'] = $setData;

        $html = $render->fetch('admin/system/setting.html','wms');
        return $html;
    }

    public function all_settings(){
        $all_settings =array(
            'wms.delivery.check',
            'wms.delivery.check_show_type',
            //'ome.batch_print_nums',
            'wms.delivery.check_ident',
            'wms.delivery.weight',
        	'wms.delivery.weightwarn',
            'wms.delivery.minWeight',
            'wms.delivery.maxWeight',
            'wms.delivery.cfg.radio',
            'wms.delivery.min_weightwarn',
            'wms.delivery.max_weightwarn',
            'wms.delivery.maxpercent',
            'wms.delivery.minpercent',
            //'ome.delivery.min_weight',
            'wms.delivery.problem_package',
            'wms.groupCalibration.intervalTime',
            'wms.groupDelivery.intervalTime',
            'wms.delivery.consignnum.show',
            'wms.delivery.checknum.show',
            'wms.delivery.logi',
            'wms.product.serial.delivery',
            'wms.delivery.check_delivery',//校验后，直接发货
        );
        return $all_settings;
    }

    public function saveConf($data){
        if ($data['wms.delivery.weight'] == 'off') {
            $data['wms.delivery.logi'] = '0';
        }
        
        //称重开启后，关闭校验完即发货功能
        if($settins['wms.delivery.weight'] == 'on'){
            $data['wms.delivery.check_delivery'] = 'off';
        }

        foreach($data as $set=>$value){
            $curSet = app::get('wms')->getConf($set);
            if(in_array($set,$this->all_settings()) && $curSet!=$data[$set]){
                app::get('wms')->setConf($set,$data[$set]);
            }
        }
        return true;
    }

    public function get_setting_data()
    {
        $setData = array();

        $all_settings = $this->all_settings();

        foreach($all_settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = app::get('wms')->getConf($set);
        }

        return $setData;
    }

}
