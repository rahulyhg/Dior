<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class eccommon_ctl_regions extends desktop_controller{

    var $workground = 'eccommon_center';

	public function __construct($app)
	{
		parent::__construct($app);
		header("cache-control: no-store, no-cache, must-revalidate");
	}

    public function index(){
        $obj_regions_op = kernel::single('eccommon_regions_operation');
        $this->path[]=array('text'=>'配送地区列表');

        if ($obj_regions_op->getTreeSize())
		{
			//超过100条
            $this->pagedata['area'] = $obj_regions_op->getRegionById();
            $this->page('regions/area_treeList.html');
        }
		else
		{
            $obj_regions_op->getMap();
            $this->pagedata['area'] = $obj_regions_op->regions;
            $this->page('regions/area_map.html');
        }
    }

    public function showRegionTreeList($serid,$multi=false)
	{
         if ($serid)
		 {
			$this->pagedata['sid'] = $serid;
         }
		 else
		 {
			$this->pagedata['sid'] = substr(time(),6,4);
         }

         $this->pagedata['multi'] =  $multi;
         $this->singlepage('regions/regionSelect.html');
    }

    public function getChildNode()
	{
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        $this->pagedata['area'] = $obj_regions_op->getRegionById($_POST['regionId']);
        $this->display('regions/area_sub_treeList.html');
    }

    public function getRegionById($pregionid)
	{
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        echo json_encode($obj_regions_op->getRegionById($pregionid));
    }

    /**
     * 添加新地区界面
     * @params string 父级region id
     * @return null
     */
    public function showNewArea($pRegionId=null)
	{
        if ($pRegionId){
            $dArea = app::get('eccommon')->model('regions');
            $this->pagedata['parent'] = $dArea->getRegionByParentId($pRegionId);
        }
        $this->path[] = array('text'=>'添加配送地区');
        $this->display('regions/area_new.html');
    }

    /**
     * 添加新地区
     * @params null
     * @return null
     */
    public function addDlArea()
	{
		$this->begin('index.php?app=eccommon&ctl=regions&act=index');
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        if(!$obj_regions_op->insertDlArea($_POST,$msg)){
            $this->end(false, '新建失败，'.$msg);
        }else
            $this->end(true, '新建成功，地区名称：'.$_POST['local_name']);

    }

	/**
	 * 修改地区信息的入口
	 * @params null
	 * @return null
	 */
    public function saveDlArea()
	{
		$this->begin('index.php?app=eccommon&ctl=regions&act=index');
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        if(!$obj_regions_op->updateDlArea($_POST,$msg)){
			$this->end(false, '修改失败，'.$msg);
        }
		else
		{
			$this->end(true, '修改成功，地区名称：'.$_POST['local_name']);
		}
    }

    /**
     * 编辑显示页面
     * @params string 地区的regions id
     * @return null
     */
    public function detailDlArea($aRegionId)
	{
        $this->path[] = array('text'=>'配送地区编辑');
        $oObj = app::get('eccommon')->model('regions');
        $this->pagedata['area'] = $oObj->getDlAreaById($aRegionId);
        $this->display('regions/area_edit.html');
    }

	/**
	 * 删除对应regions id 的地区
	 * @params string region id
	 * @return null
	 */
    public function toRemoveArea($regionId)
	{
        $this->begin('index.php?app=eccommon&ctl=regions&act=index');
		$obj_regions_op = kernel::single('eccommon_regions_operation');
		if ($obj_regions_op->toRemoveArea($regionId))
			$this->end(true,'删除地区成功！');
		else
			$this->end(false,'删除地区失败！');
    }

    /**
     * 更新地区排序数据
     * @params null
     * @return null
     */
    public function updateOrderNum()
	{
        $this->begin('index.php?app=eccommon&ctl=regions&act=index');

        $is_update = true;
        $dArea = app::get('eccommon')->model('regions');
        $arrPOdr = $_POST['p_order'];

        $arrRegions = array();
        if ($arrPOdr)
        {
            foreach ($arrPOdr as $key=>$strPOdr)
            {
                $arrdArea = $dArea->dump($key, 'region_id,ordernum');
                $arrdArea['ordernum'] = $strPOdr ? $strPOdr : '50';
                $arrRegions[] = $arrdArea;
            }
        }

        if ($arrRegions)
        {
            foreach ($arrRegions as $arrRegionsinfo)
            {
                $is_update = $dArea->save($arrRegionsinfo);
            }
        }

        $this->end($is_update,'排序成功！');
    }

}
