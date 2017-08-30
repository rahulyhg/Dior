<?php
class omeauto_system_setting{
    public function view(){
        $settings = $this->all_settings();
        foreach($settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = &app::get('ome')->getConf($set);
        }
        $render = kernel::single('base_render');
        $render->pagedata['setData'] = $setData;

        $html = $render->fetch('system/setting.html','omeauto');
        return $html;
    }

    function all_settings(){
        $all_settings =array(
             'auto.setting',
            );
        return $all_settings;
    }
}
