<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_mdl_print_tmpl_diy extends dbeav_model{

    function __construct($app){
        parent::__construct($app);
    }
    function singlepage($app='ome',$tplname,$data=null){
       // $aTmpl = explode(':',$tplname);
        $controller = $this->app->controller('admin_receipts_print');
        foreach($data as $key=>$val){
            $controller->pagedata[$key] = $val;
        }
/*
        if($tplname == 'admin/eo/eo_print' || $tplname == 'admin/purchase/purchase_print'){
            return $controller->singlepage($tplname.'.html',$app);
        }
*/
        $aRet = $this->getList('*',array('active'=>'true','app'=>$app,'tmpl_name'=>'/'.$tplname));
  
        if($aRet){
            return $controller->singlepage('messenger_'.$app.':/'.$tplname,$app);
        }

        return $controller->singlepage($tplname.'.html',$app);

    }

    function getTitle($ident){
        $row = $this->db->select('select title,path from sdb_sitemaps where action=\'page:'.$ident.'\'');
        if($row[0]['path']){
            $row[0]['path']=substr($row[0]['path'],0,strlen($row[0]['path'])-1);
            $parentRow=$this->db->select('select title,action as link from sdb_sitemaps where node_id in ('.$row[0]['path'].')');
            $parentRow[]=array('title'=>$row[0]['title'],'link'=>$row[0]['action']);
            return $parentRow;
        }

        return $row;
    }

    function _file($app,$name){
        return ROOT_DIR.'/app/'.$app.'/view/'.$name.'.html';
    }

    function get($app,$name){
        $aRet = $this->getList('*',array('active'=>'true','app'=>$app,'tmpl_name'=>$name));
        if($aRet){
            $contents =  $aRet[0]['content'];
        }else{
            $contents =  file_get_contents($this->_file($app,$name));
        }

        $contents = $this->filterContents($contents);

        return $contents;
    }

    function clear($app,$name){
        $sdf['app'] = $app;
        $sdf['tmpl_name'] = $name;
        $sdf['edittime'] = time();
        $sdf['active'] = 'false';
        return $this->save($sdf);
    }

    function tpl_src($matches){
        return '<{'.html_entity_decode($matches[1]).'}>';
    }

    function set($app,$name,$body){
        $body = $this->addfilterContents($app,$name,$body);
        $body = str_replace(array('&lt;{','}&gt;'),array('<{','}>'),$body);
        $body = preg_replace_callback('/<{(.+?)}>/',array(&$this,'tpl_src'),$body);
        $sdf['app'] = $app;
        $sdf['tmpl_name'] = $name;
        $sdf['edittime'] = time();
        $sdf['active'] = 'true';
        $sdf['content'] = $body;
        $rs = $this->save($sdf);
        return $rs;
    }

    function filterContents($contents){
        $s_pos = strpos($contents,'<script');
        if($s_pos){
            $contents = substr($contents,0,$s_pos);
        }

       return $contents;
    }

   function addfilterContents($app,$name,$body){
        $contents =  file_get_contents($this->_file($app,$name));
        $s_pos = strpos($contents,'<script');
        if($s_pos){
            $contents = substr($contents,$s_pos);
            $body .= $contents;
        }
       return $body;
    }
}
