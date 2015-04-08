<?php
include_once('resize-class.php');
$mysqlPrefix = "ltlaxkw6";
function connect_db($mysqlServer,$mysqlUser,$mysqlPassword,$mysqlDatabase){
    //MYSQL Connnection data
    
    $mysqlConnect = @mysql_connect($mysqlServer,$mysqlUser, $mysqlPassword );

    $mysqSelectDb = @mysql_select_db($mysqlDatabase);
    return $mysqlConnect;
}
// FUNCTION TO PROCESS QUEUE AND GENERATE THUMBNAILS WITH FFMPEG

function ext($path){
    return pathinfo($path, PATHINFO_EXTENSION);
}
function remove_ext($path){
	return substr($path,0,strlen($path)-4);
}

function without_ext($path){
	echo remove_ext($path);
}

function video_png($path){
	return remove_ext($path).".png";
}


function video_jpg($path){
	return remove_ext($path).".jpg";
}

function url_without_extension($path){
	$pathArray = explode('/', $path);
	$pathArrayReverse = array_reverse($pathArray);
	$filename = $pathArrayReverse[0]; 
	$filenameArray = explode('.',$path);
	$filenameArrayReverse = $filenameArray;
	return $filenameArray[0];
}

function path_without_extension($path){
	return $withoutExt = preg_replace("/\\.[^.\\s]{3,4}$/","", $path);
}


function create_thumbnail($path, $dst_w, $dst_h){
    // *** 1) Initialise / load image
	$resizeObj = new resize($path);

	// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
	$resizeObj -> resizeImage($dst_w, $dst_h, 'crop');

	// *** 3) Save image
	$resizeObj -> saveImage(remove_ext($path).'_'.$dst_w.'x'.$dst_h.'.jpg', 72);
}

function manage_postmeta($post_id, $meta_key, $meta_value, $mysqlConnect,$mysqlPrefix, $blog_id){
    //1. does the meta field already exist?
   $query = "SELECT * FROM ".$mysqlPrefix."_".$blog_id."_postmeta WHERE post_id = ".$post_id." AND meta_key='".$meta_key."'";
   $result = mysql_query($query,$mysqlConnect) or die(mysql_error());
   if(mysql_num_rows($result) == 0){
       //INSERT
       $query = "INSERT INTO ".$mysqlPrefix."_".$blog_id."_postmeta(post_id, meta_key, meta_value) VALUES (".$post_id.", '".$meta_key."', '".mysql_real_escape_string($meta_value)."')";
       mysql_query($query, $mysqlConnect) or die(mysql_error());
   } else {
       //UPDATE
       $query = "UPDATE ".$mysqlPrefix."_".$blog_id."_postmeta SET meta_value='".mysql_real_escape_string($meta_value)."' WHERE post_id=".$post_id." AND meta_key='".$meta_key."'";
       mysql_query($query);
   }
}

function obtain_month($path){
    $path_array = explode('/',$path);
    return($path_array[1]);
}
function is_waiting_posts($mysqlConnect,$mysqlPrefix,$blog_id){
    $resultSelect = mysql_query("SELECT * 
				FROM ".$mysqlPrefix."_".$blog_id."_postmeta 
				WHERE meta_key = 'queue_status' 
				AND (meta_value = 'waiting' 
                                OR meta_value = '')", 
                                $mysqlConnect);
    $numWaitingPosts = mysql_num_rows($resultSelect);
    if($numWaitingPosts > 0){
        $array['numWaitingPosts'] = $numWaitingPosts;
        $array['resultSelectWaiting'] = $resultSelect;
        return $array;
    } else {
        return false;
    }
}


function is_uncompressed_posts($mysqlConnect,$mysqlPrefix, $blog_id){
    $resultSelect = mysql_query("SELECT * 
				FROM ".$mysqlPrefix."_".$blog_id."_postmeta 
				WHERE meta_key = 'queue_status' 
				AND (meta_value = 'uncompressed' 
                                OR meta_value = '')", 
                                $mysqlConnect);
    $numUncompressedPosts = mysql_num_rows($resultSelect);
    if($numUncompressedPosts > 0){
        $array['numUncompressedPosts'] = $numUncompressedPosts;
        $array['resultSelectUncompressed'] = $resultSelect;
        return $array;
    } else {
        return false;
    }
}

