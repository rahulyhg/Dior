<?php
// 定义根目录
define('__ROOT_DIR', dirname(__FILE__) . '/../');

/**
 * 类库自动加载
 *
 * @param string $class_name            
 * @return boolean
 */
function __autoload($class_name) {
 
    if (stripos($class_name, 'PhpAmqpLib') !== false) {

        return __autoloadAmqpLib($class_name);
    }

    $pos = strpos($class_name, '_');
    
    if ($pos) {
        $owner = substr($class_name, 0, $pos);
        $class_name = substr($class_name, $pos + 1);
        
        $path = __ROOT_DIR . 'lib/' . str_replace('_', '/', $class_name) . '.php';
       
        if (file_exists($path)) {
            
            return require_once $path;
        } else {
            
            return false;
        }
    } else {
        return false;
    }
}


function __autoloadAmqpLib($class_name) {

    $filename = __ROOT_DIR . 'lib/third/' . str_replace("\\", '/', $class_name) . '.php';
    if (file_exists($filename)) {
            
        return require_once $filename;
    } else {
        
        return false;
    }
}

//注册类文件自动引用
if(function_exists('spl_autoload_register')){
    spl_autoload_register('__autoload');
}else{
    die('Can not register autoload function !!!');
}

//加载配置文件
require_once(__ROOT_DIR . 'config/config.php');