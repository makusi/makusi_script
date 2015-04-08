<?php

$urlroot = "http://www.makusi.tv/wp-content/uploads/";
$root = "/home/virtualmin/makusi.tv/public_html/wp-content/uploads/";
include_once('lib/functions.php');

$mysqlConnect = connect_db();

$UncompressedPosts = is_uncompressed_posts($mysqlConnect);

if( $UncompressedPosts === false){
    echo "No posts TO BE COMPRESSED \n";
    exit;
} else {
    echo "POSTS ARE UNCOMPRESSED \n";
    echo "Num of Posts to be processed: ".$UncompressedPosts['numUncompressedPosts']."\n";
    $i = 0;
    while($post = mysql_fetch_assoc($UncompressedPosts['resultSelectUncompressed'])){
        echo "Loop: ".$i."\n";
        echo "Post ID: ".$post['post_id']."\n";
        $associated_attachments = get_associated_attachment($post['post_id'],$mysqlConnect );
        if($associated_attachments === false){
            echo "No attachments are associated to ". $post['post_id']."\n";
        } else {
            echo "Number of Attached files: ".$associated_attachments['numAssociatedAttachments']."\n";
            echo "Attachment ID: ".$associated_attachments['attachment_id']."\n";
            $attachedFile = get_attached_file($post['post_id'],$mysqlConnect);
            if($attachedFile === false ){
                echo "No path was provided for this attachment_id ".$associated_attachments['attachment_id']."\n";
            } elseif(is_in_trash($post['post_id'], $mysqlConnect) === true) {
                echo "In trash attachment_id ".$post['post_id']."\n";
            } else {
                echo "Path: ".$attachedFile['path']."\n";
                $filepath = $root.$attachedFile['path'];
                $urlpath = $urlroot.$attachedFile['path'];
                echo "FilePath: ".$filepath."\n";
                if(!is_file($filepath)){
                    echo "FILE NOT FOUND: ".$filepath."\n";
                    //Update from queue
                    mysql_query('UPDATE wp_3_postmeta
                                    SET meta_value = "missing"
                                    WHERE post_id='.$post['post_id'].'
                                    AND meta_key="queue_status"',$mysqlConnect);
                } else {
                    $output = array();
                    //$ffmpegCommand = "sudo /usr/local/bin/ffmpeg/ffmpeg -y -i ".$filepath." -vf \"thumbnail\" -ss 7 -frames:v 1 -vsync vfr ".path_without_extension($filepath).".png 2>&1";
                    //$ffmpegCommand = "sudo /usr/local/bin/ffmpeg/ffmpeg -y -i ".$filepath." -ss 7 -frames:v 1 -vsync vfr ".path_without_extension($filepath).".png 2>&1";
                    
                    //$ffmpegCommand = "sudo /usr/local/bin/ffmpeg/ffmpeg -i ".$filepath."";
                    
                    //exec($ffmpegCommand,$output);
                    //$raw_info = implode('<br />', $output);
                    //$info = serialize($output);
                    //
                    // include getID3() library (can be in a different directory if full path is specified)
                    require_once('lib/getid3/getid3.php');

                    // Initialize getID3 engine
                    $getID3 = new getID3;

                    // Analyze file and store returned data in $ThisFileInfo
                    $ThisFileInfo = $getID3->analyze($filepath);
                    getid3_lib::CopyTagsToComments($ThisFileInfo);
                    //var_dump($ThisFileInfo);
                    manage_postmeta($post['post_id'], 'queue_filesize_before', $ThisFileInfo["filesize"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_fileformat_before', $ThisFileInfo["fileformat"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_bitrate_before', $ThisFileInfo["bitrate"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiobitrate_before', $ThisFileInfo["audio"]["bitrate"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audioformat_before', $ThisFileInfo["audio"]["dataformat"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiocoded_before', $ThisFileInfo["audio"]["codec"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audioformat_before', $ThisFileInfo["audio"]["dataformat"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiosamplerate_before', $ThisFileInfo["audio"]["sample_rate"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiochannels_before', $ThisFileInfo["audio"]["channels"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiolossless_before', $ThisFileInfo["audio"]["lossless"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiochannelmode_before', $ThisFileInfo["audio"]["channelmode"], $mysqlConnect);
                    
                    echo $CompressionffmpegCommand = "sudo /usr/local/bin/ffmpeg/ffmpeg -i ".$filepath." ".$filepath;
                    /*exec($CompressionffmpegCommand,$compressionoutput);
                    $compression_raw_info = implode('<br />', $compressionoutput);
                    $compressioninfo = serialize($compressionoutput);
                    //4. META DATA
                    //4.1 Obtain meta data from wp_3_postmeta
                    //$metadata = get_meta_data($associated_attachments['attachment_id'],$mysqlConnect);
                    //4.2 Metadata is stored as a json string and must be converted to an array.
                    //$metadataArray = unserialize($metadata['meta_value']);
                    //echo "File Size:";
                    //echo $metadataArray['filesize'];
                    //echo "\n";
                    //$urlWithoutExtension = path_without_extension($urlpath);
                    //$pathWithoutExtension = path_without_extension($filepath);
                    //4.4 convert array into json
                    //$metadataSerialized = serialize($metadataArray);
                    //4.5 store metadata with new data
        
                    //mysql_query("UPDATE wp_3_postmeta 
                                    //SET meta_value='".$metadataSerialized."' 
                                    //WHERE meta_id=".$metadata['meta_id']);
                    
                    //FIND OUT IF THIS FILE HAS BEEN CONVERTED IF NOT 
                    manage_postmeta($post['post_id'], 'queue_compression_info', $compressioninfo, $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_raw_compression_info', $compression_raw_info, $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_status', 'done', $mysqlConnect);
                    
                    $CompressedFileInfo = $getID3->analyze($filepath);
                    getid3_lib::CopyTagsToComments($CompressedFileInfo);
                    manage_postmeta($post['post_id'], 'queue_filesize_after', $CompressedFileInfo["filesize"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_fileformat_after', $CompressedFileInfo["fileformat"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_bitrate_after', $CompressedFileInfo["bitrate"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiobitrate_after', $CompressedFileInfo["audio"]["bitrate"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audioformat_after', $CompressedFileInfo["audio"]["dataformat"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiocoded_after', $CompressedFileInfo["audio"]["codec"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audioformat_after', $CompressedFileInfo["audio"]["dataformat"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiosamplerate_after', $CompressedFileInfo["audio"]["sample_rate"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiochannels_after', $CompressedFileInfo["audio"]["channels"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiolossless_after', $CompressedFileInfo["audio"]["lossless"], $mysqlConnect);
                    manage_postmeta($post['post_id'], 'queue_audiochannelmode_after', $CompressedFileInfo["audio"]["channelmode"], $mysqlConnect);*/
                }
            }
        }
    }
}