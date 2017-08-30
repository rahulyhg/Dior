<?php
class wms_ctl_admin_delivery_print extends desktop_controller{
    var $name = "快递单模板";
    var $workground = "setting_tools";

    function index(){
        $params = array(
                    'title'=>'快递单模板',
                    'actions' => array(
                        array(
                            'label' => '新增模板',
                            'href' => 'index.php?app=wms&ctl=admin_delivery_print&act=addTmpl&finder_id='.$_GET['finder_id'],
                            'target' => "_blank",
                        ),
                        array(
                            'label' => '导入模板',
                            'href' => 'index.php?app=wms&ctl=admin_delivery_print&act=importTmpl',
                            'target' => "dialog::{width:400,height:300,title:'导入快递单模板'}",
                        ),

                    ),
                    'use_buildin_new_dialog' => false,
                    'use_buildin_set_tag'=>false,
                    'use_buildin_recycle'=>true,
                    'use_buildin_export'=>false,
                    'use_buildin_import'=>false,
        );
        $this->finder('wms_mdl_print_tmpl', $params);
    }

    /**
     * 新增模板
     *
     */
    function addTmpl(){
        $this->pagedata['tmpl'] = array(
            'prt_tmpl_title'=>'',
            'prt_tmpl_width'=>240,
            'prt_tmpl_height'=>135,
        );
        $printObj = &app::get('wms')->model('print_tmpl');
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;
        $this->pagedata['elements'] = $printObj->getElements();
		$this->pagedata['title'] = '新增模板';
        $this->singlepage('admin/delivery/dly_print_edit.html');
    }

    /**
     * 导入模板
     *
     */
    function importTmpl(){

        $this->display('admin/delivery/dly_print_import.html');
    }

    /**
     * 上传模板操作
     *
     */
    function doUploadPkg(){
        header("content-type:text/html; charset=utf-8");
        $file = $_FILES['package'];
        $msg = kernel::single('wms_print_tmpl')->upload_tmpl($file);

        if ($msg=='success'){
            $result = true;
            $msg = "上传完成";
        }
        else{
            $result = false;
        }
        echo "<script>";
        if ($result){
            echo "parent.MessageBox.success('".$msg."');";
            echo "var fg = parent.finderGroup;";
            echo "for(fid in fg){";
            echo "if(fid){";
            echo "fg[fid].refresh(); ";
            echo "}";
            echo "}";
            echo "parent.$$('.dialog').getLast().retrieve('instance').close();";

        }else{
            echo "parent.MessageBox.error('".$msg."');";
        }
        echo "</script>";
        exit;
    }

    /**
     * 编辑模板
     *
     * @param int $tmpl_id
     */
    function editTmpl($tmpl_id){
        $this->begin('index.php?app=wms&ctl=admin_delivery_print');
        if ($tmpl_id == ''){
            $this->end(false,'无效的快递单模板id');
        }
        $this->pagedata['dpi'] = 96;
        $this->pagedata['base_dir'] = kernel::base_url();
        $printObj = &app::get('wms')->model('print_tmpl');
        $data = $printObj->dump($tmpl_id);
        if(!empty($data)){
            $this->pagedata['tmpl'] = $data;
            if($data['file_id']){
                $this->pagedata['tmpl_bg'] = 'index.php?app=wms&ctl=admin_delivery_print&act=showPicture&p[0]='.$data['file_id'];
            }
            //$this->pagedata['elements'] = $printObj->getElements();
             $elements = $printObj->getElements();
            #获取用户自定义的打印项
            $_arr_self_elments = app::get('wms')->getConf('wms.delivery.print.selfElments');
            #获取每个快递单对应的自定义打印项
            $arr_self_elments = $_arr_self_elments['element_'.$tmpl_id];
            if(!empty($arr_self_elments)){
                $_value =  array_values($arr_self_elments['element']);
                $_key = array_keys($arr_self_elments['element']);
                #把原来键中的+号替换掉
                $new_key = str_replace('+', '_', $_key[0]);
                $new_self_elments[$new_key] = $_value[0];
                #合并系统打印项和用户自定义打印项
                $all_elements = array_merge($elements,$new_self_elments); 
            }else{
                $all_elements = $elements;
            }
            $this->pagedata['elements'] = $all_elements; 
		    $this->pagedata['title'] = '编辑模板';
            $this->singlepage('admin/delivery/dly_print_edit.html');
        }else{
            $this->end(false,'无效的快递单模板id');
        }
    }

    /**
     * 上传背景图片页面
     *
     * @param int $print_id
     */
    function uploadBg($print_id=0){
        $this->pagedata['dly_printer_id'] = $print_id;
        $this->display('admin/delivery/dly_print_uploadbg.html');
    }

    function extName($file){
        return substr($file,strrpos($file,'.'));
    }

    /**
     * 处理上传的图片
     *
     */
    function doUploadBg(){
        $ss = kernel::single('base_storager');
        $extname = strtolower($this->extName($_FILES['background']['name']));
        if($extname=='.jpg' || $extname=='.jpeg'){
            $id = $ss->save_upload($_FILES['background'],"file","",$msg);//返回file_id;
        }else{
            echo "<script>parent.MessageBox.error('请上传JPG格式的图片');</script>";
            return false;
        }
        $this->doneUploadBg(basename($id));
        echo "<script>parent.MessageBox.success('上传完成');</script>";
    }

