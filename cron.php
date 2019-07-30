<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use SimplePie\SimplePie;

include_once('conf/config.php');
require __DIR__ . '/vendor/autoload.php';

if (defined('LOGS_DAYS_TO_KEEP')) {
    $handler = new RotatingFileHandler(__DIR__ . '/logs/influx.log', LOGS_DAYS_TO_KEEP);
} else {
    $handler = new RotatingFileHandler(__DIR__ . '/logs/influx.log', 7);
}

$stream = new StreamHandler(__DIR__ . '/logs/synchronization.log', Logger::DEBUG);
$logger = new Logger('SyncLogger');
$logger->pushHandler($stream);
$logger->pushHandler($handler);
$logger->info('Sync started');

$router = new \Bramus\Router\Router();

function convertFileSize($bytes)
{
    if ($bytes < 1024) {
        return round(($bytes / 1024), 2) . ' o';
    } elseif (1024 < $bytes && $bytes < 1048576) {
        return round(($bytes / 1024), 2) . ' ko';
    } elseif (1048576 < $bytes && $bytes < 1073741824) {
        return round(($bytes / 1024) / 1024, 2) . ' Mo';
    } elseif (1073741824 < $bytes) {
        return round(($bytes / 1024) / 1024 / 1024, 2) . ' Go';
    }
}

function getEnclosureHtml($enclosure)
{
    $html = '';
    if ($enclosure != null && $enclosure->link != '') {
        $enclosureName = substr(
            $enclosure->link,
            strrpos($enclosure->link, '/') + 1,
            strlen($enclosure->link)
        );
        $enclosureArgs = strpos($enclosureName, '?');
        if ($enclosureArgs !== false)
            $enclosureName = substr($enclosureName, 0, $enclosureArgs);
        $enclosureFormat = isset($enclosure->handler)
            ? $enclosure->handler
            : substr($enclosureName, strrpos($enclosureName, '.') + 1);
        $html = '<div class="enclosure"><h1>Fichier média :</h1>';
        $enclosureType = $enclosure->get_type();
        if (strpos($enclosureType, 'image/') === 0) {
            $html .= '<img src="' . $enclosure->link . '" />';
        } elseif (strpos($enclosureType, 'audio/') === 0) {
            $html .= '<audio src="' . $enclosure->link . '" preload="none" controls>Audio not supported</audio>';
        } elseif (strpos($enclosureType, 'video/') === 0) {
            $html .= '<video src="' . $enclosure->link . '" preload="none" controls>Video not supported</video>';
        } else {
            $html .= '<a href="' . $enclosure->link . '"> ' . $enclosureName . '</a>';
        }
        $html .= ' <span>(Format ' . strtoupper($enclosureFormat) . ', ' . convertFileSize($enclosure->length) . ')</span></div>';
    }
    return $html;
}


$sp = new \SimplePie();
$sp->enable_cache(true);
$sp->set_useragent('Mozilla/5.0 (compatible; Exabot/3.0; +http://www.exabot.com/go/robot)');

$db = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_MDP, MYSQL_BDD);
$db->set_charset('utf8mb4');
$db->query('SET NAMES utf8mb4');

$query_feed = "select * from flux order by name";

$result_feed = $db->query($query_feed);

$start = microtime(true);
$currentDate = date('d/m/Y H:i:s');
$nbErrors = 0;
$nbOk = 0;
$nbTotal = 0;
$localTotal = 0; // somme de tous les temps locaux, pour chaque flux
$nbTotalEventsMaj = 0;
$nbTotalEventsIns = 0;
$syncId = time();

