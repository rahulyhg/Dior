<?php
class ome_mdl_goods_cat extends dbeav_model{
    
    function save($aData){             
        $aData['cat_path'] = $this->getCatPath($aData['parent_id']);
        //unlink(MEDIA_DIR.'/goods_cat.data');
        return parent::save($aData);
    }
    
    function getTree(){
        return $this->db->select('SELECT o.cat_name AS text,o.cat_id AS id,o.parent_id AS pid,o.p_order,o.cat_path,
                    is_leaf,o.type_id as type,o.child_count,t.name as type_name FROM sdb_ome_goods_cat o
                    LEFT JOIN sdb_ome_goods_type t on t.type_id=o.type_id ORDER BY o.p_order,o.cat_id');
    }
    function getCatParentById($id,$view='index'){
        if(!$id) return false;
            if(is_array($id)){
                if(implode($id,' , ')==='') return false;
                $sqlString = 'SELECT cat_id,cat_name FROM sdb_ome_goods_cat WHERE parent_id in ('.implode($id,' , ').') order by p_order,cat_id desc';
            }else{
                $sqlString = 'SELECT cat_id,cat_name FROM sdb_ome_goods_cat WHERE parent_id = '.$id.' order by p_order,cat_id desc';
            }
            $default_view=$view?$view:$this->app->getConf('gallery.default_view');
            $result=$this->db->select($sqlString);
            foreach($result as $cat_key=>$cat_value){
                $result[$cat_key]['link']=kernel::mkUrl('gallery',$default_view,array($cat_value['cat_id']));
            }
            return $result;
     }
    function getMap($depth=-1,$cat_id=0){
        $var_depth = $depth;
        $var_cat_id = $cat_id;
        if(isset($this->catMap[$var_depth][$var_cat_id])){
            return $this->catMap[$var_depth][$var_cat_id];
        }
        if($cat_id>0){
            $row = $this->db->select('select cat_path from sdb_ome_goods_cat where cat_id='.intval($cat_id));
            if($depth>0){
                $depth += substr_count($row['cat_path'],',');
            }
            $rows = $this->db->select('select cat_name,cat_id,parent_id,is_leaf,cat_path,type_id from sdb_ome_goods_cat where cat_path like "'.$row['cat_path'].$cat_id.'%" order by cat_path,p_order');
        }else{
            $rows = $this->db->select('select cat_name,cat_id,parent_id,is_leaf,cat_path,type_id from sdb_ome_goods_cat order by p_order');
        }
        $cats = array();
        $ret = array();
        foreach($rows as $k=>$row){
            if($depth<0 || substr_count($row['cat_path'],',') < $depth){
                $cats[$row['cat_id']] = array('type'=>'gcat','parent_id'=>$row['parent_id'],'title'=>$row['cat_name'],'link'=>kernel::mkUrl('gallery','index',array($row['cat_id'])));
            }
        }
        foreach($cats as $cid=>$cat){
            if($cat['parent_id'] == $cat_id){
                $ret[] = &$cats[$cid];
            }else{
                $cats[$cat['parent_id']]['items'][] = &$cats[$cid];
            }
        }
        $this->catMap[$var_depth][$var_cat_id] = $ret;
        return $ret;
    }
    
    function getMapTree($ss=0, $str='└'){
        $var_ss = $ss;
        $var_str = $str;
        if(isset($this->catMapTree[$var_ss][$var_str])){
            return $this->catMapTree[$var_ss][$var_str];
        }
        $retCat = $this->map($this->getTree(),$ss,$str,$no,$num);
        $this->catMapTree[$var_ss][$var_str] = $retCat;
        global $step,$cat;
        $step = '';
        $cat = array();
        return $retCat;
    }

    
    function map($data,$sID=0,$preStr='',&$cat_cuttent,&$step){
        $step++;
        $baseurl=kernel::base_url();
        $default_view=$this->app->getConf('gallery.default_view');
        if($data){
            foreach($data as $i=>$value){
                $id=$data[$i]['id'];
                $cls=($data[$i]['child_count']?'true':'false');
                $link='';//todo kernel::realUrl('gallery',$default_view,array($id),'html',$baseurl);
                if(!$sID){ //第一轮圈套
                    if(empty($data[$i]['pid'])){ //原始节点
                        $cat_cuttent[]=array(
                            'cat_name'=>$data[$i]['text'],
                            'cat_id'=>$data[$i]['id'],
                            'pid'=>$data[$i]['pid'],
                            'type'=>$data[$i]['type'],
                            'type_name'=>$data[$i]['type_name'],
                            'step'=>$step,
                            'p_order'=>$data[$i]['p_order'],
                            'cat_path'=>$data[$i]['cat_path'],
                            'cls'=>$cls,
                            'url'=>$link
                        );
                        unset($data[$i]);
                        $this->map($data,$id,$preStr,$cat_cuttent,$step);
                    }else{ //
                        continue;
                    }
                }else{ //子节点
                    if($sID==$data[$i]['pid']){
                        $cat_cuttent[]=array(
                            'cat_name'=>$data[$i]['text'],
                            'cat_id'=>$data[$i]['id'],
                            'pid'=>$data[$i]['pid'],
                            'type'=>$data[$i]['type'],
                            'type_name'=>$data[$i]['type_name'],
                            'step'=>$step,
                            'p_order'=>$data[$i]['p_order'],
                            'cat_path'=>$data[$i]['cat_path'],
                            'cls'=>$cls,
                            'url'=>$link
                        );
                        unset($data[$i]);
                        $this->map($data,$id,$preStr,$cat_cuttent,$step);
                    }
                }
            }
        }
        $step--;
        return $cat_cuttent;
    }

    function checkTreeSize(){
        $aCount = $this->db->selectrow('SELECT count(*) AS rowNum FROM sdb_ome_goods_cat');
        if($aCount['rowNum'] > 100){
            return false;
        }else{
            return true;
        }
    }
    
    function get_cat_depth(){
        $row = $this->db->selectrow('select cat_path from sdb_ome_goods_cat order by cat_path desc');
        return count(explode(',',$row['cat_path']));
    }
    
    function cat2json($return=false){
        $contents=$this->getMapTree(0,'');
        base_kvstore::instance('template')->store('goods_cat.data',$contents);
        if($return){
            return $contents;
        }else{
            return true;
        }
    }
    
    function getCatPath($parent_id){
        if($parent_id == 0){
            return ',';
        }
        $cat_sdf = $this->dump($parent_id);     
        return $cat_sdf['cat_path'].$cat_sdf['cat_id'].",";
    }

    function getTypeList(){ 
        $sqlString = "SELECT type_id,name FROM sdb_ome_goods_type WHERE disabled = 'false'";
        return $this->db->select($sqlString);
    }
    function propsort($prop=array()){
        if (is_array($prop)){
            foreach($prop as $key => $val){
                $tmpP[$val['ordernum']]=$key;
            }
            ksort($tmpP);
            return $tmpP;
        }
    }
    
    
         
     /*根据查询字符串返回UNMAE 数组
       litie@shopex.cn
     */
     function getCatLikeStr($str){
        
         if(!$str||$str !=''){
            $sql  = 'select cat_id,cat_name from '.$this->table_name(1).' where cat_name like "'.$str.'%" and disabled="false"';         
         }else if($str == '_ALL_'){
            $sql  = 'select cat_id,cat_name from '.$this->table_name(1).' where disabled="false"';
         }
         
         
         
        $_data = $this->db->select($sql);
       
        
        foreach($_data as $d){
            
            $result[] = $d['cat_name'].'&nbsp;'.$d['cat_id'];
        
        }
        
        return json_encode($result);
       
       
     }
     
    function get_cat_list($show_stable=false){
//        if( $contents = base_kvstore::instance('template')->fetch('goods_cat.data')){
//            if(($result=json_decode($contents,true))){
//                if($show_stable){
//                    foreach($result as $key=>$value){
//                        if($result[$key]['step']>1){
//                            $result[$key]['cat_name']=str_repeat(' ',($result[$key]['step']-1)*2).'└'.$result[$key]['cat_name'];
//                        }
//                    }
//                }
//
//                return $result;
//            }else{
//                return $this->cat2json(true);
//            }
//        }else{
            return $this->cat2json(true);
//        }
    }
    
    function toRemove($catid){
        $aCats = $this->db->select('SELECT * FROM sdb_ome_goods_cat WHERE parent_id = '.intval($catid));
        if(count($aCats) > 0){
            trigger_error(app::get('base')->_('删除失败：本分类下面还有子分类'), E_USER_ERROR);
            return false;
        }
        $aGoods = $this->db->select('SELECT goods_id FROM sdb_ome_goods WHERE cat_id = '.intval($catid).' and disabled="false"');
        if(count($aGoods) > 0){
            trigger_error(app::get('base')->_('删除失败：本分类下面还有商品'), E_USER_ERROR);
            return false;
        }
        $row = $this->db->selectrow('SELECT parent_id FROM sdb_ome_goods_cat WHERE cat_id='.intval($catid));
        $parent_id = $row['parent_id'];

        $this->db->exec('DELETE FROM sdb_ome_goods_cat WHERE cat_id='.intval($catid));
        //$this->db->exec('update FROM sdb_ome_goods set cat_id="0" WHERE cat_id='.intval($catid));
        //$this->updateChildCount($parent_id);
        $this->cat2json();
        return true;
    }
    
    

}
