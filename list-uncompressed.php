<?php

include_once('config.php');
include_once('lib/functions.php');

$mysqlConnect = connect_db($mysqlServer,$mysqlUser,$mysqlPassword,$mysqlDatabase);

$UncompressedPosts = is_uncompressed_posts($mysqlConnect,$mysqlPrefix);

if( $UncompressedPosts === false){
    echo "0 Uncompressed \n";
    exit;
} else {
    echo "POSTS THAT HAVE BEEN COMPRESSED \n";
    echo "Num of Posts to be processed: ".$UncompressedPosts['numUncompressedPosts']."\n";
    $i = 0;
    while($post = mysql_fetch_assoc($UncompressedPosts['resultSelectUncompressed'])){
        echo "Post ID: ".$post['post_id']."\n";
    }
}
?>