function get_attached_file($post_id,$mysqlConnect,$mysqlPrefix, $blog_id){
    $associatedAttachment = get_associated_attachment($post_id, $mysqlConnect,$mysqlPrefix, $blog_id);
    $resultAttachmentFileSelect = mysql_query("SELECT meta_value 
        						FROM ".$mysqlPrefix."_".$blog_id."_postmeta 
        						WHERE meta_key = '_wp_attached_file' 
        						AND post_id = ".$associatedAttachment['attachment_id'], 
        						$mysqlConnect);
    $numAttachmentFileSelect = mysql_num_rows($resultAttachmentFileSelect);
    if($numAttachmentFileSelect > 0){
        $array['numAttachmentFileSelect'] = $numAttachmentFileSelect;
        $array['path'] = mysql_result($resultAttachmentFileSelect,0) or die(mysql_error());
        return $array;
    } else {
        return false;
    }    
}

function get_video_duration($post_id,$mysqlConnect,$mysqlPrefix, $blog_id){
    $associatedAttachment = get_associated_attachment($post_id, $mysqlConnect,$mysqlPrefix, $blog_id);
    echo "SELECT meta_value 
        						FROM ".$mysqlPrefix."_".$blog_id."_postmeta 
        						WHERE meta_key = '_wp_attachment_metadata' 
        						AND post_id = ".$associatedAttachment['attachment_id'];
    $resultAttachmentMetadataSelect = mysql_query("SELECT meta_value 
        						FROM ".$mysqlPrefix."_".$blog_id."_postmeta 
        						WHERE meta_key = '_wp_attachment_metadata' 
        						AND post_id = ".$associatedAttachment['attachment_id'], 
        						$mysqlConnect);
    $numrows = mysql_num_rows($resultAttachmentMetadataSelect);
    if($numrows > 0){
        $data = mysql_result($resultAttachmentMetadataSelect,0) or die(mysql_error());
        $array = unserialize($data);
    }
    return $array['length'];
}
function is_in_trash($post_id, $mysqlConnect,$mysqlPrefix, $blog_id){
    $selectPostsInTrash = mysql_query("SELECT * FROM ".$mysqlPrefix."_".$blog_id."_postmeta 
                                            WHERE meta_key = '_wp_trash_meta_status' 
                                            AND meta_value = 'publish'
                                            AND post_id = ".$post_id,
                                        $mysqlConnect);
    $numRows = mysql_num_rows($selectPostsInTrash);
    if($numRows > 0){
        return true;
    }else{
        return false;
    }
}

function get_meta_data($attachment_id,$mysqlConnect,$mysqlPrefix, $blog_id){
    
    $mysqlConnect = connect_db();
    $metadataResult = mysql_query("SELECT meta_id, meta_value FROM ".$mysqlPrefix."_".$blog_id."_postmeta WHERE post_id=".$attachment_id." AND meta_key='_wp_attachment_metadata'", $mysqlConnect)or die(mysql_error());
    //var_dump($metadataResult);
    if($metadataResult != NULL){
        $numRows = mysql_num_rows($metadataResult);
        if($numRows > 0){
            return $metadata = mysql_fetch_assoc($metadataResult);
        } else {
            return false;
        }  
    } else {
        return false;
    }  
}

function insert_meta_data_sizes($pathWithoutExtension,$urlWithoutExtension, $sizes){
    $i=0;
    $array_results = array();
    //var_dump($sizes);
    foreach ($sizes as $size){
        $array_results[$size['sizename']]=array(
            'file' => $pathWithoutExtension."_".$size['width']."x".$size['height'].".jpg",
            'url' => $urlWithoutExtension."_".$size['width']."x".$size['height'].".jpg",
            'width' => $size['width'],
            'height' => $size['height'],
            'mime-type' => 'image/jpg'
        ); 
        $i++;
    }
    return $array_results;
}


function create_thumbnails($path,$sizes){
    foreach($sizes as $size){
        create_thumbnail($path,$size['width'],$size['height']); 
    }
}

