<?php
class omeanalysts_relate_ome_shop{
    public function insert(&$data){
        $aData['relate_table'] = 'ome_shop';
        $aData['relate_key'] = $data['shop_id'];
        $relateObj = &app::get('omeanalysts')->model("relate");
        $relate = $relateObj->dump(array('relate_table'=>'ome_shop','relate_key'=>$data['shop_id']));
        if(!isset($relate['relate_id']) && $aData['relate_key']){
            $relateObj->insert($aData);
        }
        return true;
    }

    /*
    public function delete($filter){
        if(is_array($filter) && $filter){
            $relateObj = &app::get('omeanalysts')->model("relate");
            $delFilter['relate_table'] = 'ome_shop';
            $delFilter['relate_key'] = $filter['shop_id'];

            $relateObj->delete($delFilter);
        }
        return true;
    }
    */

    public function update($data){
        $aData['relate_table'] = 'ome_shop';
        $aData['relate_key'] = $data['shop_id'];
        $relateObj = &app::get('omeanalysts')->model("relate");
        $relate = $relateObj->dump(array('relate_table'=>'ome_shop','relate_key'=>$data['shop_id']));
        if(!isset($relate['relate_id']) && $aData['relate_key']){
            $relateObj->insert($aData);
        }
        return true;
    }
}