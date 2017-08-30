<?php
	$sql = "INSERT INTO sdb_ome_operations (operation_id,operation_name) VALUES ('34','发货单打回')";
	kernel::database()->exec($sql);