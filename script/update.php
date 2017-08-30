<?php

set_time_limit(0);

require_once(dirname(__FILE__) . '/../config/saasapi.php');

define('SASS_APP_KEY', 'taoguan');
define('SAAS_SECRE_KEY', '49F4589687E79D815339B13A73E5FBB4');

function fetchHostListByCode($code) {

    $api = new SaasOpenClient();
    $api->appkey = SASS_APP_KEY;
    $api->secretKey = SAAS_SECRE_KEY;
    $api->format = 'json';

    $params = array('service_code' => $code);
    $result = $api->execute('host.getlist', $params);

    unset($api);
    if ($result->success == 'true') {
        if ($result->data == 'QUEUE_END') {
            return null;
        } else {
            return $result->data;
        }
    } else {
        return null;
    }
}

function getInfoByHost($host) {

    $api = new SaasOpenClient();
    $api->appkey = SASS_APP_KEY;
    $api->secretKey = SAAS_SECRE_KEY;
    $api->format = 'json';

    $params = array('server_name' => $host);
    $result = $api->execute('host.getinfo_byservername', $params);

    unset($api);
    if ($result->success == 'true') {
        if ($result->data == 'QUEUE_END') {
            return null;
        } else {
            return $result->data;
        }
    } else {
        return null;
    }
}

function callUpdateProcess($host) {
 
    $info = getInfoByHost($host);
    if ($info->status != 'HOST_STATUS_DELETED') {
        
        $serverName = $host;
		$orderId = $info->order_id;
		$hostId = $info->host_id;
	
		$cmd = sprintf("/usr/local/php/bin/php /data/httpd/stable.tg.taoex.com/script/updateDomain.php %s %s %s", $serverName, $orderId, $hostId);
		echo 'start:'.$host."\n";
		echo 'cmd:'.$cmd."\n";
		exec($cmd ,$b, $a);
		echo 'end:'.$host."\n";
    }
}

$offset = $argv[1];
$finfo = fetchHostListByCode('taoex-tg');
$info = array_merge((array) $finfo->host_name_list);

$old_info = array (
  0 => 'shiyao.tg.taoex.com',
  1 => 'wangxi.tg.taoex.com',
  2 => 'shiyao744.tg.taoex.com',
  3 => 'shiyao20744.tg.taoex.com',
  4 => 'nicksh.tg.taoex.com',
  5 => 'nicksh1.tg.taoex.com',
  6 => 'test.tg.taoex.com',
  7 => 'dev.tg.taoex.com',
  8 => 'liupeng.tg.taoex.com',
  9 => 'zyqs.tg.taoex.com',
  10 => 'test100.tg.taoex.com',
  11 => 'xiyunjiao.tg.taoex.com',
  12 => 'tsjj.tg.taoex.com',
  13 => 'shangteng.tg.taoex.com',
  14 => 'xiu21.tg.taoex.com',
  15 => 'miao.tg.taoex.com',
  16 => 'shiyao4.tg.taoex.com',
);

$shengji_host = array();
foreach ((array) $info as $k=>$host) {
	if(!in_array($host, $old_info)){
		$shengji_host[] = $host;
	}
}

$i = 1;
$slice_host = array_slice($shengji_host,$offset,50);
var_export($slice_host);
foreach ((array) $slice_host as $k=>$host) {
	
    callUpdateProcess($host);
    echo $i."\n";
    $i++;
}


exit;
$i = 0;
$info_1 = array('shiyao4.tg.taoex.com','xizhi.tg.taoex.com','usashark.tg.taoex.com','189.tg.taoex.com','cf.tg.taoex.com','demo.tg.taoex.com','dohia.tg.taoex.com','baishu.tg.taoex.com','haojiafang.tg.taoex.com','onegoal.tg.taoex.com','lxsm.tg.taoex.com','zhangmin.tg.taoex.com','sale.tg.taoex.com');

