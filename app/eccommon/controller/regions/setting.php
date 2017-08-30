<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class eccommon_ctl_regions_setting extends desktop_controller{

    var $workground = 'eccommon_center';

	public function __construct($app)
	{
		parent::__construct($app);
		header("cache-control: no-store, no-cache, must-revalidate");
	}

    function index(){
        $package = kernel::single('eccommon_regions_mainland');

        $this->pagedata['package'] = $package->setting;

		$obj_region = app::get('eccommon')->model('regions');

		$row = $obj_region->getList('MAX(region_grade) AS _max_grade');
		if ($row){
			$this->pagedata['package']['maxdepth'] = $row[0]['_max_grade'];
		}

        $this->pagedata['package']['name'] = $package->name;

        $this->pagedata['area_depth'] = app::get('eccommon')->getConf('system.area_depth');

        $model = app::get('eccommon')->model('regions');
        $this->pagedata['package']['installed'] = $model->is_installed();

        $ectools_regions_ectools_mdl_regions = &app::get('base')->getConf('service.eccommon_regions.eccommon_mdl_regions');

        $o = &app::get('base')->model('services');
        $this->pagedata['eccommon_regions_eccommon_mdl_regions'] = &app::get('base')->getConf('site.eccommon_regions.eccommon_mdl_regions');

        foreach( $o->getList('content_path',array('content_type'=>'service','app_id'=>'eccommon','disabled'=>'false','content_name'=>'eccommon_regions.eccommon_mdl_regions')) as $k => $v ){
            $listItem = array();
            $listItem['content_path'] = $v['content_path'];
            $oItem = kernel::single($v['content_path']);
            $listItem['name'] = $oItem->name;
            $this->pagedata['eccommon_regions_eccommon_mdl_regions_list'][] = $listItem;
        }
        $this->page('regions/index.html');
    }

    function save_depth(){
        $this->begin('index.php?app=eccommon&ctl=regions_setting&act=index');
        $rs = app::get('eccommon')->setConf('system.area_depth',$_POST['area_depth']);
        $this->end($rs);
    }

    function install(){
        set_time_limit(0);
        $this->begin('index.php?app=eccommon&ctl=regions_setting&act=index');
        $package = kernel::single('eccommon_regions_mainland');
        $rs = $package->install();
        $this->end($rs);
    }

    function setDefault(){
        set_time_limit(0);
        $this->begin('index.php?app=eccommon&ctl=regions_setting&act=index');
        $model = app::get('eccommon')->model('regions');
        $model->clearOldData();
        $package = kernel::single('eccommon_regions_mainland');
        $rs = $package->install();
        $this->end($rs);
    }

    function save_regions_package(){
        $this->begin('index.php?app=eccommon&ctl=regions_setting&act=index');
        $rs = app::get('base')->setConf('service.eccommon_regions.eccommon_mdl_regions' , $_POST['service']['eccommon_regions.eccommon_mdl_regions']);
        $this->end($rs);
    }

}
