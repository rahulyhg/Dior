<?php
class taoexlib_setting{
    public function view(){
        $settings = $this->all_settings();
        foreach($settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = &app::get('taoexlib')->getConf($set);
        }

        $render = kernel::single('base_render');
        $render->pagedata['setData'] = $setData;
        $html = $render->fetch('admin/setting.html','taoexlib');
        return $html;
    }

    public function all_settings(){
        $all_settings =array(
				  'taoexlib.message.switch',
				  'taoexlib.message.warningnumber',
				  'taoexlib.message.sampletitle',
				  'taoexlib.message.samplecontent',
				  'taoexlib.message.blacklist',
                );
        return $all_settings;
    }
}
