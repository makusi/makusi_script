<?php

include_once('lib/functions.php');

$mysqlConnect = connect_db();

$queryUpdate = "UPDATE ltlaxkw6_3_postmeta SET meta_value='waiting' WHERE meta_key = 'queue_status'";
mysql_query($queryUpdate, $mysqlConnect) or die(mysql_error());

?>
