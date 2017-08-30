<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class desktop_ctl_export extends desktop_controller{

    public function __construct($app)
    {
        parent::__construct($app);
    }

    function getTemps(){
        $type = $_GET['type'];
        $exptempObj = app::get('desktop')->model('export_template');
        $temps =$exptempObj->getList('et_id,et_name',array('et_type'=>$type),0,-1);
        $this->pagedata['temps'] = $temps;
        return $this->display("export/template.html");
    }

    function saveTemp(){
        $data = array(
            'et_name' => $_POST['extmp_name'],
            'et_type' => $_POST['type'],
            'et_filter' => serialize(array('fields'=>$_POST['content'],'need_detail'=>$_POST['need_detail']))
        );
        $extempObj = app::get('desktop')->model('export_template');
        $extempObj->save($data);
    }

    function addTemp(){
        $this->pagedata['type'] = $_POST['type'];
        $this->pagedata['content'] = $_POST['content'];
        $this->pagedata['need_detail'] = $_POST['need_detail'];
        $this->display('export/addtemplate.html');
    }

    function getTempDetail(){
        $tp_id = $_GET['tp_id'];
        $exptempObj = app::get('desktop')->model('export_template');
        $tempInfo = $exptempObj->getList('et_type,et_filter',array('et_id'=>$tp_id),0,1);

        $curr_filter = unserialize($tempInfo[0]['et_filter']);
        $full_object_name = $tempInfo[0]['et_type'];
        $all_columns = kernel::single('desktop_finder_export')->get_all_columns($full_object_name);

        $fields_str ='';
        foreach(explode(',',$curr_filter['fields']) as $field){
            if(isset($all_columns[$field])){
                $fields_str .= $all_columns[$field]['label']." ,";
            }
        }
        $fields_str = substr($fields_str,0,strlen($fields_str)-2);

        $this->pagedata['export_fields_msg'] = $fields_str;
        $this->pagedata['need_detail'] = $curr_filter['need_detail'];
        return $this->display("export/templatedetail.html");
    }
}