$info_2 = array ( 0 => '51kubei.tg.taoex.com', 1 => 'ansels.tg.taoex.com', 2 => 'aoyunhui0312.tg.taoex.com', 3 => 'assue.tg.taoex.com', 4 => 'beinimy.tg.taoex.com', 5 => 'benbennvren.tg.taoex.com', 6 => 'chos.tg.taoex.com', 7 => 'cs35.tg.taoex.com', 8 => 'gsyq.tg.taoex.com', 9 => 'gyng.tg.taoex.com', 10 => 'hot6.tg.taoex.com', 11 => 'jieyang.tg.taoex.com', 12 => 'juejia.tg.taoex.com', 13 => 'moon.tg.taoex.com', 14 => 'orro.tg.taoex.com', 15 => 'popcellar.tg.taoex.com', 16 => 'qqf.tg.taoex.com', 17 => 'seasoul.tg.taoex.com', 18 => 'sieg.tg.taoex.com', 19 => 'ssgdjl.tg.taoex.com', 20 => 'swise.tg.taoex.com', 21 => 'taku.tg.taoex.com', 22 => 'tiafeiluo.tg.taoex.com', 23 => 'tw.tg.taoex.com', 24 => 'veryhomedecor.tg.taoex.com', 25 => 'wl860905.tg.taoex.com', 26 => 'xizi.tg.taoex.com', 27 => 'xzhuang.tg.taoex.com', 28 => 'yj.tg.taoex.com', 29 => 'yuju.tg.taoex.com', 30 => 'zijie.tg.taoex.com', );

$info_3 = array (
  0 => 'savaloe.tg.taoex.com',
  1 => 'nsb.tg.taoex.com',
  2 => 'lvyoumall.tg.taoex.com',
  3 => 'shyucen.tg.taoex.com',
  4 => 'vicky.tg.taoex.com',
  5 => 'koushuiwa.tg.taoex.com',
  6 => 'cs27.tg.taoex.com',
  7 => 'xianglifang.tg.taoex.com',
  8 => 'cd10.tg.taoex.com',
  9 => 'yinao.tg.taoex.com',
  10 => 'yuanpin.tg.taoex.com',
  11 => 'gulinai.tg.taoex.com',
  12 => 'chuangdongsm.tg.taoex.com',
  13 => 't-life.tg.taoex.com',
  14 => 'duomeihui.tg.taoex.com',
  15 => 'youai.tg.taoex.com',
  16 => 'midealy.tg.taoex.com',
  17 => 'cs19.tg.taoex.com',
  18 => 'bottleus.tg.taoex.com',
  19 => 'zmkm.tg.taoex.com',
  20 => 'bst.tg.taoex.com',
  21 => 'girlsshop.tg.taoex.com',
  22 => 'jmky.tg.taoex.com',
  23 => 'dk82.tg.taoex.com',
  24 => '1717.tg.taoex.com',
  25 => 'lijiefashion.tg.taoex.com',
  26 => 'zhuge1980.tg.taoex.com',
  27 => 'cashncarry.tg.taoex.com',
  28 => 'cs20.tg.taoex.com',
  29 => 'kiss.tg.taoex.com',
  30 => 'vanies.tg.taoex.com',
  31 => 'coach.tg.taoex.com',
  32 => 'justbo.tg.taoex.com',
  33 => 'nonceice.tg.taoex.com',
  34 => 'joosnow.tg.taoex.com',
  35 => 'hengshoutang.tg.taoex.com',
  36 => 'zhoulin1990.tg.taoex.com',
  37 => '18711185.tg.taoex.com',
  38 => 'yu312939255.tg.taoex.com',
  39 => 'gangtian.tg.taoex.com',
  40 => 'yishengbai.tg.taoex.com',
  41 => 'zhicheng.tg.taoex.com',
  42 => 'taks.tg.taoex.com',
  43 => 'anpingo.tg.taoex.com',
  44 => 'ulando.tg.taoex.com',
  45 => 'deai.tg.taoex.com',
  46 => 'lamilee.tg.taoex.com',
  47 => 'aiduola1.tg.taoex.com',
  48 => 'yyqs.tg.taoex.com',
  49 => 'bikee.tg.taoex.com',
  50 => 'von.tg.taoex.com',
  51 => 'lusen.tg.taoex.com',
  52 => 'thlworld.tg.taoex.com',
);

$info_4 = array('sanrenxing-1.xyt-erp.taoex.com','yurun.xyt-erp.taoex.com','test.xyt-erp.taoex.com');

