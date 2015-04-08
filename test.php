<?php

    $urlroot = "https://www.makusi.tv/wp-content/uploads/";
    $root = "/home/virtualmin/makusi.tv/public_html/wp-content/uploads/";
    $mysqlPrefix = "ltlaxkw6";
    $blog_id = 3;
    include_once('lib/functions.php');

    $mysqlConnect = connect_db();

    $associated_attachments = get_associated_attachment2(2158,$mysqlConnect,$mysqlPrefix, $blog_id);
    var_dump($associated_attachments);

?>