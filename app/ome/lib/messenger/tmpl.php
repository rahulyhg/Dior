<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class ome_messenger_tmpl{
    
     public function last_modified($tplname) 
    {
        $systmpl = app::get('ome')->model('print_tmpl_diy');
       $aRet = $systmpl->getList('*',array('active'=>'true','app'=>'ome','tmpl_name'=>$tplname));
        if($aRet){
              return $aRet[0]['edittime'];    
        }
        return time();
    }

    public function get_file_contents($tplname) 
    { 
       $systmpl = app::get('ome')->model('print_tmpl_diy');
       $aRet = $systmpl->getList('*',array('active'=>'true','app'=>'ome','tmpl_name'=>$tplname));
        if($aRet){
              return $aRet[0]['content'];    
        }
        return null;
        
    }

}
?>