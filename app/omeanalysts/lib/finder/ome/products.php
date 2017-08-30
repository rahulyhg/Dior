<?php
class omeanalysts_finder_ome_products{
	var $column_obj_type = '类型';

	public function column_obj_type($rows) {

        $obj_type = (isset($rows['obj_type']) && $rows['obj_type'] == 'pkg')?'捆绑商品':'普通商品';

		return "<span class='tag-label'>&nbsp;".$obj_type."&nbsp;</span>";
	}
}