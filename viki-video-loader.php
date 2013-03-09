#!/usr/bin/php -q
<?
/*************************************************
File:		viki-video-loader.php
Date:		08-03-2013
Build:		v1.0.1
Brief:		Viki.com video & subtitles downloader
		Code by Mefh! <mefhigoseth@gmail.com>

[08-03-2013] v1.0.1 - Mefh!
+ Fork of viki-str-loader.php and added video downloader capabilities.

[05-03-2013] v1.0.0 - Mefh!
+ First draft.
*************************************************/
ob_implicit_flush(TRUE);

//  Funcion que obtiene la seleccion del usuario a traves de un prompt...
function promptUser($promptStr,$defaultVal=false) {

	if($defaultVal) {
		echo $promptStr. " [". $defaultVal. "] : ";
	}
	else {
		echo $promptStr. ": ";
	}
	$name = chop(fgets(STDIN));
	if(empty($name)) {
		return $defaultVal;
	}
	else {
		return $name;
	}
}

/*
* Main Code
*/
echo "====================================\n";
echo "  Viki.com video downloader\n";
echo "         Coded by Mefh!\n";
echo "====================================\n";

$viki_lang = promptUser("Lenguaje","es");
$viki_id = promptUser("Video ID");

echo "Obteniendo informacion ... ";
$viki_info = file_get_contents("http://www.viki.com/player/medias/".$viki_id."/info.json");

if( $viki_info === FALSE ) {
	die("Error.\r\n");
}

echo "Ok.\r\n";
$viki_json = json_decode($viki_info,TRUE);
//print_r($viki_json);
echo "\r\nTitulo: ".$viki_json['title']."\r\n";
echo "Descripcion: ".$viki_json['description']."\r\n\r\n";

promptUser("Presione 'Enter' para continuar");


echo "Descargando subtitulos ... ";
$viki_ljson = file_get_contents("http://www.viki.com/subtitles/media/".$viki_id."/".$viki_lang.".json");

if( $viki_ljson === FALSE ) {
	die("Error.\r\n");
}

echo "Ok.\r\n";
echo "Convirtiendo JSON a STR... ";

$viki_array = json_decode($viki_ljson,TRUE);
echo "Ok.\r\n";
//print_r($viki_array);

echo "Creando archivo srt... ";
if( !($fp = fopen($viki_id."-".$viki_lang.".srt", "a+")) ) {
	die("Error.\r\n");
}

$c = 1;

foreach($viki_array['subtitles'] as $srt => $srtData) {
	fwrite($fp,$c."\r\n");
	$timeStart = date("H:i:s", mktime(0, 0, substr($srtData['start_time'],0,-3), 1, 1, 2000));
	$timeStart .= ",".substr($srtData['start_time'],-3);
	$timeEnd = date("H:i:s", mktime(0, 0, substr($srtData['end_time'],0,-3), 1, 1, 2000));
	$timeEnd .= ",".substr($srtData['end_time'],-3);
	fwrite($fp,$timeStart." --> ".$timeEnd."\r\n");
	fwrite($fp,$srtData['content']."\r\n\r\n");
	$c++;
}

echo "Ok.\r\n";
fclose($fp);

$c = 0;

while(!$c) {
	$viki_quality = promptUser("Seleccione la calidad del video (720p, 480p, 360p, 240p)","720p");

	foreach($viki_json['streams'] as $stream => $streamData) {
		if( $streamData['quality'] == $viki_quality ) {
			$c = 1;
		}
	}

	if($c) {
		echo "Descargando video ... \r\n";
		//system("wget ".$viki_json['streams'][0]['uri']);
	}
	else {
		echo "Calidad no disponible!\r\n";
	}
}

echo "El proximo video es: ".$viki_json['next_video']['title']." (".$viki_json['next_video']['media_id'].")\r\n";

?>
