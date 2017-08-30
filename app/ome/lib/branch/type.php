<?php
/**
 * 仓库类型Lib
 * @author xiayuanjun
 * @version 1.0
 */
class ome_branch_type{

    static private $__instance = null;

    static private $__branches = array();

    function __construct(){
        if(!isset(self::$__instance)){
            self::$__instance = app::get('ome')->model('branch');
        }
    }

    /**
     *
     * 获取自建类型的仓库id列表
     * @param null
     * @return array $__branches['ownIds'] 自建仓库id列表数据
     */
	public function getOwnBranchIds(){
        if(!isset(self::$__branches['ownIds'])){
            $tmpBranchList = self::$__instance->getList('branch_id',array('owner'=>'1'));

            $tmpBranchIds = array();
            foreach ($tmpBranchList as $key => $value) {
                $tmpBranchIds[] = $value['branch_id'];
            }
            self::$__branches['ownIds'] = $tmpBranchIds;
        }
        return self::$__branches['ownIds'];
	}

    /**
     *
     * 获取自建类型的仓库列表
     * @param null
     * @return array $__branches['own'] 自建仓库列表数据
     */
	public function getOwnBranchLists(){
        if(!isset(self::$__branches['own'])){
            self::$__branches['own'] = self::$__instance->getList('branch_id,branch_bn,name',array('owner'=>'1'));
        }else{
            return self::$__branches['own'];
        }
	}

    /**
     *
     * 获取第三方类型的仓库列表
     * @param null
     * @return array $__branches['other'] 第三方仓库列表数据
     */
	public function getOtherBranchLists(){
        if(!isset(self::$__branches['other'])){
            self::$__branches['other'] = self::$__instance->getList('branch_id,branch_bn,name',array('owner'=>'2'));
        }else{
            return self::$__branches['other'];
        }
	}
}