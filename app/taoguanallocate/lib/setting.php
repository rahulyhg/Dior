<?php
class taoguanallocate_setting{
    public function view(){
        $settings = $this->all_settings();
        foreach($settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = &app::get('ome')->getConf($set);
        }

        $render = kernel::single('base_render');
        $render->pagedata['setData'] = $setData;
        
        $html = $render->fetch('admin/setting.html','taoguanallocate');
        return $html;
    }
    
    function all_settings(){
        $all_settings =array(
             'taoguanallocate.appropriation_type',
            );
        return $all_settings;
    }
}
