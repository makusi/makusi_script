<?php
/**
 * This file checks wordpress custom type "videos" in order to see which files are waiting 
 * queue.php depends on the lib/functions.php file which provides with basic functionality
 *
 * @package ProcessVideoQueue
 */

/*
 * 1. Get array with waiting posts. Exit on false.
 * 2. Loop each post and obtain associated attachments using the post_parent column relatioship
 * 3. Check if attached file is missing.
 * 4. Run ffmpeg to get one thumbnail
 * 5. Resize thumbnails 
 * 6. Store thumbnail info into database
 * 7. Store metadata into "videos" custom types
 * 8. Run ffmpeg to create a webm file.
 * 9. Store ffmpeg2webm info as metadata into "videos" custom types
 * 
 *  */
include_once('config.php');
include_once('lib/functions.php');

$mysqlConnect = connect_db($mysqlServer,$mysqlUser,$mysqlPassword,$mysqlDatabase);

/*
 * 1. Get array with waiting posts. Exit on false.
 * */
 
$WaitingPosts = is_waiting_posts($mysqlConnect,$mysqlPrefix,$blog_id);

if( $WaitingPosts === false){
    echo "No posts ARE WAITING \n";
    exit;
} else {
    
    echo "POSTS ARE WAITING \n";
    echo "Num of Posts to be processed: ".$WaitingPosts['numWaitingPosts']."\n";
    $i = 0;
/*
 * 2. Loop each post and obtain associated attachments using the post_parent column relatioship
 * */
    while($post = mysql_fetch_assoc($WaitingPosts['resultSelectWaiting'])){
        
        echo "Loop: ".$i."\n";
        echo "Post ID: ".$post['post_id']."\n";
        $associated_attachments = get_associated_attachment($post['post_id'],$mysqlConnect,$mysqlPrefix, $blog_id);
/*
 * 3. Check if attached file is missing.
 * */
        if($associated_attachments === false){
            
            echo "No attachments are associated to ". $post['post_id']."\n";
            
        } else {
            
            echo "Number of Attached files: ".$associated_attachments['numAssociatedAttachments']."\n";
            echo "Attachment ID: ".$associated_attachments['attachment_id']."\n";
            $attachedFile = get_attached_file($post['post_id'],$mysqlConnect,$mysqlPrefix, $blog_id);
            
            if($attachedFile === false ){
                echo "No path was provided for this attachment_id ".$associated_attachments['attachment_id']."\n";
            } elseif(is_in_trash($post['post_id'], $mysqlConnect,$mysqlPrefix, $blog_id) === true) {
                echo "In trash attachment_id ".$post['post_id']."\n";
            } else {
                
                echo "Path: ".$attachedFile['path']."\n";
                $filepath = $root.$attachedFile['path'];
                $urlpath = $urlroot.$attachedFile['path'];
                echo "FilePath: ".$filepath."\n";
                
                if(!is_file($filepath)){
                    
                    echo "FILE NOT FOUND: ".$filepath."\n";
                    //Update from queue
                    mysql_query('UPDATE '.$mysqlPrefix.'_'.$blog_id.'_postmeta
                                    SET meta_value = "missing"
                                    WHERE post_id='.$post['post_id'].'
                                    AND meta_key="queue_status"',$mysqlConnect);
                
                    
                } else {
 /*
 * 4. Run ffmpeg to get one thumbnail
 * */                    
                    $output = array();
                    $videolength = get_video_duration($post['post_id'],$mysqlConnect,$mysqlPrefix,$blog_id);
                    $extractionpoint = intval($videolength/2);
                    manage_postmeta($post['post_id'], 'queue_status', 'processing', $mysqlConnect,$mysqlPrefix, $blog_id);
                    //$ffmpegCommand = "sudo /usr/local/bin/ffmpeg/ffmpeg -y -i ".$filepath." -vf \"thumbnail\" -ss 7 -frames:v 1 -vsync vfr ".path_without_extension($filepath).".png 2>&1";
                    $ffmpegCommand = "sudo /usr/local/bin/ffmpeg/ffmpeg -y -i ".$filepath." -ss ".$extractionpoint." -frames:v 1 -vsync vfr ".path_without_extension($filepath).".jpg 2>&1";

                    exec($ffmpegCommand,$output);
                    $raw_info = implode('<br />', $output);
                    $info = serialize($output);
                    
/*                    
 * 5. Resize thumbnails 
 */
                    $sizes = array(
                        array(
                            'sizename' => 'home-screen',
                            'width' => '1110',
                            'height' => '400'
                            ),
                        array(
                            'sizename' => 'home-carousel',
                            'width' => '260',
                            'height' => '135'
                            ),
                        array(
                            'sizename' => 'double-column',
                            'width' => '300',
                            'height' => '169'
                            ),
                        array(
                            'sizename' => 'triple-column',
                            'width' => '260',
                            'height' => '146'
                            ),
                        array(
                            'sizename' => 'single-column',
                            'width' => '730',
                            'height' => '260'
                            ),
                        array(
                            'sizename' => 'single-video',
                            'width' => '730',
                            'height' => '380'
                            ),
                        array(
                            'sizename' => 'sidebar',
                            'width' => '350',
                            'height' => '180'
                            )
                    );
                    create_thumbnails(video_jpg($filepath),$sizes);
/*                    
 * 6. Store thumbnail info into database
*/
                    
                    $metadata = get_meta_data($associated_attachments['attachment_id'],$mysqlConnect,$mysqlPrefix, $blog_id);
                    
                    if($metadata != false){
                        //4.2 Metadata is stored as a json string and must be converted to an array.
                        $metadataArray = unserialize($metadata['meta_value']);
                        
                        $urlWithoutExtension = path_without_extension($urlpath);
                        $pathWithoutExtension = path_without_extension($filepath);
                        //4.3 Add sizes subarray to metadata
                        $metadataArray['sizes'] = insert_meta_data_sizes($pathWithoutExtension,$urlWithoutExtension, $sizes);
                        //4.4 convert array into json
                        $metadataSerialized = addcslashes(serialize($metadataArray),'\'' );
                        $mysqlConnect2 = connect_db();
                        mysql_query("UPDATE ".$mysqlPrefix."_".$blog_id."_postmeta 
                                    SET meta_value='".$metadataSerialized."' 
                                    WHERE meta_id=".$metadata['meta_id'],$mysqlConnect2) or die("UPDATE ERROR:". mysql_error());
    
                        //5. Insert value of thumbnail
                        manage_postmeta($post['post_id'], '_thumbnail_id', $associated_attachments['attachment_id'], $mysqlConnect,$mysqlPrefix, $blog_id);
                    } else {
                        echo "Metadata was not correctly stored due to mysql failure";
                        echo "\n";
                    }

/*
 *  7. Store metadata into "videos" custom types
 */
                    $mysqlConnect = connect_db($mysqlServer,$mysqlUser,$mysqlPassword,$mysqlDatabase);
                    manage_postmeta($post['post_id'], 'queue_info', $info, $mysqlConnect,$mysqlPrefix, $blog_id);
                    manage_postmeta($post['post_id'], 'queue_raw_info', $raw_info, $mysqlConnect,$mysqlPrefix, $blog_id);
                    manage_postmeta($post['post_id'], 'queue_status', 'uncompressed', $mysqlConnect,$mysqlPrefix, $blog_id);
/*
 * 8. Run ffmpeg to create a webm file.
 */
                    $mobile_compress_info = compress4mobile($filepath);
 /*
 * 9. Run ffmpeg to create a webm file.
 */
                    manage_postmeta($post['post_id'], 'mobile_compress_info', $mobile_compress_info, $mysqlConnect,$mysqlPrefix, $blog_id); 
                }
            }
        }
    $i++;
    echo "______________________________________";
    echo "\n";
    }
}
//