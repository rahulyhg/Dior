<?php
/**
 * 把旧模板机制中的模板导入到新模板机制表中
 * 
 * @author chenping<chenping@shopex.cn>
 * @version 1.0
 * @param $argv[1] 域名
 * @param $argv[2] ip
 */
error_reporting(E_ALL ^ E_NOTICE);

$domain = $argv[1];
$host_id = $argv[2];

if (empty($domain) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');
cachemgr::init(false);

$db = kernel::database();

$otmpl = array(
    'delivery' =>array(
        'name' => '打印发货模板',
        'defaultPath' => '/admin/delivery/delivery_print',
        'app'=>'ome',
        'printpage'=>'admin/delivery/print.html'
     ),
    'stock' =>array(
        'name' => '打印备货模板',
        'defaultPath' => '/admin/delivery/stock_print',
        'app'=>'ome',
        'printpage'=>'admin/delivery/print.html'
     ),
    'purchase' =>array(
        'name' => '打印采购模板',
        'defaultPath' => '/admin/purchase/purchase_print',
        'app'=>'purchase',
        'printpage'=>'admin/prints.html'
     ),
    'pureo' =>array(
        'name' => '打印采购入库模板',
        'defaultPath' => '/admin/eo/eo_print',
        'app'=>'purchase',
        'printpage'=>'admin/prints.html'
     ),
    'purreturn' =>array(
        'name' => '打印采购退货模板',
        'defaultPath' => '/admin/returned/return_print',
        'app'=>'purchase',
        'printpage'=>'admin/prints.html'
     ),
    'merge' => array(
        'name'=> '打印联合模板',
        'defaultPath' => '/admin/delivery/merge_print',
        'app' => 'ome',
        'printpage' => 'admin/delivery/print.html'
    ),
);  
foreach ($otmpl as $key=>$value) {
    $is_exist = is_exist($key);
    if($is_exist) continue;

    $printTxt = getDefaultTmpl($value['app'],$value['defaultPath']);
    $data = array(
        'title' => '默认'.$value['name'],
        'type' => $key,
        'content' => addslashes($printTxt),
        'is_default' => 'true', 
        'last_modified' => time(),
        'open' => 'true',
    );
    save($data);
}

$sjsql = 'insert into `sdb_ome_print_otmpl` (`title`, `type`, `content`, `is_default`, `aloneBtn`, `btnName`, `deliIdent`, `disabled`, `last_modified`, `path`, `open`) values("发货单模板带优惠价","delivery","&lt;{capture name=&quot;header&quot;}&gt;\n&lt;style media=&quot;print&quot;&gt;\ndiv{font-size:14pt; }\n&lt;/style&gt;\n&lt;style media=&quot;screen&quot;&gt;\ndiv{font-size:12px ;}\n&lt;/style&gt;\n&lt;style&gt;\n.order-box{ height:auto; padding:10px 10px 0 10px; margin:5px 20px 0 20px; }\n.order-box li{ padding:3px 0}\n.order-tr{ font-weight:bold; border-bottom:1px solid #ddd}\n.table-border{ margin:10px 0; border-top:2px solid #333;border-bottom:2px solid #333}\n.order-box td{ padding:3px 5px; vertical-align:top}\n.order-font{ font-weight:bold; padding:0 5px; clear:both; height:25px; line-height:25px; margin:5px 0 25px 0}\n&lt;/style&gt;\n&lt;{/capture}&gt;\n\n&lt;{ if $errIds }&gt;\n&lt;div class=&quot;errormsg notice&quot; id=&quot;errormsg&quot;&gt;\n    &lt;div id=&quot;msg&quot; class=&quot;msg&quot;&gt;注意：本次打印数据中的一些单据有问题，这些数据将被忽略(详细内容见下面列表)！！！&lt;/div&gt;\n    &lt;br&gt;\n    &lt;{ foreach from=$errIds item=id }&gt;\n        &lt;{$errBns[$id]}&gt;：&lt;{$errInfo[$id]}&gt;&lt;br&gt;\n    &lt;{ /foreach }&gt;\n&lt;/div&gt;\n&lt;{ /if }&gt;\n\n\n&lt;{if $err==\'false\'}&gt;\n&lt;{foreach from=$items item=item}&gt;\n&lt;div style=&quot;page-break-after: always; margin:0&quot;&gt;\n&lt;div class=&quot;order-box&quot;&gt;\n&lt;table width=&quot;100%&quot; cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; border=&quot;0&quot; &gt;\n&lt;tr&gt;&lt;td colspan=&quot;2&quot; class=&quot;order-tr&quot;&gt;发货底单&lt;{if $item.is_code==\'true\'}&gt;(订单支付方式：货到付款)&lt;{/if}&gt;\n&lt;{if $item.shop_logo_url}&gt;\n&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&lt;img src=&quot;&lt;{$item.shop_logo_url}&gt;&quot; width=&quot;257&quot; height=&quot;50&quot; alt=&quot;京东商城&quot;&gt;\n&lt;{elseif $item.shop_name}&gt;\n&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;(来源店铺：&lt;{$item.shop_name}&gt;)\n&lt;{/if}&gt;\n\n&lt;/td&gt;&lt;/tr&gt;\n&lt;tr&gt;&lt;td width=&quot;65%&quot; rowspan=&quot;4&quot; valign=&quot;top&quot; style=&quot;padding:5px 0&quot;&gt;&lt;{$item.delivery_bn|barcode}&gt;&lt;/td&gt;\n  &lt;td valign=&quot;middle&quot; style=&quot;padding:5px 0&quot;&gt;发货单号：&lt;span style=&quot;font-weight:bold&quot;&gt;&lt;{$item.delivery_bn}&gt;&lt;/span&gt;\n&lt;/tr&gt;\n&lt;tr&gt;\n	&lt;td valign=&quot;middle&quot; style=&quot;padding:5px 0&quot;&gt;打印批次号：&lt;{$idents[$item[\'delivery_id\']]}&gt;&lt;/td&gt;\n&lt;/tr&gt;\n&lt;tr&gt;&lt;td valign=&quot;middle&quot; style=&quot;padding:5px 0&quot;&gt;订单号：&lt;{$item.order_bn}&gt;&lt;/td&gt;&lt;/tr&gt;\n&lt;tr&gt;\n  &lt;td valign=&quot;middle&quot; style=&quot;padding:5px 0&quot;&gt;会员名：&lt;{$item.member_name}&gt;\n&lt;/tr&gt;\n\n&lt;tr&gt;&lt;td colspan=&quot;2&quot; &gt;打印日期： &lt;{$time}&gt;&lt;/td&gt;&lt;/tr&gt;\n&lt;tr&gt;&lt;td colspan=&quot;2&quot; &gt;操作员： &lt;{$item.op_name}&gt;&lt;/td&gt;&lt;/tr&gt;\n\n\n&lt;/table&gt;\n&lt;table border=&quot;0&quot; align=&quot;center&quot; width=&quot;100%&quot; cellpadding=&quot;0&quot;  cellspacing=&quot;0&quot;  class=&quot;table-border&quot;&gt;\n  &lt;tr &gt;\n    &lt;td&gt;&lt;b&gt;商品名称&lt;/b&gt;&lt;/td&gt;\n    &lt;td&gt;&lt;b&gt;商品规格&lt;/b&gt;&lt;/td&gt;\n    &lt;td&gt;&lt;b&gt;货号&lt;/b&gt;&lt;/td&gt;\n    &lt;td&gt;&lt;b&gt;货位&lt;/b&gt;&lt;/td&gt;\n    &lt;td&gt;&lt;b&gt;数量&lt;/b&gt;&lt;/td&gt;\n    &lt;td class=&quot;price&quot;&gt;&lt;b&gt;单价&lt;/b&gt;&lt;/td&gt;\n    &lt;td&gt;优惠价&lt;/td&gt;\n&lt;td&gt;实际价格&lt;/td&gt;\n  &lt;/tr&gt;\n&lt;{foreach from=$item.delivery_items item=i}&gt;\n&lt;tr&gt;\n    &lt;td&gt;&lt;{$i.name}&gt;&lt;/td&gt;\n    &lt;td &gt;&lt;{$i.addon|default:\'--\'}&gt;&lt;/td&gt;\n    &lt;td &gt;&lt;{$i.bn}&gt;&lt;/td&gt;\n    &lt;td &gt;&lt;{$i.store_position}&gt;&lt;/td&gt;\n    &lt;td&gt;&lt;{$i.number}&gt;&lt;/td&gt;\n    &lt;td class=&quot;price&quot;&gt;&lt;{$i.price}&gt;&lt;/td&gt;\n    &lt;td class=&quot;pmt_price&quot;&gt;&amp;nbsp;&lt;{$i.pmt_price}&gt;&lt;/td&gt;\n &lt;td class=&quot;sale_price&quot;&gt;&amp;nbsp;&lt;{$i.sale_price}&gt;&lt;/td&gt;\n&lt;/tr&gt;\n&lt;{/foreach}&gt;\n&lt;{if !empty($total)}&gt;\n  &lt;tr&gt;\n    &lt;td colspan=&quot;2&quot; style=&quot;text-align:right; padding-right:10px;&quot;&gt;共计&lt;/td&gt;\n    &lt;td style=&quot;font-weight:bold;  padding-right:10px;&quot;&gt;&lt;{$total}&gt;&lt;/td&gt;\n  &lt;/tr&gt;\n&lt;{/if}&gt;\n&lt;/table&gt;\n&lt;table width=&quot;100%&quot; border=&quot;0&quot; cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; style=&quot;border-bottom:1px solid #666&quot;&gt;\n  &lt;tr&gt;&lt;td align=&quot;right&quot;&gt;发货单数量总计：&lt;{$item.delivery_total_nums}&gt;\n   &lt;span class=&quot;price&quot;&gt;&lt;{if $item.order_total_amount}&gt;\n     &amp;nbsp;&amp;nbsp;商品总金额:&lt;{$item.order_cost_item}&gt;;商品总优惠:&lt;{$item.pmt_order_total}&gt;&amp;nbsp;实付金额:&lt;{$item.order_total_amount}&gt;\n    &lt;{/if}&gt;&lt;/span&gt;\n  &lt;/td&gt;&lt;/tr&gt;\n&lt;/table&gt;\n&lt;style&gt;\n    #aaa td{border:none;}\n&lt;/style&gt;\n\n\n&lt;div class=&quot;order-tr&quot; style=&quot;padding:0 5px; height:28px; line-height:28px;&quot;&gt;收货人信息&lt;/div&gt;\n&lt;table width=&quot;100%&quot; border=&quot;0&quot; cellspacing=&quot;0&quot; cellpadding=&quot;0&quot;&gt;\n          &lt;tr&gt;\n          &lt;td width=&quot;65%&quot; rowspan=&quot;5&quot;&gt;订单备注：\n          &lt;{foreach name=&quot;m1&quot; from=$item._mark_text key=key item=item1}&gt;\n              &lt;br&gt;&lt;{$key}&gt;:\n              &lt;{foreach from=$item1 item=it}&gt;\n                  &lt;br&gt;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&lt;b&gt;&lt;{$it.op_content}&gt;&lt;/b&gt; &lt;{$it.op_time}&gt; by &lt;{$it.op_name}&gt;\n              &lt;{/foreach}&gt;\n          &lt;{/foreach}&gt;\n          &lt;br /&gt;&lt;br /&gt;订单附言：\n          &lt;{foreach name=&quot;m2&quot; from=$item._mark key=key item=item2}&gt;\n              &lt;br&gt;&lt;{$key}&gt;:\n              &lt;{foreach from=$item2 item=it}&gt;\n                  &lt;br&gt;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&lt;b&gt;&lt;{$it.op_content}&gt;&lt;/b&gt; &lt;{$it.op_time}&gt; by &lt;{$it.op_name}&gt;\n              &lt;{/foreach}&gt;\n          &lt;{/foreach}&gt;\n          &lt;/td&gt;\n          &lt;td &gt;收货人：&lt;{$item.consignee.name}&gt;&lt;/td&gt;\n        &lt;/tr&gt;\n        &lt;tr&gt;\n          &lt;td &gt;电话：&lt;{$item.consignee.telephone}&gt;&lt;/td&gt;\n        &lt;/tr&gt;\n        &lt;tr&gt;\n          &lt;td &gt;手机：&lt;{$item.consignee.mobile}&gt;&lt;/td&gt;\n        &lt;/tr&gt;\n        &lt;tr&gt;\n          &lt;td&gt;邮编：&lt;{$item.consignee.zip}&gt;&lt;/td&gt;\n        &lt;/tr&gt;\n        &lt;tr&gt;\n          &lt;td&gt;地址：&lt;{$item.consignee.area|region}&gt; &lt;{$item.consignee.addr}&gt;&lt;/td&gt;\n        &lt;/tr&gt;\n  &lt;tr&gt;\n    &lt;td colspan=&quot;2&quot;&gt;&lt;div class=&quot;order-font&quot;&gt;签字：&lt;/div&gt;&lt;/td&gt;\n    &lt;/tr&gt;\n  &lt;tr&gt;\n      &lt;td align=&quot;left&quot; class=&quot;order-font&quot;&gt;&lt;/td&gt;\n    &lt;td&gt;&lt;div align=&quot;right&quot; class=&quot;order-font&quot; style=&quot;border-bottom:1px dashed #666; height:25px; line-height:25px&quot;&gt;Powered by ShopEx.cn&lt;/div&gt;&lt;/td&gt;\n    &lt;/tr&gt;\n&lt;/table&gt;\n&lt;div style=&quot;clear:both&quot;&gt;&lt;/div&gt;\n&lt;/div&gt;\n&lt;/div&gt;\n&lt;{/foreach}&gt;\n&lt;{/if}&gt;\n \n\n     &lt;script&gt;\r\nvar err = &lt;{$err}&gt;;\r\nif (err==true){\r\n    new Dialog(new Element(&quot;div.tableform&quot;,{html:\'&lt;div class=&quot;division&quot;&gt;部分发货单已被合并或者拆分&lt;/div&gt;&lt;div class=&quot;table-action&quot;&gt;&lt;{button label=&quot;关闭&quot; onclick=&quot;re_finder();&quot;}&gt;&lt;/div&gt;\'}),{\r\n        title:\'提示\',\r\n        width:230,\r\n        height:130,\r\n        modal:true,\r\n        resizeable:false});\r\n}\r\n\r\nfunction re_finder(){\r\n    opener.finderGroup[\'&lt;{$env.get.finder_id}&gt;\'].unselectAll();\r\n    opener.finderGroup[\'&lt;{$env.get.finder_id}&gt;\'].refresh.delay(400,opener.finderGroup[\'&lt;{$env.get.finder_id}&gt;\']);\r\n    window.close();\r\n}\r\n\r\nfunction changePrint()\r\n{\r\n    new Dialog(new Element(&quot;div.tableform&quot;,{html:\'&lt;div id=&quot;pause&quot; class=&quot;division&quot;&gt;正在提交数据&lt;{img app=&quot;desktop&quot; src=&quot;loading.gif&quot;}&gt;&lt;/div&gt;&lt;div class=&quot;table-action&quot;&gt;&lt;{button label=&quot;关闭&quot; onclick=&quot;re_finder();&quot;}&gt;&lt;/div&gt;\'}),{\r\n        title:\'提示\',\r\n        width:230,\r\n        height:130,\r\n        modal:true,\r\n        resizeable:false}\r\n    );\r\n    var printname= $(\'printname\').value;\r\n    new Request({url:\'index.php?app=ome&amp;ctl=admin_receipts_print&amp;act=setPrintStatus\',method:\'post\',data:\'type=delivery&amp;str=\'+printname+\'&amp;current_otmpl_name=&lt;{$current_otmpl_name}&gt;\',\r\n        onSuccess:function(json){\r\n          if (json == \'true\'){\r\n              $(\'pause\').getParent(\'.dialog\').retrieve(\'instance\').close();\r\n              window.print();\r\n          }else {\r\n              $(\'pause\').set(\'text\',json);\r\n          }\r\n        }\r\n    }).send();\r\n}\r\n\r\n&lt;/script&gt;","false","false","","","false","1335449410","admin/print/otmpl/8","true")';
$db->exec($sjsql);

ilog('保存打印模板');

function is_exist($type) 
{
    $sql = 'SELECT id FROM `sdb_ome_print_otmpl` WHERE type=\''.$type.'\' AND  is_default=\'true\'';
    $row = $GLOBALS['db']->selectrow($sql);
    return $row ? true : false;
}

function save($data) 
{
    $sql = 'INSERT INTO `sdb_ome_print_otmpl` (`'.implode('`,`',array_keys($data)).'`) VALUES(\''.implode('\',\'',array_values($data)).'\')';
    $GLOBALS['db']->exec($sql);
    $id = $GLOBALS['db']->lastinsertid();
    $path = 'admin/print/otmpl/'.$id;
    $sql = 'UPDATE `sdb_ome_print_otmpl` SET path=\''.$path.'\' WHERE id='.$id;
    $GLOBALS['db']->exec($sql);
}

// 获取打印类型
function getDefaultTmpl($app,$name) 
{
    $sql = 'SELECT content FROM sdb_ome_print_tmpl_diy WHERE app=\''.$app.'\' AND active=\'true\' AND tmpl_name=\''.$name.'\' ';
    $row = $GLOBALS['db']->selectrow($sql);
    if ($row) {
        //去除JS 换成HTML的JS
        $file = ROOT_DIR.'/app/'.$app.'/view/'.$name.'.html';

        $contents = filterBody($row['content'],$file);
    }else{
        $file = ROOT_DIR.'/app/'.$app.'/view/'.$name.'.html';
        $contents =  file_get_contents($file);
    }

    return $contents;
}

function filterBody($body,$file='') 
{
    $body = htmlspecialchars_decode($body);
    //过滤js
    $body = preg_replace('/<script[^>]*>([\s\S]*?)<\/script>/i',' ',$body);

    $contents =  file_get_contents($file);
    $re = preg_match_all('/<script[^>]*>([\s\S]*?)<\/script>/i',$contents,$matches);
    if ($re) {
        foreach ($matches[0] as $value) {
            $body .= $value;
        }
    }

    $body = htmlspecialchars($body);

    return $body;
}


/**
 * 日志
 */
function ilog($str) {
	
	global $domain;
	$filename = dirname(__FILE__) . '/../logs/tmpl_' . date('Y-m-d') . '.log';
	$fp = fopen($filename, 'a');
 echo date("m-d H:i") . "\t" . $domain . "\t" . mb_convert_encoding($str, "gb2312", "utf-8"). "\n";
	fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
	fclose($fp);
}