    /**
     * 显示背景图片
     *
     * @param string $file
     */
    function showPicture($file){
        header('Content-Type: image/jpeg');

        // 读缓存，保存30天
        $storagerObj = kernel::single('base_storager');
        $imgUrl = $storagerObj->getUrl($file,"file");

        $memKey = sprintf("express_bg:%s",$imgUrl);

        $imgContent = cachecore::fetch($memKey);

        if (!$imgContent) {
            // 当背景图片加载失败时，显示失败提示
            $imgContent = file_get_contents($imgUrl);

            if (!$imgContent) {
                $imgContent = file_get_contents(app::get('wms')->res_dir.'/express_bg_error.jpg');
            }

            cachecore::store($memKey, $imgContent, 2592000);
        }

        echo $imgContent;
    }

    function printTest(){
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;

        if($_POST['tmp_bg']){
            $this->pagedata['bg_id'] = $_POST['tmp_bg'];
        }else if($_POST['prt_tmpl_id']){
            $printTmpl = app::get('wms')->model('print_tmpl');

            $printTmpl = $printTmpl->dump($_POST['prt_tmpl_id'],'file_id');

            $this->pagedata['bg_id'] = $printTmpl['file_id'];
        }


        $this->display('admin/delivery/express_print_test.html');


    }


    /**
     * 保存模板
     *
     */
    function saveTmpl(){
        $this->begin();
        $printObj = &app::get('wms')->model('print_tmpl');
        $print = $_POST;

        if ($print['prt_tmpl_title'] == ''){
            $this->end(false,'请输入快递单名称');
        }
        if ($print['prt_tmpl_offsety']=='' || !is_numeric($print['prt_tmpl_offsety'])){
            $this->end(false,'请输入正确的纵向');
        }
        if ($print['prt_tmpl_offsetx']=='' || !is_numeric($print['prt_tmpl_offsetx'])){
            $this->end(false,'请输入正确的横向');
        }



        if (!empty($_POST['tmp_bg'])){
            $print['file_id'] = $_POST['tmp_bg'];
        }

        if ($print['prt_tmpl_id']){
            $re = $printObj->save($print);
            $tpl_id = $print['prt_tmpl_id'];
        }else {
            $re = $printObj->save($print);
            $tpl_id = $print['prt_tmpl_id'];
        }
        if ($re){
            $this->end(true,'保存成功');
        }else {
            $this->end(false,'保存失败');
        }

    }

    /**
     * 添加一个新的模板，与选择的模板格式相似
     *
     * @param int $tmpl_id
     */
    function addSameTmpl($tmpl_id){
        $this->begin('index.php?app=wms&ctl=admin_delivery_print');
        if ($tmpl_id == ''){
            $this->end(false,'无效的快递单模板id');
        }
        $this->pagedata['dpi'] = 96;
        $this->pagedata['base_dir'] = kernel::base_url();
        $printObj = &app::get('wms')->model('print_tmpl');
        $data = $printObj->dump($tmpl_id);
        if($data){
            unset($data['prt_tmpl_id']);
            $this->pagedata['tmpl'] = $data;
            if($data['file_id']){
                $this->pagedata['tmpl_bg'] = 'index.php?app=wms&ctl=admin_delivery_print&act=showPicture&p[0]='.$data['file_id'];
            }

            $this->pagedata['elements'] = $printObj->getElements();
            $this->pagedata['title'] = '新增相似模板';
            $this->singlepage('admin/delivery/dly_print_edit.html');
        }else {
            $this->end(false,'无效的快递单模板id');
        }

    }

    /**
     * 更新背景图片的显示
     *
     * @param string $file
     */
    function doneUploadBg($file){
        if($file){
            $url = 'index.php?app=wms&ctl=admin_delivery_print&act=showPicture&p[0]='.$file;
            echo '<script>
                if(parent.$("dly_printer_bg")){
                    parent.$("dly_printer_bg").value = "'.$file.'";
        }else{
          new parent.Element("input",{id:"dly_printer_bg",type:"hidden",name:"tmp_bg",value:"'.$file.'"}).inject(parent.$("dly_printer_form"));
        }
        parent.printer_editor.dlg.close();
        parent.printer_editor.setPicture("'.$url.'");
            </script>';
        }else{
            var_dump(__LINE__,$file);
        }
    }

    /**
     * 下载模板包
     *
     */
    function downloadTmpl($tmpl_id){
        $printObj = &app::get('wms')->model('print_tmpl');
        $tmpl = $printObj->dump($tmpl_id,'prt_tmpl_title,prt_tmpl_width,prt_tmpl_height,prt_tmpl_data,file_id');

        $tar = kernel::single('ome_utility_tar');
        $tar->addFile('info',serialize($tmpl));
        if($tmpl['file_id']){
            $ss = kernel::single('base_storager');
            $a = $ss->getUrl($tmpl['file_id'],"file");//echo file_get_contents($a);die;
            $tar->addFile('background.jpg',file_get_contents($a));
        }

        $charset = kernel::single('base_charset');
        $name = $charset->utf2local($tmpl['prt_tmpl_title'],'zh');
        @set_time_limit(0);
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header('Content-type: application/octet-stream');
        header('Content-type: application/force-download');
        header('Content-Disposition: attachment; filename="'.$name.'.dtp"');
        $tar->getTar('output');
    }

