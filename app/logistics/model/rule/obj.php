<?php
class logistics_mdl_rule_obj extends dbeav_model{
    /**
    *添加区域排它规则
    */
    function create_rule_obj($data){
        $area = $data['area'];

        $rule_id = $data['rule_id'];
        if(is_array($area)){
            foreach($area as $ak=>$av){

                $region_id = $av['region_id'];
                $region = kernel::single('eccommon_regions')->getOneById($region_id,'region_grade,local_name');
                $region_grade = $region['region_grade'];

                if($region_grade==2){
                    #二级区域添加其子区域为规则里
                    #如果没有下级则添加自身区域
                    $child = $this->app->model('area')->getAllChild($region_id);
                    if($child){
                        foreach($child as $ck=>$cv){

                            $this->createAreaRule($cv['region_id'],$data);
                        }
                    }else{
                        $this->createAreaRule($region_id,$data);
                    }

                }else{
                    $this->createAreaRule($region_id,$data);

                }


            }
        }

        return true;
    }

    /**
    *删除规则
    */
    function delete_rule($item_id,$type=''){
        $db = kernel::database();
        if($type=='obj'){
            $result = $db->exec('delete from sdb_logistics_region_rule WHERE obj_id='.$item_id);
            $result = $db->exec('delete from sdb_logistics_rule_items WHERE obj_id='.$item_id);
            $item_result = $db->exec('delete from sdb_logistics_rule_obj WHERE obj_id='.$item_id);

        }else{
            $result = $db->exec('delete from sdb_logistics_region_rule WHERE item_id='.$item_id);

            if($result){
                $item_result = $db->exec('delete from sdb_logistics_rule_items WHERE item_id='.$item_id);
            }
        }
        //$this->app->model('rule')->branchRuleData(true);
        return $item_result;
    }

    /**
    *区域排它规则详情
    *
    */
    function detail_rule_obj($obj_id,$region_grade){
        $dly_corpObj = &app::get('ome')->model('dly_corp');
        $rule_obj = $this->app->model('rule_obj')->getlist('region_id,region_name,set_type,obj_id,rule_id',array('obj_id'=>$obj_id),0,1);

        $rule_obj = $rule_obj[0];

        $rows = $dly_corps = array();
        $rows = $dly_corpObj->getList('corp_id,name');
        foreach ($rows as $val) {
            $dly_corps[$val['corp_id']] = $val['name'];
            unset($val);
        }
        unset($rows);

        $items = $this->app->model('rule_items')->getlist('*',array('obj_id'=>$obj_id),0,-1,'min_weight ASC');
        foreach($items as $k=>$v){
            $corp_id = $v['corp_id'];
            if($corp_id=='-1'){
                $items[$k]['corp_name'] = '人工审单';
            }else{
                $items[$k]['corp_name']=$dly_corps[$corp_id];
                $items[$k]['second_corp_name']=$dly_corps[$v['second_corp_id']];
            }

        }
        $rule_obj['items'] = $items;
        return $rule_obj;
    }

    /**
    * 编辑排它规则
    */
    function edit_rule_obj($data){

        $rule_obj = $this->getlist('set_type,region_id',array('obj_id'=>$data['obj_id']),0,1);
        if($rule_obj[0]['set_type']!=$data['set_type']){
            $this->db->exec('delete from sdb_logistics_rule_items WHERE obj_id='.$data['obj_id']);
            $this->db->exec('delete from sdb_logistics_region_rule WHERE obj_id='.$data['obj_id']);
        }
        $region_id = $rule_obj[0]['region_id'];

        $obj_data = array();
        $obj_data['set_type']=$data['set_type'];
        $obj_data['obj_id'] = $data['obj_id'];
        $this->save($obj_data);
        if($data['set_type']=='weight'){
            $area_weight_conf = array();
            foreach($data['min_weight'] as $mk=>$mv){
                $area_weight_conf[] = array(
                    'min_weight'=>$mv,
                    'max_weight'=>$data['max_weight'][$mk],
                    'corp_id'=>$data['corp_id'][$mk],
                    'second_corp_id'=>$data['second_corp_id'][$mk],
                    'item_id'=>$data['item_id'][$mk],

                );
            }
            foreach($area_weight_conf as $wk=>$wv){
                $items_data = array();
                $items_data['obj_id'] = $data['obj_id'];
                $items_data['min_weight'] = $wv['min_weight'];
                $items_data['max_weight'] = $wv['max_weight'];
                $items_data['corp_id'] = $wv['corp_id'];
                $items_data['second_corp_id'] = $wv['second_corp_id'];
                #判断明细是否已被删除
                $items = $this->app->model('rule_items')->getlist('*',array('item_id'=>$wv['item_id']),0,1);

                if(!empty($items)){
                    $items_data['item_id'] = $wv['item_id'];
                    unset($area_weight_conf[$wk]['item_id']);
                }
                $this->app->model('rule_items')->save($items_data);

                $item_id = $items_data['item_id'];
                $obj_id = $obj_data['obj_id'];
                if(empty($items)){
                    $this->db->exec("insert into sdb_logistics_region_rule(item_id,region_id,obj_id) VALUES($item_id,$region_id,$obj_id)");
                }


            }

        }else if($data['set_type']=='noweight'){
            if($rule_obj){
                $items_data= array(
                    'obj_id'=>$obj_data['obj_id'],
                    'corp_id'=>$data['default_corp_id'],
                    'second_corp_id'=>$data['default_second_corp_id'],
                );
                if($data['default_item_id']){
                    $items_data['item_id'] = $data['default_item_id'];
                }
                $this->app->model('rule_items')->save($items_data);
                if(!$data['default_item_id']){
                    $item_id = $items_data['item_id'];
                    $obj_id = $obj_data['obj_id'];
                    $this->db->exec("insert into sdb_logistics_region_rule(item_id,region_id,obj_id) VALUES($item_id,$region_id,$obj_id)");
                }
            }

        }
        //$this->app->model('rule')->branchRuleData(true);

    }