$info_5 = array (
  0 => 'thingo.tg.taoex.com',
  1 => 'mulsan.tg.taoex.com',
  2 => 'jzy.tg.taoex.com',
  3 => 'cryj.tg.taoex.com',
  4 => '9loft.tg.taoex.com',
  5 => 'mycatparadise.tg.taoex.com',
  6 => 'rebelette.tg.taoex.com',
  7 => 'biaofu.tg.taoex.com',
  8 => 'teyi.tg.taoex.com',
  9 => 'br.tg.taoex.com',
  10 => 'busen.tg.taoex.com',
  11 => 'liswlai.tg.taoex.com',
  12 => 'ytmy.tg.taoex.com',
  13 => 'sh.tg.taoex.com',
  14 => 'shoppingtime.tg.taoex.com',
  15 => 'lalusea.tg.taoex.com',
  16 => 'jingmei.tg.taoex.com',
  17 => 'cd1.tg.taoex.com',
  18 => 'cd12.tg.taoex.com',
  19 => 'qwmf.tg.taoex.com',
  20 => 'daixiao5.tg.taoex.com',
  21 => 'manager.tg.taoex.com',
  22 => 'royln2011.tg.taoex.com',
  23 => 'cjbbt.tg.taoex.com',
  24 => 'ssshzx.tg.taoex.com',
  25 => 'shinco.tg.taoex.com',
  26 => 'naishidi.tg.taoex.com',
  27 => 'hrjf.tg.taoex.com',
  28 => 'xiangmishijiaqjd.tg.taoex.com',
  29 => 'cd2.tg.taoex.com',
  30 => 'nanxiu.tg.taoex.com',
  31 => 'hidoo.tg.taoex.com',
  32 => 'bluejazz.tg.taoex.com',
  33 => 'lusheng.tg.taoex.com',
  34 => '707070.tg.taoex.com',
  35 => 'yizifushi.tg.taoex.com',
  36 => 'cd17.tg.taoex.com',
  37 => 'leevy.tg.taoex.com',
  38 => 'cs40.tg.taoex.com',
  39 => 'dorlink.tg.taoex.com',
  40 => 'victor.tg.taoex.com',
  41 => 'youyou.tg.taoex.com',
  42 => 'pafullan.tg.taoex.com',
  43 => 'qiyoushangwu.tg.taoex.com',
  44 => 'dahaibian.tg.taoex.com',
  45 => 'thcha.tg.taoex.com',
  46 => 'wanmt.tg.taoex.com',
  47 => 'doaerzhang88.tg.taoex.com',
  48 => 'tacera.tg.taoex.com',
  49 => 'taofu.tg.taoex.com',
  50 => 'cd42.tg.taoex.com',
  51 => 'wuxianmei.tg.taoex.com',
  52 => 'scentline.tg.taoex.com',
  53 => 'micawa.tg.taoex.com',
);

$info_6 = array (
  0 => 'starnyc.tg.taoex.com',
  1 => 'yingying.tg.taoex.com',
  2 => 'cheersofa.tg.taoex.com',
  3 => 'urbanfox.tg.taoex.com',
  4 => 'dwecom2011.tg.taoex.com',
  5 => 'liuming03.tg.taoex.com',
  6 => 'cocacola.tg.taoex.com',
  7 => 'beautycreator.tg.taoex.com',
  8 => 'nbcfs.tg.taoex.com',
  9 => 'kinlong.tg.taoex.com',
  10 => 'mustore.tg.taoex.com',
  11 => 'contec.tg.taoex.com',
  12 => '51swim.tg.taoex.com',
  13 => 'szf.tg.taoex.com',
  14 => 'qu.tg.taoex.com',
  15 => 'xyshop.tg.taoex.com',
  16 => 'kailai.tg.taoex.com',
  17 => 'nadimoda.tg.taoex.com',
  18 => 'tianxiang.tg.taoex.com',
  19 => 'smallmillet2010.tg.taoex.com',
  20 => 'mejibeyu.tg.taoex.com',
  21 => 'gogan.tg.taoex.com',
  22 => 'nanan.tg.taoex.com',
  23 => 'hzzsyb.tg.taoex.com',
  24 => 'xiaomishop.tg.taoex.com',
  25 => 'jkh.tg.taoex.com',
  26 => 'jushang.tg.taoex.com',
  27 => 'equipoise.tg.taoex.com',
  28 => '5188.tg.taoex.com',
  29 => 'xiyee.tg.taoex.com',
  30 => 'leadmusic.tg.taoex.com',
  31 => 'cd6.tg.taoex.com',
  32 => '365qin.tg.taoex.com',
  33 => 'ouyang.tg.taoex.com',
  34 => 'abo.tg.taoex.com',
  35 => 'kidsarsah.tg.taoex.com',
  36 => 'mellowine.tg.taoex.com',
  37 => 'zsrj.tg.taoex.com',
  38 => 'flowersme.tg.taoex.com',
  39 => 'duozi.tg.taoex.com',
  40 => 'leathercraft.tg.taoex.com',
  41 => 'umanto.tg.taoex.com',
  42 => 'ranth.tg.taoex.com',
  43 => 'eusania.tg.taoex.com',
  44 => 'polo.tg.taoex.com',
  45 => 'ttaok.tg.taoex.com',
  46 => 'bailian.tg.taoex.com',
  47 => 'yifufs.tg.taoex.com',
  48 => 'left.tg.taoex.com',
  49 => 'nangeangel0610.tg.taoex.com',
  50 => '5177.tg.taoex.com',
  51 => 'ibrave.tg.taoex.com',
  52 => 'okface.tg.taoex.com',
  53 => 'allrican.tg.taoex.com',
  54 => 'gcb.tg.taoex.com',
  55 => 'tf.tg.taoex.com',
  56 => 'minashopping.tg.taoex.com',
);