while ($row = $result_feed->fetch_array()) {

    $fluxName = $row['name'];
    $fluxUrl = $row['url'];
    $fluxId = $row['id'];

    $sp->set_feed_url($fluxUrl);
    $sp->handle_content_type();

    echo "\tParse flux: \t{$fluxName}\n";
    $logger->info("\tParse flux: \t{$fluxName}\n");
    echo "\t\tFlux id: \t$fluxId\n";
    $logger->info("\t\tFlux id: \t$fluxId\n");

    if (!$sp->init()) {
        $error = $sp->error;
        $lastSyncInError = 1;
        $nbErrors += 1;
        echo "\t\tFlux id: \t$sp->error()\n";
        $logger->error("\t\tError: \t$sp->error()\n");
        continue;
    }

    $linesUpdated = 0;
    $linesInserted = 0;

    foreach ($sp->get_items() as $item) {
        $guid = $item->get_id(true);

        $permalink = $item->get_permalink();
        $content = $db->real_escape_string($item->get_content());
        $title = $db->real_escape_string(mb_strimwidth($item->get_title(), 0, 250, "..."));
        $description = $db->real_escape_string(mb_strimwidth($item->get_description(), 0, 300, "..."));

        $link = $item->get_link();

        $enclosure = getEnclosureHtml($item->get_enclosure());


        $author = '';

        if ($creator = $item->get_author()) {
            $author = $creator->get_name();
        } elseif ($item->get_authors()) {
            foreach ($item->get_authors() as $creator) {
                $author .= $creator->get_name() . ',';
            }
        } else {
            $author = $link;
        }

        if (is_numeric($item->get_date())) {
            $pubdate = $item->get_date();
        } elseif ($item->get_date()) {
            $pubdate = strtotime($item->get_date());
        } else {
            $pubdate = time();
        }

        $insertOrUpdate = "INSERT INTO influx.items VALUES ('" . $guid . "','" . $title . "','" . $db->real_escape_string($author) . "','" . $content . $enclosure . "','" . $description . "','" . $permalink . "',1," . $fluxId . ",0," . $pubdate . ',' . time() . ',' . $syncId . ") ON DUPLICATE KEY UPDATE title = '" . $title . "', content = '" . $content . "'";

        $db->query($insertOrUpdate);
        $ret = $db->affected_rows;

        if ($ret == 1) {
            $linesInserted += 1;
            $nbTotalEventsIns += 1;
        } elseif ($ret == 2) {
            $linesUpdated += 1;
            $nbTotalEventsMaj += 1;
        }

        if ($db->errno) {
            echo "\t\tFailure: \t$db->error\n";
            $logger->info("\t\tFailure: \t$db->error\n");
            $logger->error($insertOrUpdate);
        }

    }

    $nbOk += 1;
    echo "\t\tLines updated: $linesUpdated\n";
    $logger->info("\t\tLines updated: $linesUpdated\n");
    echo "\t\tLines inserted: $linesInserted\n";
    $logger->info("\t\tLines inserted: $linesInserted\n");
}

$totalTime = microtime(true) - $start;

$totalTimeStr = number_format($totalTime, 3);
$currentDate = date('d/m/Y H:i:s');

echo "\t{$nbErrors}\t" . 'ERRORS' . "\n";
$logger->error("\t{$nbErrors}\t" . 'ERRORS' . "\n");
echo "\t{$nbOk}\t" . 'GOOD' . "\n";
$logger->info("\t{$nbOk}\t" . 'GOOD' . "\n");
echo "\t{$nbTotal}\t" . 'AT_TOTAL' . "\n";
$logger->info("\t{$nbTotal}\t" . 'AT_TOTAL' . "\n");
echo "\t$currentDate\n";
$logger->info("\t$currentDate\n");
echo "\tTotal Updated\t$nbTotalEventsMaj\n";
$logger->info("\tTotal Updated\t$nbTotalEventsMaj\n");
echo "\tTotal Inserted\t$nbTotalEventsIns\n";
$logger->info("\tTotal Inserted\t$nbTotalEventsIns\n");
echo "\t{$totalTimeStr}\t" . 'SECONDS' . "\n";
$logger->info("\t{$totalTimeStr}\t" . 'SECONDS' . "\n");