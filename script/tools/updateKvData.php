<?php

/**
 * 更新KV数据，并重新恢复
 * 
 * @author hzjsq@msn.com
 * @version 1.0 
 */

$argv[1] = 'gs02.gs.taoshopex.com';

require_once(dirname(__FILE__) . '/../lib/init.php');

class kv {

    static function update($prefix, $key, $value) {

        if ($prefix && $key) {

            $db = kernel::database();

            //更新KV缓存
            $row = $db->selectrow(sprintf("SELECT * FROM sdb_base_kvstore WHERE prefix='%s' AND `key`='%s'", mysql_escape_string($prefix), mysql_escape_string($key)));
            if ($row) {

                $sql = sprintf("UPDATE sdb_base_kvstore SET value= '%s' WHERE prefix='%s' AND `key`='%s'", mysql_escape_string(serialize($value)), mysql_escape_string($prefix), mysql_escape_string($key));
                if ($db->query($sql)) {
                    
                    $row['value'] = $value;
                } else {
                    kernel::log($row['prefix'] . '=>' . $row['key'] . ' ... Recovery Failure');
                    return;
                }
            } else {
                $time = time();
                $sql = sprintf("INSERT INTO sdb_base_kvstore SET value= '%s', prefix='%s', `key`='%s', dateline='%s', ttl=0", mysql_escape_string(serialize($value)), mysql_escape_string($prefix), mysql_escape_string($key), $time);
                $id = $db->query($sql);
                if ($id) {

                    $row['value'] = $value;
                    $row['dateline'] = $time;
                    $row['id'] = $id;
                } else {

                    kernel::log($row['prefix'] . '=>' . $row['key'] . ' ... Recovery Failure');
                    return;
                }
            }

            if (base_kvstore::instance($row['prefix'])->recovery($row)) {
                kernel::log($row['prefix'] . '=>' . $row['key'] . ' ... Recovery Success');
            } else {
                kernel::log($row['prefix'] . '=>' . $row['key'] . ' ... Recovery Failure');
            }
        }
    }
}