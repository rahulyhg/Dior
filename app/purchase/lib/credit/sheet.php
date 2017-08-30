<?php
/*
 * 赊购单结算
 */

class purchase_credit_sheet {

	function save_credit_sheet($data, &$msg){
		$oCredit_sheet = &app::get('purchase')->model("credit_sheet");
        return $oCredit_sheet->save($data);
	}

	function gen_id(){
		$oCredit_sheet = &app::get('purchase')->model("credit_sheet");
        return $oCredit_sheet->gen_id();
	}
	
	function isCredit($po_id){
		$oCredit_sheet = &app::get('purchase')->model("credit_sheet");
		return $oCredit_sheet->isCredit($po_id);
	}

}
?>