    /**
    * 批量显示排它规则
    */

    function group_rule_obj($rule_id,$obj_id){
        $dly_corpObj = &app::get('ome')->model('dly_corp');
        if($obj_id){
            $obj_id = implode(',',$obj_id);
        }
        $sql = 'SELECT i.*,o.set_type FROM sdb_logistics_rule_items as i LEFT JOIN sdb_logistics_rule_obj as o on i.obj_id=o.obj_id WHERE i.obj_id in ('.$obj_id.') group by i.min_weight,i.max_weight,i.corp_id,o.set_type';

        $item_list = $this->db->select($sql);

        $rule_list = array();
        foreach($item_list as $k=>$v){
            $dly_corp = $dly_corpObj->dump($v['corp_id'],'name');

            if($rule_list['item_list'][$v['set_type']]){
                $rule_list['item_list'][$v['set_type']][$k]=$v;
                $rule_list['item_list'][$v['set_type']][$k]['corp_name'] = $dly_corp['name'];
            }else{
                $rule_list['item_list'][$v['set_type']][$k]=$v;
                $rule_list['item_list'][$v['set_type']][$k]['corp_name'] = $dly_corp['name'];
            }

        }

        $obj_sql = 'SELECT region_name,region_id FROM sdb_logistics_rule_obj WHERE obj_id in ('.$obj_id.')';

        $obj_list = $this->db->select($obj_sql);

        $rule_list['region_list'] = $obj_list;
        return $rule_list;
    }

    /**
    * 排量更新排它规则
    */
    function update_rule_obj($data){
        #删除现有规则
        $obj_id = $data['obj_id'];
        $region_sql = 'DELETE FROM sdb_logistics_region_rule WHERE obj_id in('.$obj_id.')';
        $items_sql = 'DELETE FROM sdb_logistics_rule_items WHERE obj_id in('.$obj_id.')';
        $this->db->exec($region_sql);
        $this->db->exec($items_sql);
        #添加现有区域规则
        $obj_id = explode(',',$obj_id);

        foreach($obj_id as $k=>$v){
            $obj_data = array();
            $obj_data['obj_id'] = $v;
            $obj_data['set_type'] = $data['set_type'];
            $this->save($obj_data);
            $obj_list = $this->dump($v,'region_id');
            $region_id = $obj_list['region_id'];
            if($data['set_type']=='weight'){
                $area_weight_conf = array();
                foreach($data['min_weight'] as $mk=>$mv){
                    $area_weight_conf[] = array(
                        'min_weight'=>$mv,
                        'max_weight'=>$data['max_weight'][$mk],
                        'corp_id'=>$data['corp_id'][$mk],
                        'second_corp_id'=>$data['second_corp_id'][$mk],
                        'item_id'=>$data['item_id'][$mk],

                    );
                }
                foreach($area_weight_conf as $wk=>$wv){
                    $items_data = array();
                    $items_data['obj_id'] = $v;
                    $items_data['min_weight'] = $wv['min_weight'];
                    $items_data['max_weight'] = $wv['max_weight'];
                    $items_data['corp_id'] = $wv['corp_id'];
                    $items_data['second_corp_id'] = $wv['second_corp_id'];

                    $this->app->model('rule_items')->save($items_data);

                    $item_id = $items_data['item_id'];
                    $obj_id = $v;
                    if($wv['item_id']!=''){

                        $this->db->exec("insert into sdb_logistics_region_rule(item_id,region_id,obj_id) VALUES($item_id,$region_id,$obj_id)");
                    }
                }

            }else if($data['set_type']=='noweight'){
                $items_data= array(
                    'obj_id'=>$v,
                    'corp_id'=>$data['default_corp_id'],
                    'second_corp_id'=>$data['default_second_corp_id'],
                );
                $this->app->model('rule_items')->save($items_data);
                if(!$data['default_item_id']){
                    $item_id = $items_data['item_id'];
                    $obj_id = $v;
                    $this->db->exec("insert into sdb_logistics_region_rule(item_id,region_id,obj_id) VALUES($item_id,$region_id,$obj_id)");
                }
            }
        }

        //$this->app->model('rule')->branchRuleData(true);
    }

