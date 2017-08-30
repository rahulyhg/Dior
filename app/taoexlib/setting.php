<?php
$setting = array(
	'taoexlib.message.switch'=>array('type'=>SET_T_BOOL,'default'=>'off','desc'=>app::get('taoexlib')->_('发货同时启用短信提醒')),
	'taoexlib.message.warningnumber'=>array('type'=>SET_T_TXT,'default'=>'500','desc'=>app::get('taoexlib')->_('短信预警条数')),
	'taoexlib.message.sampletitle'=>array('type'=>SET_T_TXT,'default'=>'发货通知','desc'=>app::get('taoexlib')->_('短信模板设置标题')),
	'taoexlib.message.samplecontent'=>array('type'=>SET_T_STR,'default'=>'{收货人}，您好！您在{店铺名称}订购的商品已通过{物流公司}发出，单号：{物流单号}，请当面检查后再签收，谢谢！','desc'=>app::get('taoexlib')->_('短信模板发送内容')),
	'taoexlib.message.blacklist'=>array('type'=>SET_T_STR,'default'=>'13813800000##138138111111##13813822222##13813833333','desc'=>app::get('taoexlib')->_('免打扰列表')),
);
?>