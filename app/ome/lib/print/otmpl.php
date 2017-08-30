<?php
/**
* 打印模板处理类
*
* @author chenping<chenping@shopex.cn>
* @version 2012-4-18 15:01
*/
class ome_print_otmpl
{

    function __construct(&$app)
    {
        $this->app = $app;
    }

    /**
     * 获取模板最后更新时间
     *
     * @return int
     * @author
     * @param string $path 模板路径
     **/
    public function last_modified($path)
    {
        $id = substr(strrchr($path, '/'), 1);
        $otmplModel = $this->app->model('print_otmpl');
        $last_modified = $otmplModel->select()->columns('last_modified')->where('id=?',(int)$id)->limit(0,1)->instance()->fetch_one();

        return $last_modified ? $last_modified : time();
    }

    /**
     * 获取模板内容
     *
     * @return String
     * @author
     * @param string $path 模板路径
     **/
    public function get_file_contents($path)
    {
        $id = substr(strrchr($path, '/'), 1);
        $otmplModel = $this->app->model('print_otmpl');
        $content = $otmplModel->select()->columns('content')->where('id=?',(int)$id)->limit(0,1)->instance()->fetch_one();
        $content = htmlspecialchars_decode($content);
        return $content ? $content : NULL;
    }

    //多维数组转成一维数组，
    static function array_to_flat($array,&$ret,$p_key=null){
        foreach($array as $key=>$item){
            if($p_key != null){
                $key = $p_key."[".$key."]";
            }
           if(is_array($item)){
               self::array_to_flat($item,$ret,$key);
           }else{
               $ret[$key] = $item;
           }
        }
    }

    /**
     * 打印模板公共方法
     *
     * @return void
     * @author chenping<chenping@shopex.cn>
     * @param int $id 模板ID
     * @param string $type 模板类型
     **/
    public function printOTmpl($id,$type,$controller)
    {
        if (!$type) {
            $this->message($controller,$this->app->_('请先选择打印模板类型!'));
            return;
        }

        $otmplModel = $this->app->model('print_otmpl');
        if (!$id) {
            //指定店铺模板
            if ($controller->pagedata['allItems']) {
                $deli = current($controller->pagedata['allItems']);
                $shopModel = $this->app->model('shop');
                $shopInfo = $shopModel->getList('shop_bn',array('shop_id'=>$deli['shop_id']),0,1);
                $shopInfo = current($shopInfo);
                $row = $otmplModel->select()->columns('id,title')
                ->where('type=?',$type)
                ->where('deliIdent=?',$shopInfo['shop_bn'])
                ->where('open=?','true')->limit(0,1)
                ->instance()->fetch_row();
                $id = $row['id']; $title = $row['title'];
            }
            if (!$id) {
                // 默认模板
                $row = $otmplModel->select()->columns('id,title')
                ->where('is_default=?','true')
                ->where('type=?',$type)
                ->instance()->fetch_row();
                $id = $row['id']; $title = $row['title'];
                if (!$id) {
                    $msg = $this->app->_('请先设置默认').$otmplModel->otmpl[$type]['name'];
                    $this->message($controller,$msg);
                    return;
                }
            }
        }else{
            $title = $otmplModel->select()->columns('title')
            ->where('id=?',$id)->instance()->fetch_one();
        }

        $controller->pagedata['current_otmpl_name'] = $title;
        $controller->pagedata['title'] = $title.$this->app->_('打印');
        $controller->pagedata['request_uri'] = kernel::single('base_component_request')->get_request_uri();

        //获取所有未删除模板
        $otmplList = $otmplModel->select()->columns('id,title')
        ->where('disabled=?','false')
        ->where('type=?',$type)
        ->where('aloneBtn=?','false')
        ->where('open=?','true')
        ->instance()->fetch_all();

        if (!in_array($id, array_map('current', $otmplList))) {
            $otmplList = array();
        }
        $controller->pagedata['otmplList'] = $otmplList;
        $controller->pagedata['current_otmpl_id'] = $id;

        $post = kernel::single('base_component_request')->get_post();
        if ($post) {
            self::array_to_flat($post,$ret);
            $controller->pagedata['postData'] = $ret;
        }


        $path = 'admin/print/otmpl/'.$id;
        $controller->singlepage('print_otmpl:/'.$path,$otmplModel->otmpl[$type]['app']);

        $controller->display($otmplModel->otmpl[$type]['printpage'],$otmplModel->otmpl[$type]['app']);
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function message($controller,$msg)
    {
        $controller->pagedata['err'] = 'true';
        $controller->pagedata['base_dir'] = kernel::base_url();
        $controller->pagedata['time'] = date("Y-m-d H:i:s");
        $controller->pagedata['msg'] = $msg;
        $controller->singlepage('admin/delivery/message.html','ome');
        $controller->display('admin/delivery/print.html','ome');
    }
}