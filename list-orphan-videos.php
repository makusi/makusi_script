<?php

include_once('config.php');
include_once('lib/functions.php');

$mysqlConnect = connect_db($mysqlServer,$mysqlUser,$mysqlPassword,$mysqlDatabase);

$SelectOrphanVideos="SELECT * FROM `".$mysqlPrefix."_3_posts` WHERE `post_type` = 'attachment' AND `post_mime_type` LIKE '%video%' AND `post_parent` = 0";
$SelectOrphanVideosQuery = mysql_query($SelectOrphanVideos,$mysqlConnect);
$SelectOrphanVideosNumRows = mysql_num_rows($SelectOrphanVideosQuery);

//echo $SelectOrphanVideosNumRows."\n\n";
if ($SelectOrphanVideosNumRows == 0){
    echo "No Orphan Videos";
}else{
    echo "There are ".$SelectOrphanVideosNumRows." Orphan Videos";
    while($attachment = mysql_fetch_array($SelectOrphanVideosQuery)){
        // var_dump($attachment);
        echo "Attachment ID\n";
        echo $attachment['ID']."\n";
        echo "Title\n";
        echo $attachment['post_title']."\n\n";
    }
}
?>

