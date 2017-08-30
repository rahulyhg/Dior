<?php
/**
 * 初始化调拔单打印模板数据
 * 
 * @author sunjing
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
    'appropriation'=>array(
        'name' => '调拔单打印模板',
        'defaultPath' => '/admin/appropriation/printtemp',
        'app'=>'taoguanallocate',
        'printpage'=>'admin/print.html'
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
 
	fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
	fclose($fp);
}
