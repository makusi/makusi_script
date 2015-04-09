<?php

include_once('config.php');
include_once('lib/functions.php');

$mysqlConnect = connect_db($mysqlServer,$mysqlUser,$mysqlPassword,$mysqlDatabase);

$WaitingPosts = is_waiting_posts($mysqlConnect,$mysqlPrefix);

if( $WaitingPosts === false){
    echo "No posts ARE WAITING \n";
    exit;
} else {
    echo "POSTS ARE WAITING \n";
    echo "Num of Posts to be processed: ".$WaitingPosts['numWaitingPosts']."\n";
    while($post = mysql_fetch_assoc($WaitingPosts['resultSelectWaiting'])){
        echo "Post ID: ".$post['post_id']."\n";
    }
}
?>