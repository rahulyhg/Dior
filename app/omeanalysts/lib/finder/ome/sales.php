<?php
class omeanalysts_finder_ome_sales{
	var $column_gross_sales = '毛利';

	public function column_gross_sales($rows) {

		return "1.00";
	}

	var $column_gross_sales_rate = '毛利率';

	public function column_gross_sales_rate($rows) {
        #echo "<pre>";
        #print_r($rows);exit;
	}

}