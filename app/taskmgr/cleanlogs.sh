#!/bin/bash

NewLOG=$(date +%Y%m%d)
OldLOG=$(date +%Y%m%d --date='14 days ago')
LOGDIR=$(dirname $0)/logs
cd $LOGDIR
if [ -d "$LOGDIR/$OldLOG" ];then
	rm -rf $OldLOG
        echo "$(date) $OldLOG has deleted" >>/tmp/erp-dellogs.log
else
	echo "$(date) no logs $OldLOG" >>/tmp/erp-dellogs.log
fi
