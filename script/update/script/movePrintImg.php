<?php
/**
 * 根据传入的域名做初始化工作
 * 
 * @author hzjsq@msn.com
 * @version 1.0
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain) || empty($order_id) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$printTmplObj = &app::get('ome')->model('print_tmpl');
$fileObj = &app::get("base")->model("files");

$oldMemcacheObj=new Memcache;
$host_mirrors = preg_split('/[,\s]+/',constant('STORAGE_MEMCACHED'));
if(is_array($host_mirrors) && isset($host_mirrors[0])){
    foreach($host_mirrors as $k =>$v){
        list($host,$port) = explode(":",$v);
        $oldMemcacheObj->addServer($host,$port);
    }
}

$newMemcacheObj=new Memcache;
$host_mirrors = preg_split('/[,\s]+/','223.4.233.95:30000');
if(is_array($host_mirrors) && isset($host_mirrors[0])){
    foreach($host_mirrors as $k =>$v){
        list($host,$port) = explode(":",$v);
        $newMemcacheObj->addServer($host,$port);
    }
}

$tmpl = $printTmplObj->getList('*');
foreach($tmpl as $val){
    $file = $fileObj->dump(array('file_id'=>$val['file_id']));

    if(!empty($file['file_path'])){
        $ret = array();
        list($ret['url'],$ret['id'],$ret['storager']) = explode('|',$file['file_path']);

        error_log(var_export($ret,true),3,ROOT_DIR."/script/update/logs/mvimg.log");

        $content = $oldMemcacheObj->get($ret['id']);
        $newMemcacheObj->set($ret['id'],$content);
    }
}

ilog("move print img $domain Ok.");

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/img_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
