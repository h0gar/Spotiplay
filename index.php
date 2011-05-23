<?php
set_time_limit(0);
error_reporting(0);
header ('Content-type: text/html; charset=utf-8');

if(!isset($_POST['u']))
	die('Usage: index.php?u=lastfmusername');

$username = $_POST['u'];

function remove_accents($string) {
	return str_replace( array('à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'), array('a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'), $string);
}

function wd_remove_accents($str, $charset='utf-8')
{
    $str = htmlentities($str, ENT_NOQUOTES, $charset);
    
    $str = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $str);
    $str = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    //$str = preg_replace('#\&[^;]+\;#', '', $str); // supprime les autres caractères
    
    return html_entity_decode($str);
}

function sortFunction($t1, $t2) {
	//print_r($t1);
	//$t1->album->availability->territories
	//exit();
	if(ereg('NO', $t1->album->availability->territories) || ereg('worldwide', $t1->album->availability->territories))
		if(ereg('NO', $t2->album->availability->territories) || ereg('worldwide', $t2->album->availability->territories)) {
			if ((float)$t1->popularity > (float)$t2->popularity)
				return -1;
			return 1;
		}
		else
			return -1;
	return 1;
}

$html = file_get_contents("http://www.lastfm.fr/user/".$username."/charts?subtype=tracks");
$html = str_replace("\n", '', $html);
$html = str_replace('&amp;', '', $html);
$html = preg_replace('/\<small\>[^<]*\<\/small\>/mis', '', $html);

preg_match_all('/\<td class="subjectCell" [^>]*\>(.+?)\<\/td\>/mis', $html, $out);
//$out = explode('</td>', $html);
//print_r($out);
foreach($out[1] as $k=>$v) {
	$out[1][$k] = preg_replace('/\<[^>]*\>/mis', '', $out[1][$k]);
	$out[1][$k] = trim(preg_replace('/–([^-]*)-.*$/mis', '–\\1', $out[1][$k]));
	$out[1][$k] = remove_accents($out[1][$k]);
}
	//print $out[$k];
//print_r($out[1]);
//exit();

$tracks = $out[1];
//$tracks = explode("\n",$list);
foreach($tracks as $v) {
	if(strpos($v, '–')) {
		list($artist, $track) = explode('–', $v);
	}
	else{
		echo 'Not found: '.$v.'<br/>'."\n";
		continue;
	}
	$url = 'http://ws.spotify.com/search/1/track?q=artist:%22'.urlencode(trim($artist)).'%22%20track:%22'.urlencode(trim($track)).'%22';
	//echo $url;
	$i=0;
	while($i++<10 && !($xml = @file_get_contents($url))) {usleep(500);}
	if($i<10) {
		$xml = preg_replace('/<tracks [^>]*>/', '<tracks>', $xml);
		$xml = preg_replace('/<opensearch[^>]*\/>/', '', $xml);
		$xml = preg_replace('/<opensearch[^>]*>[^<]*<[^>]*>/', '', $xml);
		$xml=simplexml_load_string($xml);
		//print_r($xml);
		$result = $xml->xpath('track');
		usort($result, 'sortFunction');
		if(isset($result[0]))
			echo $result[0]['href']."\t".$result[0]->artist->name. ' - '.$result[0]->name.'<br/>'."\n";
		else
			echo 'Not found: '.$v.'<br/>'."\n";
	}
	else
		echo 'Not found: '.$v.'<br/>'."\n";
	usleep(500);
}
?>