function convert_to_mp4($filepath,$attachment_id, $blog_id){
    global $mysqlPrefix;
    echo "Filepath:\n";
    echo $filepath;
    echo "Attachment ID:\n";
    echo $attachment_id;
    echo "\n";
    if(ext($filepath) == "mov" || ext($filepath) == "avi"){
        //1. Make compression
        $ffmpegCommand = "sudo /usr/local/bin/ffmpeg/ffmpeg -i ".$filepath."  ".path_without_extension($filepath).".mp4";
        exec($ffmpegCommand,$output);
        //2. change data in dabase.
        $SelectQuery = "SELECT meta_value FROM ".$mysqlPrefix."_".$blog_id."_postmeta WHERE meta_key='_wp_attached_file' AND post_id=".$attachment_id;
        $Query = mysql_query($SelectQuery) or die("Error in meta data input for mp4 conversion: ".mysql_error());
        $path = mysql_result($Query,0);
        $UpdateQuery = "UPDATE ".$mysqlPrefix."_".$blog_id."_postmeta SET meta_value='".path_without_extension($path).".mp4' WHERE meta_key='_wp_attached_file' AND post_id=".$attachment_id;
        mysql_query($UpdateQuery) or die("Error in meta data input for mp4 update: ".mysql_error());
        return $output;
    } else {
    }
}

function get_associated_attachment($post_id, $mysqlConnect,$mysqlPrefix, $blog_id){
    echo "SELECT ID 
                            FROM ".$mysqlPrefix."_".$blog_id."_posts
                            WHERE post_parent = ".$post_id." 
                            AND post_type='attachment'
                            AND post_mime_type='video/mp4'";
    $query = mysql_query("SELECT ID 
                            FROM ".$mysqlPrefix."_".$blog_id."_posts
                            WHERE post_parent = ".$post_id." 
                            AND post_type='attachment'
                            AND post_mime_type='video/mp4'", $mysqlConnect);
    $array = mysql_fetch_assoc($query);

    foreach($array as $row){
        $query2 = mysql_query("SELECT meta_value 
				FROM ".$mysqlPrefix."_".$blog_id."_postmeta
				WHERE meta_key='_wp_attached_file' 
				AND post_id=".$row, $mysqlConnect);
	$numRows = mysql_num_rows($query2);
	if($numRows > 0){
            $array['numAssociatedAttachments'] = $numRows;
            $array['attachment_id'] = $row;
            return $array;
	} else {
            return false;
	}
    }
}

function get_associated_attachment_previous($post_id, $mysqlConnect,$mysqlPrefix, $blog_id){
    $resultAttachmentSelect = mysql_query("SELECT meta_value 
    					FROM ".$mysqlPrefix."_".$blog_id."_postmeta 
    					WHERE (meta_key = 'file' 
                                        OR meta_key = '_wp_attached_file')
    					AND post_id = ".$post_id, 
    					$mysqlConnect);
    $numAssociatedAttachments = mysql_num_rows($resultAttachmentSelect);
    if($numAssociatedAttachments > 0){
        $array['numAssociatedAttachments'] = $numAssociatedAttachments;
        $array['attachment_id'] = mysql_result( $resultAttachmentSelect,0) or die(mysql_error());
        $array['resultAttachmentSelect'] = $resultAttachmentSelect;
        return $array;
    } else {
        return false;
    }
}

function compress4mobile($inputPath){
    $outputPath = path_without_extension($inputPath).".webm";
    $ffmpeg = "sudo /usr/local/bin/ffmpeg/ffmpeg -i ".$inputPath." -pass 1 -passlogfile ".$inputPath." -threads 16  -keyint_min 0 -g 250 -skip_threshold 0 -qmin 1 -qmax 51 -vcodec libvpx -b:v 204800 -s 320x240 -aspect 4:3 -an -f webm -y NUL && sudo /usr/local/bin/ffmpeg/ffmpeg -i ".$inputPath." -pass 2 -passlogfile ".$inputPath." -threads 16  -keyint_min 0 -g 250 -skip_threshold 0 -qmin 1 -qmax 51 -vcodec libvpx -b:v 204800 -s 320x240 -aspect 4:3 -acodec libvorbis -ac 2 -y ".$outputPath." 2>&1";
    exec($ffmpeg,$output);
    return $raw_info = implode('<br />', $output);
}
?>