    /**
     * 启用模板
     *
     */
    function ableTmpl(){//TODO begin end
        $printObj = &app::get('wms')->model('print_tmpl');
        $filter['prt_tmpl_id'] = $_POST['prt_tmpl_id'];
        $data['shortcut'] = 'true';
        $rs = $printObj->update($data, $filter);
        if ($rs){
            exit("启用操作成功");
        }exit("操作失败");
    }

    /**
     * 禁用模板
     *
     */
    function disableTmpl(){//TODO begin end
        $printObj = &app::get('wms')->model('print_tmpl');
        $filter['prt_tmpl_id'] = $_POST['prt_tmpl_id'];
        $data['shortcut'] = 'false';
        $rs = $printObj->update($data, $filter);
        if ($rs){
            exit("禁用操作成功");
        }exit("操作失败");
    }
    #自定义模板
    function selfTmpl(){
        #模板id
        $prt_tmpl_id = $_GET['prt_tmpl_id'];
        $this->pagedata['prt_tmpl_id'] = $prt_tmpl_id;
        #获取用户自定义的打印项
        $_arr_self_elment = app::get('wms')->getConf('wms.delivery.print.selfElments');
        $arr_self_elment = $_arr_self_elment['element_'.$prt_tmpl_id];
        #自定义打印项的权重
        if(isset($arr_self_elment)){
            $key = array_keys($arr_self_elment['element']);
            $elments = explode('+', $key[0]);
            foreach($elments as $v){
                #获取打印项的权重
                if($v == 'n'){
                    $this->pagedata['n'] = 'true';
                }else{
                    $this->pagedata[$v] = $arr_self_elment['weight'][$v]['weight'];
                }
            }
        }
        $this->display('admin/delivery/dly_print_selftmp.html');
    }
    #保存自定义的打印方式
    function doSelfTmlp(){
        header("Content-type: text/html; charset=utf-8");
        #模板id
        $tmlp_id = 'element_'.$_POST['prt_tmpl_id'];
        $_weight = $_POST['dly'];
        $elments = $_POST['delivery'];
        $_elments = $elments;
        #自定义的打印项键
        unset($_elments['n']);
        #去除换行后，打印项数量在2-5个之间
        $_count = count($_elments);
        if($_count > 5){
            echo "<script>alert('最多不超过 5 个');</script>";exit;
        }
        if( $_count < 2){
            echo "<script>alert('至少选择 2 个');</script>";exit;
        }
        $self_elments = $this->selfPrintElments();
        $_elments = array_keys($_elments);
        $elment_key = array();
        $_i = 0;
        foreach($_elments as $v){
            if(strlen($_weight[$v]['weight'])<=0){
                echo "<script>alert('请为选中的打印项设置权重');</script>";exit;
            }
            #检测权重$_weight[$v]['weight']，是否重复
            if($_i > 0){
                if(array_key_exists($_weight[$v]['weight'], $elment_key)){
                    echo "<script>alert('请不要设置相同权重!');</script>";exit;
                }
            }
            $elment_key[$_weight[$v]['weight']] = $v;
            $elment_name[$_weight[$v]['weight']] = $self_elments[$v];
            $_i++;
        }
        #按键名倒序排序
        krsort($elment_key);
        krsort($elment_name);
        #检测前端原始提交的数据中，是否包含换行
        if($elments['n'] == 'true'){
            $elment_key['n'] = 'n';
            $elment_name['n'] = $self_elments['n'];
        }
        $new_elment_key =  implode('+',$elment_key);
        $new_elment_name =  implode('+',$elment_name);
        #本次设置的自定义打印项
        $arr_new_elments[$new_elment_key] = $new_elment_name;
        
        #获取用户所有自定义的打印项
        $selfElments=  app::get('wms')->getConf('wms.delivery.print.selfElments');
        $selfElments[$tmlp_id]['element'] = $arr_new_elments;
        $selfElments[$tmlp_id]['weight'] = $_weight;
        #保存自定义打印项
        app::get('wms')->setConf('wms.delivery.print.selfElments',$selfElments);
        echo "<script> parent.printer_editor.elments.close(); parent.history.go(0);</script>";
    }
    #自定义打印项键值对
    function selfPrintElments(){
        return array(
            'bn' => '货号',
            'pos' => '货位',
            'name' => '货品名称',
            'spec' => '规格',
            'amount' => '数量',
            'new_bn_name' => '商品名称',
            'goods_bn' => '商家编码',
            'goods_bn2' => '商品编号',//历史遗留问题,商家编码就是商品编号
            'n' => '换行'
           );
    }
}
?>