    /**
    * 三级区域查询
    */
    function areaFilter($region_id,$branch_id){

        $child = $this->app->model('area')->getAllChild($region_id);
        foreach($child as $ck=>$cv){

            $rule_obj = $this->app->model('rule_obj')->getlist('obj_id',array('region_id'=>$cv['region_id'],'branch_id'=>$branch_id),0,1);
            if($rule_obj){
                $child[$ck]['flag']=1;
                $child[$ck]['obj_id']=$rule_obj[0]['obj_id'];
            }

        }
        return $child;


    }

    /**
    * 执行创建排它区域规则
    */

    function createAreaRule($region_id,$data){
        $rule_id=$data['rule_id'];
        $rule = $this->app->model('rule')->dump($data['rule_id'],'branch_id');
        if($data['branch_id']){
            $branch_id = $data['branch_id'];
        }else{
            $branch_id = $rule['branch_id'];
        }
        $region = kernel::single('eccommon_regions')->getOneById($region_id,'region_grade,local_name');
        #可作判断#
        $rule_obj = $this->app->model('rule_obj')->getlist('obj_id',array('region_id'=>$region_id,'branch_id'=>$branch_id),0,1);
        if($rule_obj){
            #删除已存在三级区域规则
            if($region['region_grade']==3){
                $this->delete_rule($rule_obj[0]['obj_id'],'obj');
            }
        }
        $obj_data = array();
        $obj_data['rule_id'] = $data['rule_id'];
        $obj_data['region_id'] = $region_id;
        $obj_data['region_name']=$region['local_name'];
        #获取
        $obj_data['region_grade']=$region['region_grade'];

        $obj_data['rule_type']='other';
        $obj_data['set_type']=$data['set_type'];
        $obj_data['branch_id']=$branch_id;
        $rule_obj = $this->app->model('rule_obj')->save($obj_data);

        if($data['set_type']=='weight'){
            $area_weight_conf = array();
            if($data['min_weight']){
                foreach($data['min_weight'] as $mk=>$mv){
                    $area_weight_conf[] = array(
                        'min_weight'=>$mv,
                        'max_weight'=>$data['max_weight'][$mk],
                        'corp_id'=>$data['corp_id'][$mk],
                        'second_corp_id'=>$data['second_corp_id'][$mk],
                    );
                }
            }

            if($rule_obj){
                foreach($area_weight_conf as $wk=>$wv){
                    $items_data = array();
                    $items_data['obj_id'] = $obj_data['obj_id'];
                    $items_data['min_weight'] = $wv['min_weight'];
                    $items_data['max_weight'] = $wv['max_weight'];
                    $items_data['corp_id'] = $wv['corp_id'];
                    $items_data['second_corp_id'] = $wv['second_corp_id'];

                    $this->app->model('rule_items')->save($items_data);

                    $item_id = $items_data['item_id'];
                    $obj_id = $obj_data['obj_id'];
                    $this->db->exec("insert into sdb_logistics_region_rule(item_id,region_id,obj_id) VALUES($item_id,$region_id,$obj_id)");
                }
            }

        }else if($data['set_type']=='noweight'){
            if($rule_obj){
                $items_data= array(
                    'obj_id'=>$obj_data['obj_id'],
                    'corp_id'=>$data['default_corp_id'],
                    'second_corp_id'=>$data['default_second_corp_id'],
                );
                $this->app->model('rule_items')->save($items_data);
                $item_id = $items_data['item_id'];
                $obj_id = $obj_data['obj_id'];
                $this->db->exec("insert into sdb_logistics_region_rule(item_id,region_id,obj_id) VALUES($item_id,$region_id,$obj_id)");
            }
        }

        return true;
    }

    /**
    * 三级区域查询
    */
    function regionFilter($region_id,$branch_id){
        $tmpRow = kernel::single('eccommon_regions')->getAllChildById($region_id,'containSelf');
        if($tmpRow){
            $region_id_arr = array();
            foreach($tmpRow as $k=>$v){
                $region_id_arr[] = $v['region_id'];
            }

            $rule_obj = $this->getList('obj_id', array('rule_type'=>'other','region_id'=>$region_id_arr,'branch_id'=>$branch_id), 0, -1);

            $region_id_list = array();
            foreach ($rule_obj as $obj) {
               $region_id_list[] = $obj['obj_id'];
            }

            return $region_id_list;
        }
    }
}
?>