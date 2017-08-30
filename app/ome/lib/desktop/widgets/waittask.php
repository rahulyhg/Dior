<?php
class ome_desktop_widgets_waittask implements desktop_interface_widget{
    
    function __construct($app){
        $this->app = $app; 
        $this->render =  new base_render(app::get('ome'));  
    }
    
    function get_title(){
        return app::get('ome')->_("待办事务表");
    }

    function get_html(){
        $render = $this->render;
        $data = array();
        $i = 0;

        //从缓存中获取待处理事项，缓存时间 暂设为 30 分钟更新一次
        $data = $this->fetchDataFromCache();
        if (empty($data)) {
            //获取待办事务的‘今日’，‘订单’，‘发货单’等大项
            foreach(kernel::servicelist('get_todo_row') as $object){
                if(method_exists($object,'get_menugroup')){
                    
                    $data[$i] = $object->get_menugroup();
                    //获取待办事务的‘今日订单’，‘今日未确认’，‘今日已发货’等小项
                    foreach(kernel::servicelist('get_todo_row_'.$data[$i]['type']) as $objects){
                        if(method_exists($objects,'get_menu')){
                                $data[$i]['value'][] = $objects->get_menu();
                        }
                    }
                }
                $i++;
            }

            $this->saveDataToCache($data);
        }
        $render->pagedata['data'] = $data;
        $html = $render->fetch('desktop/widgets/waittask.html');
        return $html;
    }

    function fetchDataFromCache() {

        return cachecore::fetch('widgets_data');
    }

    function saveDataToCache($data) {

        cachecore::store('widgets_data', $data, 1800);
    }

    function get_className(){
        return "";
    }

    function get_width(){
        return "l-1";
    }
    
}

?>