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

$viki_lang = promptUser("Language","es");
$viki_id = promptUser("Video ID");

do {
	echo "Obtaining information ... ";
	$viki_info = file_get_contents("http://www.viki.com/player/medias/".$viki_id."/info.json");

	if( $viki_info === FALSE ) {
		die("Error.\r\n");
	}

	$viki_json = json_decode($viki_info,TRUE);
	if( $viki_json === FALSE ) {
		die("Error.\r\n");
	}
	echo "Ok.\r\n";

	$viki_title = $viki_json['title'];
	$viki_description = $viki_json['description'];
	$viki_next_title = $viki_json['next_video']['title'];
	$viki_next_id = $viki_json['next_video']['media_id'];
	foreach($viki_json['streams'] as $stream => $streamData) {
		$viki_uris[$streamData['quality']] = $streamData['uri'];
	}

	unset($viki_info);
	unset($viki_json);

	echo "\r\nThe title: ".$viki_title."\r\n";
	echo "The description: ".$viki_description."\r\n\r\n";

	do {
		$qty = promptUser("Select quality of video:",implode(",",array_keys($viki_uris)));
	}
	while( !isset($viki_uris[$qty]) );

	echo "Descargando video ... \r\n";
	system("wget ".$viki_uris[$qty]);

	echo "\r\nDownloading subtitles ... ";
	$viki_ljson = file_get_contents("http://www.viki.com/subtitles/media/".$viki_id."/".$viki_lang.".json");

	if( $viki_ljson === FALSE ) {
		die("Error.\r\n");
	}

	echo "Ok.\r\n";
	echo "Converting JSON to STR filetype... ";

	$viki_array = json_decode($viki_ljson,TRUE);
	if( $viki_array === FALSE ) {
		die("Error.\r\n");
	}
	echo "Ok.\r\n";

	echo "Building srt file... ";
	$srtFile = explode("/",$viki_uris[$qty]);
	if( !($fp = fopen($srtFile[4].".srt", "a+")) ) {
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

	echo "The next chapter is: ".$viki_next_title."\r\n";
	$viki_id = $viki_next_id;
	$ans = promptUser("Do you want to download the next chapter too?","y");
	$ans = strtolower($ans);
}
while($ans=="y");
?>
