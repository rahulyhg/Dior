#!/bin/bash
# Name : php-cgi

PHPBIN=/usr/local/php-pthreads/bin/php
BINBASEDIR=`dirname $0`
phpcgiprocess=$(ps -ef |grep php|grep taskmgr|grep -v timer|grep -v init|grep -v export|grep task)

if [ -z "$phpcgiprocess" ];then
        echo "$(date)  task  downd  ">> $BINBASEDIR/logs/phpdown.log
        rm -rf  /var/run/erp-taskDaemon-task.pid
        $PHPBIN  $BINBASEDIR/taskDaemon.php start task >> $BINBASEDIR/logs/phpdown.log
        echo "$(date) task ok" >> $BINBASEDIR/logs/phpdown.log

else
        echo "$(date) task ok" >> /tmp/erp-chechphp.log
fi

phpcgiprocess=$(ps -ef |grep taskmgr|grep taskDaemon|grep export)

if [ -z "$phpcgiprocess" ];then
        echo "$(date)  export  downd  ">> $BINBASEDIR/logs/phpdown.log
        rm -rf  /var/run/erp-taskDaemon-export.pid
        $PHPBIN  $BINBASEDIR/taskDaemon.php start export >> $BINBASEDIR/logs/phpdown.log
        echo "$(date) export ok" >> $BINBASEDIR/logs/phpdown.log

else
        echo "$(date) export ok" >> /tmp/erp-chechphp.log
fi

phpcgiprocess=$(ps -ef |grep taskmgr|grep taskDaemon|grep init)

if [ -z "$phpcgiprocess" ];then
        echo "$(date)  init  downd  ">> $BINBASEDIR/logs/phpdown.log
        rm -rf  /var/run/erp-taskDaemon-init.pid
        $PHPBIN  $BINBASEDIR/taskDaemon.php start init >> $BINBASEDIR/logs/phpdown.log
        echo "$(date) init ok" >> $BINBASEDIR/logs/phpdown.log

else
        echo "$(date) init ok" >> /tmp/erp-chechphp.log
fi

phpcgiprocess=$(ps -ef |grep taskmgr|grep taskDaemon|grep timer)

if [ -z "$phpcgiprocess" ];then
        echo "$(date)  timer  downd  ">> $BINBASEDIR/logs/phpdown.log
        rm -rf  /var/run/erp-taskDaemon-timer.pid
        $PHPBIN  $BINBASEDIR/taskDaemon.php start timer >> $BINBASEDIR/logs/phpdown.log
        echo "$(date) timer ok" >> $BINBASEDIR/logs/phpdown.log

else
        echo "$(date) timer ok" >> /tmp/erp-chechphp.log
fi