$info_7 = array (
  0 => 'jlgz.tg.taoex.com',
  1 => 'onijie.tg.taoex.com',
  2 => 'mc.tg.taoex.com',
  3 => 'nanikids.tg.taoex.com',
  4 => 'gisan.tg.taoex.com',
  5 => 'zgds.tg.taoex.com',
  6 => 'pinrui.tg.taoex.com',
  7 => 'fenix.tg.taoex.com',
  8 => 'soobest.tg.taoex.com',
  9 => 'uusheep.tg.taoex.com',
  10 => 'amingfood.tg.taoex.com',
  11 => 'mesu.tg.taoex.com',
  12 => 'missangle.tg.taoex.com',
  13 => 'trus.tg.taoex.com',
  14 => 'dongtaifs.tg.taoex.com',
  15 => 'yxsm.tg.taoex.com',
  16 => 'yaqi.tg.taoex.com',
  17 => 'athabasca.tg.taoex.com',
  18 => '114716.tg.taoex.com',
  19 => 'yinsang.tg.taoex.com',
  20 => 'jasun.tg.taoex.com',
  21 => 'baoshidi.tg.taoex.com',
  22 => 'qiaoyan518518.tg.taoex.com',
  23 => 'ouxian.tg.taoex.com',
  24 => 'zhizaibide.tg.taoex.com',
  25 => 'zridd.tg.taoex.com',
  26 => 'qianyun.tg.taoex.com',
  27 => 'im.tg.taoex.com',
  28 => 'fancy365.tg.taoex.com',
  29 => 'samgj.tg.taoex.com',
  30 => 'byyf.tg.taoex.com',
  31 => 'glz.tg.taoex.com',
  32 => 'cs8.tg.taoex.com',
  33 => 'orzan.tg.taoex.com',
  34 => 'crocodile.tg.taoex.com',
  35 => 'jyf.tg.taoex.com',
  36 => 'yi.tg.taoex.com',
  37 => '869auto.tg.taoex.com',
  38 => 'donggan.tg.taoex.com',
  39 => 'x2.tg.taoex.com',
  40 => 'tao3515.tg.taoex.com',
  41 => 'hfyg.tg.taoex.com',
  42 => 'ssm.tg.taoex.com',
  43 => 'guizhongniepan.tg.taoex.com',
  44 => 'itpfw.tg.taoex.com',
  45 => 'yq3c.tg.taoex.com',
  46 => 'waicoo.tg.taoex.com',
  47 => 'vegoo.tg.taoex.com',
  48 => 'jumeer.tg.taoex.com',
  49 => 'nootouch.tg.taoex.com',
  50 => 'tmimi.tg.taoex.com',
  51 => 'test2011.tg.taoex.com',
  52 => 'shiyao1.tg.taoex.com',
  53 => 'thelux.tg.taoex.com',
  54 => 'liujinguang.tg.taoex.com',
  55 => 'shiyao3.tg.taoex.com',
  56 => 'shiyao2.tg.taoex.com',
  57 => 'webrand.tg.taoex.com',
  58 => 't1.tg.taoex.com',
  59 => 'youweiping.tg.taoex.com',
  60 => 'taojin.tg.taoex.com',
);

//$info = array_merge($info_1,$info_2);
//$info = array_reverse($info);

foreach ((array) $info_7 as $k=>$host) {
	if(in_array($host, $info_1) || in_array($host, $info_2) || in_array($host, $info_3) || in_array($host, $info_4) || in_array($host, $info_5) || in_array($host, $info_6)){
		echo 'no:'.$host;
		exit;
	}	
}

$info = $info_7;

foreach ((array) $info as $k=>$host) {
	
    callUpdateProcess($host);
    $i++;
}
