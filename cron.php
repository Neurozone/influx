<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SimplePie\SimplePie;

require __DIR__ . '/vendor/autoload.php';

$stream = new StreamHandler(__DIR__ . '/logs/synchronization.log', Logger::DEBUG);
$logger = new Logger('SyncLogger');
$logger->pushHandler($stream);
$logger->info('Sync started');

$router = new \Bramus\Router\Router();

function convertFileSize($bytes)
{
    if($bytes<1024){
        return round(($bytes / 1024), 2).' o';
    }elseif(1024<$bytes && $bytes<1048576){
        return round(($bytes / 1024), 2).' ko';
    }elseif(1048576<$bytes && $bytes<1073741824){
        return round(($bytes / 1024)/1024, 2).' Mo';
    }elseif(1073741824<$bytes){
        return round(($bytes / 1024)/1024/1024, 2).' Go';
    }
}

function getEnclosureHtml($enclosure) {
	$html = '';
        if($enclosure!=null && $enclosure->link!=''){
            $enclosureName = substr(
                $enclosure->link,
                strrpos($enclosure->link, '/')+1,
                strlen($enclosure->link)
            );
            $enclosureArgs = strpos($enclosureName, '?');
            if($enclosureArgs!==false)
                $enclosureName = substr($enclosureName,0,$enclosureArgs);
            $enclosureFormat = isset($enclosure->handler)
                ? $enclosure->handler
                : substr($enclosureName, strrpos($enclosureName,'.')+1);
            $html ='<div class="enclosure"><h1>Fichier média :</h1>';
            $enclosureType = $enclosure->get_type();
            if (strpos($enclosureType, 'image/') === 0) {
                $html .= '<img src="' . $enclosure->link . '" />';
            } elseif (strpos($enclosureType, 'audio/') === 0) {
                $html .= '<audio src="' . $enclosure->link . '" preload="none" controls>Audio not supported</audio>';
            } elseif (strpos($enclosureType, 'video/') === 0) {
                $html .= '<video src="' . $enclosure->link . '" preload="none" controls>Video not supported</video>';
            } else {
                $html .= '<a href="'.$enclosure->link.'"> '.$enclosureName.'</a>';
            }
            $html .= ' <span>(Format '.strtoupper($enclosureFormat).', '.convertFileSize($enclosure->length).')</span></div>';
        }
        return $html;
    }

include_once('conf/config.php');

$sp = new \SimplePie();
$sp->enable_cache(true);
$sp->set_useragent('Mozilla/5.0 (compatible; Exabot/3.0; +http://www.exabot.com/go/robot)');

$db = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_MDP, MYSQL_BDD);
$db->set_charset('utf8mb4');
$db->query('SET NAMES utf8mb4');

$query_feed = "select * from flux order by name";

$result_feed = $db->query($query_feed);

$currentDate = date('d/m/Y H:i:s');
$nbErrors = 0;
$nbOk = 0;
$nbTotal = 0;
$localTotal = 0; // somme de tous les temps locaux, pour chaque flux
$nbTotalEvents = 0;
$syncId = time();

