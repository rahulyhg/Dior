<?php

/**
 * 打印批次处理
 * 
 *  以输入的要打印ID做 md5 hash 和 crc32 , 做为打印批次的鉴定。
 *
 * @author hzjsq@msn.com
 * @version 0.1 b
 */
class ome_queue {
    const appFix = 'ome';

    /**
     *  输入要打印的ID, 建产或获取打印任务
     *
     * @param mixed $ids 要打印的发货单id
     * @return
     */
    function fetchPrintQueue($ids) {


        if (!is_array($ids)) {

            $ids = array(intval($ids));
        }

        //sort($ids);

        $idsStr = serialize($ids);
        $md5Value = md5($idsStr);
        $crcValue = sprintf('%u', crc32($idsStr));
        $queue = $this->_existsQueue($md5Value, $crcValue);

        if (empty($queue)) {
            //如为空，则表示没有
            $hasIdents = $this->_getExistsIdentsInIds($ids);
            $createIds = array();
            if (!empty($hasIdents['items'])) {

                foreach ($ids as $id) {
                    if (!isset($hasIdents['items'][$id])) {
                        $createIds[] = $id;
                    }
                }
            } else {
                $createIds = $ids;
            }

            if (!empty($createIds)) {
                $newIdent = $this->_newPrintQueue($createIds);

                foreach ($newIdent['items'] as $id => $iden) {

                    $hasIdents['items'][$id] = $iden;
                }
                $hasIdents['idents'] = array_merge($newIdent['idents'], $hasIdents['idents']);
            }

            return $hasIdents;
        } else {
            //有
            $ident = $queue['ident'];

            $queueItems = array();
            $mdl_queue = app::get(self::appFix)->model('print_queue');
            $rows = $mdl_queue->findQueueItems($ident);

            if ($rows) {
                foreach ($rows as $row) {
                    $ident_dly = $ident . '_' . $row['ident_dly']; //加上批次号序列
                    $queueItems[$row['delivery_id']] = $ident_dly;
                }
            }
            return array('items' => $queueItems, 'idents' => array($ident));
        }
    }

    function _getExistsIdentsInIds($ids) {

        $mdl_queue = app::get(self::appFix)->model('print_queue');
        return $mdl_queue->getExistsIdentsInIds($ids);
    }

    function _newPrintQueue($ids) {

        if (!is_array($ids)) {

            $ids = array(intval($ids));
        }

        //sort($ids);

        $idsStr = serialize($ids);
        $md5Value = md5($idsStr);
        $crcValue = sprintf('%u', crc32($idsStr));
        $queue = $this->_existsQueue($md5Value, $crcValue);
        //if (!empty($queue)) {
        //    $ident = $queue['ident'];

        //} else {

        $avaiIds = app::get(self::appFix)->model('print_queue')->emptyPrintQueueFromIds($ids);
        $ident = $this->_createPrintQueue($md5Value, $crcValue, $ids);
        //}

        $queueItems = array();
        $mdl_queue = app::get(self::appFix)->model('print_queue');
        $rows = $mdl_queue->findQueueItems($ident);
        
        if ($rows) {
            foreach ($rows as $row) {
                $ident_dly = $ident . '_' . $row['ident_dly']; //加上批次号序列
                $queueItems[$row['delivery_id']] = $ident_dly;
            }
        }

        return array('items' => $queueItems, 'idents' => array($ident));
    }

    /**
     *  获取打印批次号
     *
     * @param mixed $ids 要打印的发货单id
     * @return
     */
    function getPrintQueue($ids) {

        if (!is_array($ids)) {

            $ids = array(intval($ids));
        }

        sort($ids);

        $idsStr = serialize($ids);
        $md5Value = md5($idsStr);
        $crcValue = sprintf('%u', crc32($idsStr));
        $queue = $this->_existsQueue($md5Value, $crcValue);

//        if (empty($queue)) {
//            $queue = $this->_existsItemInQueue($ids[0]); //已经isExistsQueueItems判断过，确保在同一个批次号中
//        }
        if (!empty($queue)) {

            $ident = $queue['ident'];
        } else {

            $ident = '';
        }

        //$reult = array();
        $queueItems = array();
        if ($ident) {
            $k = 0;
            $mdl_queue = app::get(self::appFix)->model('print_queue');
            $rows = $mdl_queue->findQueueItems($ident);
            if ($rows) {
                foreach ($rows as $row) {
                    $ident_dly = $ident . '_' . $row['ident_dly']; //加上批次号序列
                    $queueItems[$row['delivery_id']] = $ident_dly;
                }
            }
        }
        //foreach ($ids as $id) {
        //$result[$id] = sprintf( "%s-%03d", $ident, ++ $k);
        //}

        return array('itmes' => $queueItems, 'ident' => $ident);
    }

    /**
     *  如果没有批次号就生成批次号
     *
     * @param mixed $ids 要打印的发货单id
     * @return
     */
    /* function addPrintQueue($ids){
      if (! is_array($ids)) {

      $ids = array(intval($ids));
      }

      sort($ids);

      $idsStr = serialize($ids);
      $md5Value = md5($idsStr);
      $crcValue = sprintf('%u', crc32($idsStr));
      $queue = $this->_existsQueue($md5Value, $crcValue);
      if(empty($queue)){
      $queue = $this->_existsItemInQueue($ids[0]);//已经isExistsQueueItems判断过，确保在同一个批次号中
      }
      $ident = null;
      if (empty($queue)) {
      $ident  = $this->_createPrintQueue($md5Value, $crcValue, $ids);
      }

      return $ident;
      } */

