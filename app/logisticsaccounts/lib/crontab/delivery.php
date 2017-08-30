<?php
    require_once(dirname(__FILE__) .'/config.php');
    cachemgr::init(false);
   kernel::single('logisticsaccounts_estimate')->crontab_delivery();
