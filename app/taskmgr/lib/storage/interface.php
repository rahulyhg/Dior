<?php
/**
 * 文件存储的接口定义
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */
interface taskmgr_storage_interface {

    public function get($url, $local_file);

    public function save($source_file, $task_id, &$url);

    public function delete($url);
}