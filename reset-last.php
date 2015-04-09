<?php

include_once('config.php');
include_once('lib/functions.php');

$mysqlConnect = connect_db($mysqlServer,$mysqlUser,$mysqlPassword,$mysqlDatabase);
$SelectLast = "SELECT max(meta_id), post_id FROM ltlaxkw6_3_postmeta WHERE post_id=(SELECT max(post_id) FROM ltlaxkw6_3_postmeta)";
$querySelectLast = mysql_query($SelectLast, $mysqlConnect) or die(mysql_error());
$mysql_num_rows = mysql_num_rows($querySelectLast);
$i=0;
if($mysql_num_rows > 0){
    //while($row = mysql_fetch_assoc($querySelectLast)){
        $row = mysql_fetch_assoc($querySelectLast);
    //}
}


$queryUpdate = "UPDATE ltlaxkw6_3_postmeta SET meta_value='waiting' WHERE meta_key = 'queue_status' AND post_id=".$row['post_id'];
mysql_query($queryUpdate, $mysqlConnect) or die(mysql_error());

?>