    /**
     * 创建打印批次，并生成每张订单的打印批号
     *
     * @param String $md5
     * @param Integer $crc
     * @param Array $ids
     * @return Array
     */
    private function _createPrintQueue($md5, $crc, $ids) {

        $userId = kernel::single('desktop_user')->get_id();
        $userName = kernel::single('desktop_user')->get_name();

        $mdl_queue = app::get(self::appFix)->model('print_queue');

        $queue = array('md5' => $md5,
            'crc' => $crc,
            'opt_id' => $userId,
            'opt_name' => $userName,
            'ident' => '',
            'create_time' => time(),
            'dly_num' => count($ids),
            'dly_bns' => implode(',', $ids),
            'dly_orders' => '',
        );
        $id = $mdl_queue->insert($queue);
        $ident = $this->_createPrintIden($id, $userId);
        $sdf = array('queue_id' => $id, 'ident' => $ident);
        $mdl_queue->save($sdf);

        $mdl_queue_items = app::get(self::appFix)->model('print_queue_items');

        $i = 0;
        foreach ($ids as $delivery_id) {
            $queue_item = array(
                'ident' => $ident,
                'delivery_id' => $delivery_id,
                'ident_dly' => ++$i
            );
            $mdl_queue_items->insert($queue_item);
        }

        return $ident;
    }

    /**
     * 创建打印批次主标识
     *
     * @return String
     */
    private function _createPrintIden($id, $optid) {

        $iden = $id - (intval($id / 10000) * 10000);
        $predayIden = substr(date('y', time()),-1);
        $dayIden = $predayIden.date('md', time());
        $userIden = $optid;
        return sprintf("%s-%02d-%04d", $userIden, $dayIden, $iden);
    }

    /**
     * 检查打印批次是否存在
     * 
     * @param Array $ids
     * @return Array
     */
    private function _existsQueue($md5, $crc) {

        $mdl_queue = app::get(self::appFix)->model('print_queue');
        $queues = $mdl_queue->getList('*', array('crc' => $crc), 0, -1, ' queue_id DESC');

        if (is_array($queues) && !empty($queues)) {

            foreach ($queues as $queue) {

                if ($queue['md5'] == $md5) {
 
                    return $queue;
                }
            }
        }

        return array();
    }

    /**
     * 检查有效的打印批次是否存在
     * 
     * @param Array $ids
     * @return Array
     */
    private function _existsVaildQueue($md5) {

        $mdl_queue = app::get(self::appFix)->model('print_queue');
        //$queues = $mdl_queue->getList('*', array('crc' => $crc), 0, -1);

        return $mdl_queue->isVaildQueue($md5);
    }

    function deletePrintQueue($ident) {

        $mdl_queue = app::get(self::appFix)->model('print_queue');
        //$queues = $mdl_queue->getList('*', array('crc' => $crc), 0, -1);
        $mdl_queue->deletePrintQueueByIdent($ident);

        return true;
    }

    function emptyPrintQueueByIds($ids) {
        $mdl_queue = app::get(self::appFix)->model('print_queue');
        $mdl_queue->emptyPrintQueueByIds($ids);

        return true;
    }

    /**
     * 如果某发货单已经存在批次号,那就不生成新的，取已有批次号
     * 
     * @param $delivery_id
     * @return unknown_type
     */
    function _existsItemInQueue($delivery_id) {
        $mdl_queue = app::get(self::appFix)->model('print_queue');
        $ident = $mdl_queue->findIdent($delivery_id);

        if ($ident) {
            $queue = $mdl_queue->findQueue($ident);
            if ($queue) {
                return $queue;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    /**
     * 原则1 已经生成批次号的发货单不和未生成的发货 单进行打印,因为会导致新的批次号的产生
     *     2 在已经生成的批次号里面判断是不是同一批次号
     * 
     * @param $ids
     * @param $existsQueueItems
     * @return boolean
     */
    function isExistsQueueItems($ids, & $existsQueueItems=array()) {
        $exists_queue_items = array();
        //$mdl_queue_items = app::get(self::appFix)->model('print_queue_items');
        //$queue_items = $mdl_queue_items->getList('*', array('delivery_id' => $ids), 0, -1);
        $mdl_queue = app::get(self::appFix)->model('print_queue');
        $queue_items = $mdl_queue->getVaildQueueItems($ids);
        if ($queue_items) {
            $flag = false;
            if (count($queue_items) != count($ids)) {//已生成批次号的发货单不能和未生成的发货单一起打印，未打印的批次号视作未生成
                $flag = true;
                foreach ($queue_items as $row) {
                    $exists_queue_items[$row['delivery_id']] = $row['ident'];
                }
                $existsQueueItems = $exists_queue_items;
            } else {//是否在同一批次号中
                $first_row = current($queue_items);
                foreach ($queue_items as $row) {
                    if ($first_row['ident'] != $row['ident']) {
                        $flag = true;
                    }
                    $exists_queue_items[$row['delivery_id']] = $row['ident'];
                }
            }

            if ($flag) {
                $existsQueueItems = $exists_queue_items;
            }

            return $flag;
        } else {
            return false;
        }
    }

    /**
     * 根据发货单号的批次号获取同类的发货单号 ,避免相同批次号的发货单重复查询
     *
     */
    function getSimilarQueueItems($delivery_id) {
        $mdl_queue = app::get(self::appFix)->model('print_queue');
        $ident = $mdl_queue->findIdent($delivery_id);

        if ($ident) {
            $rows = $mdl_queue->findQueueItems($ident);
            if ($rows) {
                $queueItems = array();
                foreach ($rows as $row) {
                    $ident_dly = $ident . '_' . $row['ident_dly']; //加上批次号序列
                    $queueItems[$row['delivery_id']] = $ident_dly;
                }

                return $queueItems;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

}