<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */ 
class taoexlib_finder_ietask {

	var $addon_cols = 'file_name,export_ver';
    
    var $column_edit3 = '下载链接';
	var $column_edit3_width = 100;
	var $column_edit3_order = 15;
    function column_edit3($row){
        $link = '---';
		if($row['status'] == 'finished') {
			if($row[$this->col_prefix.'export_ver'] == 1){
                $url = kernel::single('taoexlib_storager')->parse($row[$this->col_prefix.'file_name']);
                if($url){
                    $link = '<a target="_blank" href="'.$url['url'].'">点击下载</a>';
                }
			}elseif($row[$this->col_prefix.'export_ver'] == 2){
				$link = '<a target="_blank" href="index.php?app=taoexlib&ctl=ietask&act=newdownload&p[0]='.$row['task_id'].'">点击下载</a>';
			}
        }
        return $link;
	}
}