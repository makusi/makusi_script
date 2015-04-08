<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//DEFINICION DE VARIABLES

//$argv[1] corresponde al primer valor aportado en el comando php -e compresion nombredelarchivo.mp4
$inputFile = $argv[1];
$outputFile = $argv[1];
$inputFolder = "/home/xaresd/inaki/Videos/";
$outputFolder = "/home/xaresd/inaki/comprimidos/";

/*PARAMETROS DE COMPRESION*/
//codec
$parametrosCompresion ="-codec:v libx264 ";
//Profile
//Profile constrains H.264 to a subset of features - higher profiles require more CPU power to decode and are able to generate better looking videos at same bitrate. You should always choose the best profile your target devices support.
$parametrosCompresion .="-profile:v high ";
//Bitrate según el segundo valor aportado a la linea de comandos 320p | 360p | 480p | 576p | 720p

if(isset($argv[2])){
    switch($argv[2]){
        // 320p for mobiles
        case "320p":
        $parametrosCompresion .= "-b:v 180k -maxrate 180k -bufsize 360k ";
        break;
        //360p
        case "360p":
        $parametrosCompresion .= "-b:v 300k -maxrate 300k -bufsize 600k";
        break;
        // 480p
        case "480p":
        $parametrosCompresion .= "-b:v 500k -maxrate 500k -bufsize 1000k ";
        break;
        // 576p (SD/PAL)
        case "576p":
        $parametrosCompresion .= "-b:v 850k -maxrate 850k -bufsize 1700k ";
        break;
        // 720p
        case "720p":
        $parametrosCompresion .= "-b:v 1000k -maxrate 1000k -bufsize 2000k ";
        break;
        default:
        $parametrosCompresion .= "-b:v 500k -maxrate 500k -bufsize 1000k ";
        break;
    }
}

//DEFINIMOS LOS CAMINOS A LOS ARCHIVOS DE ENTRADA Y SALIDA RESPECTO DE LA RAIZ
$inputPath = $inputFolder.$inputFile;
$outputPath = $outputFolder.$outputFile;

//obtención de datos del archivo entrante
$input_filesize = ffprobe_render(probe($inputPath));

//COMPRESION
compress($inputPath, $outputPath, $parametrosCompresion);
compress4mobile($inputPath);
//var_dump($raw_info);

//obtención de datos del archivo entrante
$output_filesize = ffprobe_render(probe($outputPath));
//MOSTRAR RESULTADOS EN CONSOLA
echo "COMPRESION: \n";
echo "input Size: ". $input_filesize."\n";
echo "output Size: ". $output_filesize."\n";
echo "%: ".($output_filesize/$input_filesize)*100 ." %";

//FUNCIONES
//DEFINE el valor del tamaño en forma humanamente legible
function human_filesize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

//Ejetuta ffprobe donde analiza el archivo entrante y saliente y puede deducir los metavalores de diferencia
function probe($path){
    $ffprobe="sudo /usr/local/bin/ffmpeg/ffprobe -v quiet -print_format json -show_format";
    exec($ffprobe. " ". $path,$probe);
    $raw_info_probe = implode('', $probe);
    return json_decode($raw_info_probe,true);
}

//ORGANIZA LOS VALORES DE ANALISIS DEL ARCHIVO
function ffprobe_render($ffprobe_array){
    foreach( $ffprobe_array['format'] as $key=>$value){
    echo $key." :";
    if(is_array($value)){   
        foreach($value as $key2=>$value2){
            echo "\n";
            echo " ".$key2." : ";
            echo $value2;
        }
    } else {
        echo $value;
        if($key =="size"){
            $size_value = human_filesize($value);
            echo "(".$size_value.")\n";
            return $size_value;
        }
    }
    echo "\n";
    }
}
function compress($inputPath, $outputPath, $parametrosCompresion){
    $ffmpeg = "sudo /usr/local/bin/ffmpeg/ffmpeg -i";
    $ffmpegCommand = $ffmpeg." ".$inputPath." ".$parametrosCompresion." ".$outputPath;
    exec($ffmpegCommand,$output);
    return $raw_info = implode('<br />', $output);
}

function compress4mobile($inputPath){
    $outputPath = path_without_extension($inputPath).".webm";
    $ffmpeg = "sudo /usr/local/bin/ffmpeg/ffmpeg -i ".$inputPath." -pass 1 -passlogfile ".$inputPath." -threads 16  -keyint_min 0 -g 250 -skip_threshold 0 -qmin 1 -qmax 51 -vcodec libvpx -b 204800 -s 320x240 -aspect 4:3 -an -f webm -y NUL && sudo /usr/local/bin/ffmpeg/ffmpeg -i ".$inputPath." -pass 2 -passlogfile ".$inputPath." -threads 16  -keyint_min 0 -g 250 -skip_threshold 0 -qmin 1 -qmax 51 -vcodec libvpx -b 204800 -s 320x240 -aspect 4:3 -acodec libvorbis -ac 2 -y ".$outputPath;
    exec($ffmpeg,$output);
    return $raw_info = implode('<br />', $output);
}
function path_without_extension($path){
    return $withoutExt = preg_replace("/\\.[^.\\s]{3,4}$/","", $path);
}
?>

