<?php

$urlroot = "http://www.makusi.tv/hosting/wp-content/uploads/sites/3/";
$root = "/home/virtualmin/makusi.tv/public_html/wp-content/uploads/sites/3/";
include_once('lib/functions.php');

$mysqlConnect = connect_db();

/*Get attachments where parent-post =0 and attachments are video files.*/

$OrphanAttachmentsSelect = mysql_query("SELECT ID, post_title,post_name, post_date FROM wp_3_posts WHERE post_type='attachment' AND post_parent=0 AND post_mime_type='video/mp4'", $mysqlConnect);
$numOrphanAttachments = mysql_num_rows($OrphanAttachmentsSelect);
if($numOrphanAttachments > 0){
    $i=0;
    while($row = mysql_fetch_array($OrphanAttachmentsSelect)) {
        echo "DELETE: ID - ".$row['ID']." TITLE: ".$row['post_title'] . " NAME : " . $row['post_name'] . "\n";
        mysql_query("DELETE FROM wp_3_postmeta WHERE post_id=".$row['ID'], $mysqlConnect);
        mysql_query("DELETE FROM wp_3_posts WHERE ID=".$row['ID'], $mysqlConnect);
        unlink($root.date('Y',$row['post_date']).'/'.date('m',$row['post_date']).'/'.$row['post_name'].'.mpg');
    }
}
?>