while ($row = $result_feed->fetch_array()) {

    $fluxName = $row['name'];
    $fluxUrl = $row['url'];
    $fluxId = $row['id'];

    $sp->set_feed_url($fluxUrl);
    $sp->handle_content_type();

    $success = $sp->init();


    $logger->info($fluxName);

    echo "\tParse flux: \t{$fluxName}\n";
    $logger->info("\tParse flux: \t{$fluxName}\n");
    echo "\t\tFlux id: \t$fluxId\n";
    $logger->info("\t\tFlux id: \t$fluxId\n");
    if($sp->error())
    {
        echo "\t\tFlux id: \t$sp->error()\n";
        $logger->error("\t\tFlux id: \t$sp->error()\n");
    }

    foreach($sp->get_items() as $item)
    {
       $guid = $item->get_id(true);

        $permalink = $item->get_permalink();
        $content = $db->real_escape_string($item->get_content());
        $title = $db->real_escape_string(mb_strimwidth($item->get_title(), 0, 250, "..."));
        $description =  $db->real_escape_string(mb_strimwidth($item->get_description(), 0, 300, "..."));

        $link = $item->get_link();

        $enclosure = getEnclosureHtml($item->get_enclosure());


        $author = '';

        if ($creator = $item->get_author())
        {
            $author =  $creator->get_name();
        }
        elseif($item->get_authors()){
            foreach ($item->get_authors() as $creator)
            {
                $author .=   $creator->get_name() . ',';
            }
        }
        else{
            $author = $link;
        }

        if(is_numeric($item->get_date()))
        {
            $pubdate = $item->get_date();
        }
        elseif($item->get_date())
        {
            $pubdate = strtotime($item->get_date());
        }
        else{
            $pubdate = time();
        }

        $insertOrUpdate = "INSERT INTO influx.items VALUES ('" . $guid . "','" . $title . "','" . $db->real_escape_string($author) . "','" . $content . $enclosure . "','" . $description . "','" . $permalink . "',1," . $fluxId . ",0," . $pubdate . ',' . time() . ',' . $syncId . ") ON DUPLICATE KEY UPDATE title = '" . $title . "', content = '" . $content ."'";

        $db->query($insertOrUpdate);


        echo "\t\tLine changed: \t$db->affected_rows\n";
        $logger->info("\t\tLine changed: \t$db->affected_rows\n");
        if ($db->errno) {
            echo "\t\tFailure: \t$db->error\n";
            $logger->info("\t\tFailure: \t$db->error\n");
            $logger->error($insertOrUpdate);


        }

    }



}
/*
echo "\t{$nbErrors}\t" . _t('ERRORS') . "\n";
echo "\t{$nbOk}\t" . _t('GOOD') . "\n";
echo "\t{$nbTotal}\t" . _t('AT_TOTAL') . "\n";
echo "\t$currentDate\n";
echo "\t$nbTotalEvents\n";
echo "\t{$totalTimeStr}\t" . _t('SECONDS') . "\n";

    //$feedManager->synchronize($feeds, $syncTypeStr, $commandLine, $configurationManager, $start);

    $currentDate = date('d/m/Y H:i:s');
    echo "{$syncTypeStr}\t{$currentDate}\n";

    $maxEvents = $configurationManager->get('feedMaxEvents');
    $nbErrors = 0;
    $nbOk = 0;
    $nbTotal = 0;
    $localTotal = 0; // somme de tous les temps locaux, pour chaque flux
    $nbTotalEvents = 0;
    $syncId = time();
    $enableCache = ($configurationManager->get('synchronisationEnableCache') == '') ? 0 : $configurationManager->get('synchronisationEnableCache');
    $forceFeed = ($configurationManager->get('synchronisationForceFeed') == '') ? 0 : $configurationManager->get('synchronisationForceFeed');
    foreach ($feeds as $feed) {
        $nbEvents = 0;
        $nbTotal++;
        $startLocal = microtime(true);
        $parseOk = $feed->parse($syncId, $nbEvents, $enableCache, $forceFeed);
        $parseTime = microtime(true) - $startLocal;
        $localTotal += $parseTime;
        $parseTimeStr = number_format($parseTime, 3);
        if ($parseOk) { // It's ok
            $errors = array();
            $nbTotalEvents += $nbEvents;
            $nbOk++;
        } else {
            // tableau au cas où il arrive plusieurs erreurs
            $errors = array($feed->getError());
            $nbErrors++;
        }
        $feedName = truncate($feed->getName(), 30);


        $feedUrl = $feed->getUrl();
        $feedUrlTxt = truncate($feedUrl, 30);

        echo date('d/m/Y H:i:s') . "\t" . $parseTimeStr . "\t";
        echo "{$feedName}\t{$feedUrlTxt}\n";

        foreach ($errors as $error) {
            if ($commandLine)
                echo "$error\n";
            else
                echo "<dd>$error</dd>\n";
        }

    }
    assert('$nbTotal==$nbOk+$nbErrors');
    $totalTime = microtime(true) - $start;
    assert('$totalTime>=$localTotal');
    $totalTimeStr = number_format($totalTime, 3);
    $currentDate = date('d/m/Y H:i:s');

    echo "\t{$nbErrors}\t" . _t('ERRORS') . "\n";
    echo "\t{$nbOk}\t" . _t('GOOD') . "\n";
    echo "\t{$nbTotal}\t" . _t('AT_TOTAL') . "\n";
    echo "\t$currentDate\n";
    echo "\t$nbTotalEvents\n";
    echo "\t{$totalTimeStr}\t" . _t('SECONDS') . "\n";
*/




