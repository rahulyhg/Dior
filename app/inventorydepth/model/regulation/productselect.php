<?php
/**
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_mdl_regulation_productselect extends dbeav_model {

    public $tmp_filter = '';

    public function get_tmp_filter($init_bn) {
        if($this->tmp_filter) return $this->tmp_filter;

        $tmp_filter = kernel::single('inventorydepth_regulation_apply')->fetch_merchandise_filter($init_bn);

        return $tmp_filter;
    }
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $tmp_filter = $this->get_tmp_filter($filter['init_bn']);

        $tmp_filter = array_merge_recursive((array)$filter,(array)$tmp_filter);

        $list = parent::getList($cols, $tmp_filter, $offset, $limit, $orderType);

        return $list;
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        return parent::_filter($filter,$tableAlias,$baseWhere);
    }

    public function count($filter=null){
        $tmp_filter = $this->get_tmp_filter($filter['init_bn']);
        
        $count = parent::count($tmp_filter);

        return $count;
    }

    public function doRemove($init_bn,$id) {
        $tmp_filter = $this->get_tmp_filter($init_bn);
        if($tmp_filter['id'][0] == '_ALL_') {
            $tmp_filter['id|notin'][] = $id;
        }else{
            $key = array_search($id,$tmp_filter['id']);
            unset($tmp_filter['id'][$key]);
        }

        kernel::single('inventorydepth_regulation_apply')->store_merchandise_filter($init_bn,$tmp_filter);
        return true;
    }

    public function modifier_release_stock($row){
        return $row;
    }

    public function table_name($real=false){
        $table_name = 'shop_skus';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

}
