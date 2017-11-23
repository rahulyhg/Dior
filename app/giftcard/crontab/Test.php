<?php
$root_dir = realpath(dirname(__FILE__).'/../../../');//echo $root_dir;exit();
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");
require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}
require_once(APP_DIR.'/base/defined.php');
cachemgr::init(false);

$post['trade_no']='562121347927';
$post['card_id']='pmZYgswHde5ua0XP0I3I1CvBHsBM';
kernel::single("giftcard_wechat_request_order")->getExistOrderId($post);//Ω≈±æ

exit();
$order=json_decode('{"ToUserName":"gh_c85110c224cf","FromUserName":"omZYgs6BzKhv_HVmRNxo8wHMZNLo","CreateTime":"1502885269","MsgType":"event","Event":"giftcard_user_accept","PageId":"Wc6cT2KtvHc9E87Ttb9kN8xc3Tuxun4PdvzVZmdrPR0=","OrderId":"AQAACDR49a6PTDcKBpmWILONnwUA","IsChatRoom":"false","UnionId":"oXgeOwp7gpltUgMvf6LNQ5HMcPPw"}',true);

kernel::single("giftcard_jing_response_order")->update($order);//Ω≈±æ{"card_id":"pmZYgswHde5ua0XP0I3I1CvBHsBM","token":"81fc730ffe673910005dda035c4e672c","api_method":"exchangeOrder","account":{"name":"\u5434\u838e","mobile":"13823210961","open_id":"oDvUM0YISfBSVks6m62Uj23lAPjc"},"products":[{"bn":"F042783999","num":"1","name":"Dior\u8fea\u5965\u70c8\u8273\u84dd\u91d1\u5507\u818f","type":"sales","price":"300.00","sale_price":"300.00"}],"form_id":"eef932060eb7ced30b6bd567c6d7cf4a","address_id":"\u5e7f\u4e1c\u7701-\u6df1\u5733\u5e02-\u5357\u5c71\u533a","consignee":{"addr":"\u79d1\u6280\u56ed\u817e\u8baf\u5927\u53a6","name":"\u5434\u838e","zip":"518000","mobile":"13823210961"},"cost_shipping":"0.00","pmt_order":"0.00","pay":300,"trade_no":"970695300824","createtime":1502886002}

//{"errcode":0,"errmsg":"ok","order":{"order_id":"AQAAwxCE764VlT4KBpmWILNumAIA","page_id":"Wc6cT2KtvHc9E87Ttb9kN8xc3Tuxun4PdvzVZmdrPR0=","trans_id":"4001102001201708146161594053","create_time":1502685438,"pay_finish_time":1502685464,"total_price":30000,"open_id":"omZYgs4Ed5pTpAqcp9jG0bP7LAmA","card_list":[{"card_id":"pmZYgswHde5ua0XP0I3I1CvBHsBM","price":30000,"code":"879428470408","background_pic_url":"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/Qk8iaddR5VJp7c2roEp7FQpHAu00cibUJKQoOabqzrVpP3TlDznzZJib7KVyqZaZO5vlYOWXSsVQPB9HqG4UITeicw\/0","outer_img_id":""}],"outer_str":"wallet02","headimgurl":"http:\/\/wx.qlogo.cn\/mmhead\/p8pI0okPyWD3vnsibz5lH2d1bqDUjCwBPmAGMRcjQjx4","nickname":"’≈∫Ω","is_chatroom":false}}



//{"card_id":"pmZYgs-NhpLgbyDdJuS8WTftG-hc","token":"81fc730ffe673910005dda035c4e672c","api_method":"exchangeOrder","account":{"name":"\u674e's","mobile":"13437373013","open_id":"oDvUM0V06xU_-UHgxh3gkUzF0SoE"},"products":[{"bn":"F008222080","num":"1","name":"Dior\u8fea\u5965\u5c0f\u59d0","type":"sales","price":"950.00","sale_price":"950.00"}],"form_id":"1502886089402","address_id":"\u5e7f\u4e1c\u7701-\u4e1c\u839e\u5e02-\u4e1c\u839e\u5e02","consignee":{"addr":"\u5927\u6717\u9547\u5df7\u5934\u5eb7\u4e30\u8def\u6a2a\u885736\u53f7","name":"\u674e's","zip":"523000","mobile":"13437373013"},"cost_shipping":"0.00","pmt_order":"0.00","pay":950,"trade_no":"166855837924","createtime":1502886089}