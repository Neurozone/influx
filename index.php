<?php

require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use Sinergi\BrowserDetector\Language;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Influx\Flux;
use Influx\Items;
use Influx\User;
use Influx\Category;
use Influx\Opml;
use Influx\Configuration;
use Influx\Statistics;


$stream = new StreamHandler(__DIR__ . '/logs/influx.log', Logger::DEBUG);
$logger = new Logger('influxLogger');
$logger->pushHandler($stream);

$templatePath = __DIR__ . '/templates/influx';

$loader = new \Twig\Loader\FilesystemLoader($templatePath);
$twig = new \Twig\Environment($loader, ['cache' => __DIR__ . '/cache' , 'debug' => true,]);

$twig->addExtension(new \Twig\Extension\DebugExtension());

// Create Router instance
$router = new \Bramus\Router\Router();

session_start();

if (file_exists('conf/config.php')) {
    require_once('conf/config.php');

    /* ---------------------------------------------------------------- */
    // Database
    /* ---------------------------------------------------------------- */

    $db = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_MDP, MYSQL_BDD);
    $db->set_charset('utf8mb4');
    $db->query('SET NAMES utf8mb4');

    $conf = new Configuration($db);
    $config = $conf->getAll();

    $fluxObject = new Flux($db, $logger);
    $itemsObject = new Items($db, $logger);
    $userObject = new User($db,$logger);
    $categoryObject = new Category($db,$logger);
    $opmlObject = new Opml($db,$logger);

    $synchronisationCode = $config['synchronisationCode'];

    mb_internal_encoding('UTF-8'); // UTF8 pour fonctions mb_*
    $start = microtime(true);

    /* ---------------------------------------------------------------- */
    // Timezone
    /* ---------------------------------------------------------------- */

    $timezone_default = 'Europe/Paris';
    date_default_timezone_set($timezone_default);

    $paginationScale = $config['paginationScale'];
    if (empty($paginationScale)) {

        $paginationScale = 5;
    }

    $scroll = false;
    $unreadEventsForFolder = 0;
    $hightlighted = 0;

    $page = 1;

    /*
    $results = $db->query('SELECT COUNT(i.guid),f.folder FROM items i INNER JOIN flux f ON i.feed = f.id WHERE i.unread =1 GROUP BY f.folder');
    while ($item = $results->fetch_array()) {
        $events[$item[1]] = intval($item[0]);
    }

    $allEvents = $events;

    $results = $db->query("SELECT f.name AS name, f.id AS id, f.url AS url, c.id AS folder 
FROM flux f 
    INNER JOIN categories c ON f.folder = c.id 
ORDER BY f.name");

    if ($results != false) {
        while ($item = $results->fetch_array()) {
            $name = $item['name'];
            $feedsIdMap[$item['id']]['name'] = $name;


            $feedsFolderMap[$item['folder']][$item['id']]['id'] = $item['id'];
            $feedsFolderMap[$item['folder']][$item['id']]['name'] = $name;
            $feedsFolderMap[$item['folder']][$item['id']]['url'] = $item['url'];

        }
    }
    $feeds['folderMap'] = $feedsFolderMap;
    $feeds['idMap'] = $feedsIdMap;

    $allFeeds = $feeds;
    $allFeedsPerFolder = $allFeeds['folderMap'];
    */



} else {
    if (!isset($_SESSION['install'])){
        $_SESSION['install'] = true;
        header('location: /install');
        exit();
    }

}

function getClientIP()
{
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        return $_SERVER["HTTP_X_FORWARDED_FOR"];
    } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
        return $_SERVER["REMOTE_ADDR"];
    } else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }

    return '';
}

/* ---------------------------------------------------------------- */
// Reverse proxy
/* ---------------------------------------------------------------- */

// @todo

// Use X-Forwarded-For HTTP Header to Get Visitor's Real IP Address

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $http_x_headers = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

    $_SERVER['REMOTE_ADDR'] = $http_x_headers[0];
}

/* ---------------------------------------------------------------- */
// i18n
/* ---------------------------------------------------------------- */

$language = new Language();

if(isset($config['language'])) {
    if ($language->getLanguage() == $config['language'] && is_file('locales/' . $config['language'] . '.json')) {
        $_SESSION['language'] = $language->getLanguage();
        $l_trans = json_decode(file_get_contents('templates/influx/locales/' . $config['language'] . '.json'), true);
    } elseif ($language->getLanguage() != $config['language'] && is_file('locales/' . $config['language'] . '.json')) {
        $_SESSION['language'] = $language->getLanguage();
        $l_trans = json_decode(file_get_contents('templates/influx/locales/' . $config['language'] . '.json'), true);
    } elseif (!is_file('locales/' . $config['language'] . '.json')) {
        $_SESSION['language'] = 'en';
        $l_trans = json_decode(file_get_contents('templates/influx/locales/' . $_SESSION['language'] . '.json'), true);
    }
}
else{
    $_SESSION['language'] = 'en';
    $l_trans = json_decode(file_get_contents('templates/influx/locales/' . $_SESSION['language'] . '.json'), true);
}

$trans = $l_trans;

/* ---------------------------------------------------------------- */
// Cookie
/* ---------------------------------------------------------------- */

$cookiedir = '';
if (dirname($_SERVER['SCRIPT_NAME']) != '/') {
    $cookiedir = dirname($_SERVER["SCRIPT_NAME"]) . '/';
}

/* ---------------------------------------------------------------- */
// Route: Before for logging
/* ---------------------------------------------------------------- */

$router->before('GET|POST|PUT|DELETE|PATCH|OPTIONS', '*', function () use ($logger) {
    $logger->info($_SERVER['REQUEST_URI']);
    $logger->info(getClientIP());
    $logger->info("before");

    if (!isset($_SESSION['user']) && (!isset($_SESSION['install']))) {
        header('location: /login');
    }

});

/* ---------------------------------------------------------------- */
// Route: / (GET)
/* ---------------------------------------------------------------- */
$router->get('/', function () use (
    $twig,
    $logger,
    $scroll,
    $config,
    $db,
    $trans,
    $itemsObject,
    $categoryObject
) {

    $action = '';
    $numberOfItem = $itemsObject->countAllUnreadItem();
    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $pages = ($config['articlePerPages'] > 0 ? ceil($numberOfItem / $config['articlePerPages']) : 1);
    $startArticle = ($page - 1) * $config['articlePerPages'];

    $offset = ($page - 1) * 25 ; //$config['articlePerPages'];
    $row_count = 25; //$config['articlePerPages'];


    echo $twig->render('index.twig',
        [
            'action' => $action,
            //'allEvents' => $allEvents,
            //'allFeedsPerFolder' => $allFeedsPerFolder,
            'config' => $config,
            'events' => $itemsObject->loadAllUnreadItem($offset, $row_count),
            //'folders' => $folders,
            'folders' => $categoryObject->getFeedsByCategories(),
            'numberOfItem' => $numberOfItem,
            'page' => $page,
            'pages' => $pages,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll,
            'trans' => $trans
        ]
    );

});

/* ---------------------------------------------------------------- */
// Route: /login
/* ---------------------------------------------------------------- */

$router->get('/login', function () use ($twig) {
    echo $twig->render('login.twig');
});

/* ---------------------------------------------------------------- */
// Route: /login (POST)
/* ---------------------------------------------------------------- */

$router->post('/login', function () use ($db, $config, $logger) {

    $salt = $config['cryptographicSalt'];

    if ($stmt = $db->prepare("select id,login,password from user where login = ? and password = ?")) {
        $stmt->bind_param("ss", $_POST['login'], sha1($_POST['password'] . $salt));
        /* execute query */
        $stmt->execute();

        /* instead of bind_result: */
        $result = $stmt->get_result();

        while ($row = $result->fetch_array()) {
            $_SESSION['user'] = $row['login'];
            $_SESSION['userid'] = $row['id'];
            $user = $row['login'];
        }
    }

    if (!isset($_SESSION['user'])) {
        $logger->info("wrong login for '" . $_POST['login'] . "'");
        header('location: /login');
    } else {
        $_SESSION['user'] = $user;
        if (isset($_POST['rememberMe'])) {
            setcookie('InfluxChocolateCookie', sha1($_POST['password'] . $_POST['login']), time() + 31536000);
        }
        header('location: /');
    }
    exit();

});

/* ---------------------------------------------------------------- */
// Route: /password/recover (POST)
/* ---------------------------------------------------------------- */

$router->post('/password/recover', function () use ($db, $config, $logger) {

    $token = bin2hex(random_bytes(50));


    $salt = $config['cryptographicSalt'];

    if ($stmt = $db->prepare("select id,login,password from user where login = ? and password = ?")) {
        $stmt->bind_param("ss", $_POST['login'], sha1($_POST['password'] . $salt));
        /* execute query */
        $stmt->execute();

        /* instead of bind_result: */
        $result = $stmt->get_result();

        while ($row = $result->fetch_array()) {
            $_SESSION['user'] = $row['login'];
            $_SESSION['userid'] = $row['id'];
            $user = $row['login'];
        }
    }


    if ($user == false) {
        $logger->info("wrong login for '" . $_POST['login'] . "'");
        header('location: /login');
    } else {
        $_SESSION['currentUser'] = $user;
        if (isset($_POST['rememberMe'])) {
            setcookie('InfluxChocolateCookie', sha1($_POST['password'] . $_POST['login']), time() + 31536000);
        }
        header('location: /');
    }


    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = 2;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = SMTP_HOSR;  // Specify main and backup SMTP servers
        $mail->SMTPAuth   = SMTP_AUTH;                                   // Enable SMTP authentication
        $mail->Username   = SMTP_LOGIN;                     // SMTP username
        $mail->Password   = SMTP_PASSWORD;                               // SMTP password
        $mail->SMTPSecure = SMTP_SECURE;                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = SMTP_PORT;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom('rss@neurozone.fr', 'no-reply@neurozone.fr');
        $mail->addAddress('joe@example.net', 'Joe User');

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Reset your password on InFlux';
        $mail->Body    = "Hi there, click on this <a href=\"new_password.php?token=" . $token . "\">link</a> to reset your password on our site";
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }


});

/* ---------------------------------------------------------------- */
// Route: /logout (GET)
/* ---------------------------------------------------------------- */

$router->get('/logout', function () {

    setcookie('InfluxChocolateCookie', '', -1);
    $_SESSION = array();
    session_unset();
    session_destroy();
    header('location: /login');

});

// @TODO: à mettre en place

/* ---------------------------------------------------------------- */
// Route: /update (GET)
/* ---------------------------------------------------------------- */

$router->get('/update', function () {

    setcookie('InfluxChocolateCookie', '', -1);
    $_SESSION = array();
    session_unset();
    session_destroy();
    header('location: /');

});

/* ---------------------------------------------------------------- */
// Route: /favorites (GET)
/* ---------------------------------------------------------------- */

$router->get('/favorites', function () use (
    $twig, $logger,
    $scroll,
    //$eventManager,
    $unreadEventsForFolder,
    //$target,
    //$folders,
    //$allEvents,
    //$unread,
    //$allFeedsPerFolder,
    $config,
    $db,
    $categoryObject
) {

    $resultsNbFavorites = $db->query('SELECT count(*) as nb_items from items where favorite = 1');
    $numberOfItem = 0;

    while ($rows = $resultsNbFavorites->fetch_array()) {
        $numberOfItem = $rows['nb_items'];
    }
    //$numberOfItem = $eventManager->rowCount(array('favorite' => 1));
    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $pages = ceil($numberOfItem / $config['articlePerPages']);
    $startArticle = ($page - 1) * $config['articlePerPages'];
    //$events = $eventManager->loadAllOnlyColumn($target, array('favorite' => 1), 'pubdate DESC', $startArticle . ',' . $articlePerPages);

    $results = $db->query('SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM items le inner join flux lf on lf.id = le.feed where favorite = 1 ORDER BY pubdate desc,unread desc LIMIT  ' . ($page - 1) * $config['articlePerPages'] . ',' . $config['articlePerPages']);

    while ($rows = $results->fetch_array()) {

        $events[] = array(
            'id' => $rows['guid'],
            'guid' => $rows['guid'],
            'title' => $rows['title'],
            'creator' => $rows['creator'],
            'content' => $rows['content'],
            'description' => $rows['description'],
            'link' => $rows['link'],
            'unread' => $rows['unread'],
            'feed' => $rows['feed'],
            'favorite' => $rows['favorite'],
            'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
            'syncId' => $rows['syncId'],
            'feed_name' => $rows['feed_name'],
        );

    }

    echo $twig->render('index.twig',
        [
            //'action' => $action,
            //'allEvents' => $allEvents,
            //'allFeedsPerFolder' => $allFeedsPerFolder,
            //'articlePerPages' => $articlePerPages,
            //'eventManager' => $eventManager,
            'events' => $events,
            //'feedState' => $feedState,
            'folders' => $categoryObject->getFeedsByCategories(),
            //'functions' => New Functions(),
            //'nextPages' => $nextPages,
            'numberOfItem' => $numberOfItem,
            'page' => $page,
            'pages' => $pages,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll

        ]
    );


});

/* ---------------------------------------------------------------- */
// Route: /article (GET)
/* ---------------------------------------------------------------- */

// @todo

$router->mount('/article', function () use ($router, $twig, $db, $logger, $trans, $config) {

    /* ---------------------------------------------------------------- */
    // Route: /article (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/', function () use ($twig, $db, $logger, $trans, $config) {

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/favorite (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/favorites', function () use ($twig, $db, $logger, $trans, $config) {

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/unread (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/unread', function () use ($twig, $db, $logger, $trans, $config) {

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/flux/ (GET)
    /* ---------------------------------------------------------------- */

    $router->post('/flux', function () use ($twig, $db, $logger, $config) {

        $scroll = $_POST['scroll'];
        $nblus = isset($_POST['nblus']) ? $_POST['nblus'] : 0;
        $hightlighted = $_POST['hightlighted'];
        $action = $_POST['action'];
        $folder = $_POST['folder'];
        $feed = (int)$_POST['feed'];

        $nblus = isset($_POST['nblus']) ? $_POST['nblus'] : 0;

        $articleConf['startArticle'] = ($scroll * 50) - $nblus;

        //$currentFeed = $feedManager->getById($_['feed']);
        //$allowedOrder = array('date'=>'pubdate DESC','older'=>'pubdate','unread'=>'unread DESC,pubdate DESC');
        //$order = (isset($_['order'])?$allowedOrder[$_['order']]:$allowedOrder['unread']);
        //$events = $currentFeed->getEvents($articleConf['startArticle'],$articleConf['articlePerPages'],$order,$target,$filter);

        $logger->info($articleConf['startArticle']);
        $logger->info($config['articlePerPages']);

        $offset = $articleConf['startArticle'];
        $rowcount = $articleConf['startArticle'] + $config['articlePerPages'];

        if ($articleConf['startArticle'] < 0) {
            $articleConf['startArticle'] = 0;
        }

        $q = 'SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
                FROM items le 
                    inner join flux lf on lf.id = le.feed 
                where le.feed = ' . $feed . ' 
                ORDER BY pubdate desc,unread desc 
                LIMIT ' . $offset .
            ',' . $rowcount;

        $logger->info($q);

        $results = $db->query($q);

        while ($rows = $results->fetch_array()) {

            $events[] = array(
                'id' => $rows['guid'],
                'guid' => $rows['guid'],
                'title' => $rows['title'],
                'creator' => $rows['creator'],
                'content' => $rows['content'],
                'description' => $rows['description'],
                'link' => $rows['link'],
                'unread' => $rows['unread'],
                'feed' => $rows['feed'],
                'favorite' => $rows['favorite'],
                'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
                'syncId' => $rows['syncId'],
                'feed_name' => $rows['feed_name'],
            );

        }

        echo $twig->render('article.twig',
            [
                'events' => $events,
                'scroll' => $scroll,
            ]
        );

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/folder/{id} (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/folder/{id}', function () use ($twig, $db, $logger, $trans, $config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        header('location: /settings/manage');

    });

});

/* ---------------------------------------------------------------- */
// Route: /settings (GET)
/* ---------------------------------------------------------------- */

$router->mount('/settings', function () use ($router, $twig, $trans, $logger, $config, $db, $cookiedir, $categoryObject, $fluxObject,$opmlObject) {

    $router->get('/', function () use ($twig, $cookiedir) {

        header('location: /settings/manage');

    });

    $router->get('/settings/user', function () use ($twig, $cookiedir) {

        header('location: /settings/manage');

    });



    /* ---------------------------------------------------------------- */
    // Route: /settings/synchronize (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/synchronize', function ($option) use ($twig, $trans, $logger, $config, $cookiedir) {

        if (isset($myUser) && $myUser != false) {
            $syncCode = $config['synchronisationCode'];
            $syncGradCount = $config['syncGradCount'];
            if (!(isset($_['code']) && $conf['synchronisationCode'] != null && $_GET['code'] == $conf['synchronisationCode'])) {
                die(_t('YOU_MUST_BE_CONNECTED_ACTION'));
            }
            Functions::triggerDirectOutput();


            echo '<html>
                <head>
                <link rel="stylesheet" href="./templates/influx/css/style.css">
                <meta name="referrer" content="no-referrer" />
                </head>
                <body>
                <div class="sync">';

            $synchronisationType = $conf['synchronisationType'];

            $synchronisationCustom = array();
            Plugin::callHook("action_before_synchronisationtype", array(&$synchronisationCustom, &$synchronisationType, &$commandLine, $configurationManager, $start));
            if (isset($synchronisationCustom['type'])) {
                $feeds = $synchronisationCustom['feeds'];
                $syncTypeStr = _t('SYNCHRONISATION_TYPE') . ' : ' . _t($synchronisationCustom['type']);
            } elseif ('graduate' == $synchronisationType) {
                // sélectionne les 10 plus vieux flux
                //$feeds = $feedManager->loadAll(null, 'lastupdate', $syncGradCount);
                $syncTypeStr = _t('SYNCHRONISATION_TYPE') . ' : ' . _t('GRADUATE_SYNCHRONISATION');
            } else {
                // sélectionne tous les flux, triés par le nom
                //$feeds = $feedManager->populate('name');
                $syncTypeStr = _t('SYNCHRONISATION_TYPE') . ' : ' . _t('FULL_SYNCHRONISATION');
            }

            if (!isset($synchronisationCustom['no_normal_synchronize'])) {
                // $feedManager->synchronize($feeds, $syncTypeStr, $commandLine, $configurationManager, $start);
            }
        } else {
            echo _t('YOU_MUST_BE_CONNECTED_ACTION');
        }

    });

    /* ---------------------------------------------------------------- */
    // Route: /statistics (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/statistics', function () use ($twig, $trans, $logger, $config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        echo '
	<section id="leedStatslBloc" class="leedStatslBloc" style="display:none;">
		<h2>' . _t('P_LEEDSTATS_TITLE') . '</h2>

		<section class="preferenceBloc">
		<h3>' . _t('P_LEEDSTATS_RESUME') . '</h3>
	';

        //Nombre global d'article lus / non lus / total / favoris
        $requete = 'SELECT
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'flux`) as nbFeed,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` WHERE unread = 1) as nbUnread,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` WHERE unread = 0) as nbRead,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items`) as nbTotal,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` WHERE favorite = 1) as nbFavorite
                ';
        $query = $mysqli->customQuery($requete);
        if ($query != null) {
            echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBFEED') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART_NONLU') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART_LU') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBFAV') . '</th>
        ';
            while ($data = $query->fetch_array()) {
                echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">' . $data['nbFeed'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbTotal'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbUnread'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbRead'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbFavorite'] . '</td>
                </tr>
            ';
            }
            echo '</table>
            </div>';
        }
        echo '
            <h3>' . _t('P_LEEDSTATS_NBART_BY_FEED_TITLE') . '</h3>

    ';
        //Nombre global d'article lus / non lus / total / favoris
        $requete = 'SELECT name, count(1) as nbTotal,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.unread=1 and le1.feed = le2.feed) as nbUnread,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.unread=0 and le1.feed = le2.feed) as nbRead,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.favorite=1 and le1.feed = le2.feed) as nbFavorite
                FROM `' . MYSQL_PREFIX . 'flux` lf1
                INNER JOIN `' . MYSQL_PREFIX . 'items` le1 on le1.feed = lf1.id
                GROUP BY name
                ORDER BY name
                ';
        $query = $mysqli->customQuery($requete);
        if ($query != null) {
            echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_FEED') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART_NONLU') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART_LU') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBFAV') . '</th>
        ';
            while ($data = $query->fetch_array()) {
                echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">' . short_name($data['name'], 32) . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbTotal'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbUnread'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbRead'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbFavorite'] . '</td>
                </tr>
            ';
            }
            echo '</table>
            </div>';
        }

        echo '
            <h3>' . _t('P_LEEDSTATS_LASTPUB_BY_FEED_TITLE') . '</h3>

    ';

        $requete = 'select lf.name, FROM_UNIXTIME(max(le.pubdate)) last_published from flux lf inner join items le on lf.id = le.feed group by lf.name order by 2';

        $query = $mysqli->customQuery($requete);
        if ($query != null) {
            echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_FEED') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_LASTPUB') . '</th>
        ';
            while ($data = $query->fetch_array()) {
                echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">' . short_name($data['name'], 32) . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['last_published'] . '</td>
                </tr>
            ';
            }
            echo '</table>
            </div>';
        }

        echo '
        </section>
	</section>
	';

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/feed/add (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/feed/add', function () use ($twig, $trans, $logger, $config, $fluxObject) {


        $cat = isset($_POST['newUrlCategory']) ? $_POST['newUrlCategory'] : 1;

        $sp = new SimplePie();

        $fluxObject->setUrl($_POST['newUrl']);

        if ($fluxObject->notRegistered()) {

            //$fluxObject->getInfos();
            $fluxObject->setFolder((isset($_POST['newUrlCategory']) ? $_POST['newUrlCategory'] : 1));
            $fluxObject->add($sp);

        } else {

            $logger->info($trans['FEED_ALREADY_STORED']);
        }
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/feed/remove/{id} (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/feed/remove/{id}', function ($id) use ($twig, $trans, $logger, $config,$fluxObject) {


        $fluxObject->setId($id);
        $logger->info($fluxObject->getId($id));
        $fluxObject->remove();
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/feed/rename (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/feed/rename', function () use ($logger, $fluxObject) {

        // data:{id:feed,name:feedNameValue,url:feedUrlValue}

        $fluxObject->setId($_POST['id']);
        $fluxObject->setName($_POST['name']);
        $fluxObject->setUrl($_POST['url']);

        return $fluxObject->rename();

    });

    $router->get('/feed/folder/{id}', function ($id) use ($twig, $trans, $logger, $config, $db) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }


        if (isset($id)) {
            $feedManager->change(array('name' => $_['name'], 'url' => Functions::clean_url($_['url'])), array('id' => $_['id']));
        }
        header('location: /settings');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/add (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/category/add', function () use ($twig, $db, $logger, $trans, $config,$categoryObject) {

        $name = $_POST['categoryName'];
        $categoryObject->setName($name);
        if(isset($_POST['categoryName']) && !$categoryObject->exist()) {

            $categoryObject->add();
        }
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/remove/{id} (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/category/remove/{id}', function ($id) use ($twig, $db, $logger, $trans, $config) {



        if (isset($id) && is_numeric($id) && $id > 0) {
            //$eventManager->customQuery('DELETE FROM `' . MYSQL_PREFIX . 'items` WHERE `' . MYSQL_PREFIX . 'event`.`feed` in (SELECT `' . MYSQL_PREFIX . 'feed`.`id` FROM `' . MYSQL_PREFIX . 'feed` WHERE `' . MYSQL_PREFIX . 'feed`.`folder` =\'' . intval($_['id']) . '\') ;');
            //$feedManager->delete(array('folder' => $id));
            //$folderManager->delete(array('id' => $id));
        }
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/rename (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/category/rename', function () use ($twig, $db, $logger, $trans, $config,$categoryObject) {

        $id = $_POST['id'];
        $name = $_POST['name'];
        $categoryObject->setId($id);
        $categoryObject->setName($name);

        $logger->info(" avant le if rename");

        if(isset($_POST['id']) && $categoryObject->exist()) {

            $logger->info(" on rentre dans le if rename");
            $categoryObject->rename();
        }
        header('location: /settings/manage');

    });

    $router->get('/feeds/export', function () use ($twig, $db, $logger, $trans, $config, $opmlObject) {

        header('Content-Disposition: attachment;filename=export_opml.opml');
        header('Content-Type: text/xml');

        echo $opmlObject->export();

    });

    $router->get('/feeds/import', function () use ($twig, $db, $logger, $trans, $config) {



        /*
         * // On ne devrait pas mettre de style ici.
        echo "<html>
            <style>
                a {
                    color:#F16529;
                }

                html,body{
                        font-family:Verdana;
                        font-size: 11px;
                }
                .error{
                        background-color:#C94141;
                        color:#ffffff;
                        padding:5px;
                        border-radius:5px;
                        margin:10px 0px 10px 0px;
                        box-shadow: 0 0 3px 0 #810000;
                    }
                .error a{
                        color:#ffffff;
                }
                </style>
            </style><body>
\n";
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (!isset($_POST['importButton'])) break;
        $opml = new Opml();
        echo "<h3>" . _t('IMPORT') . "</h3><p>" . _t('PENDING') . "</p>\n";
        try {
            $errorOutput = $opml->import($_FILES['newImport']['tmp_name']);
        } catch (Exception $e) {
            $errorOutput = array($e->getMessage());
        }
        if (empty($errorOutput)) {
            echo "<p>" . _t('IMPORT_NO_PROBLEM') . "</p>\n";
        } else {
            echo "<div class='error'>" . _t('IMPORT_ERROR') . "\n";
            foreach ($errorOutput as $line) {
                echo "<p>$line</p>\n";
            }
            echo "</div>";
        }
        if (!empty($opml->alreadyKnowns)) {
            echo "<h3>" . _t('IMPORT_FEED_ALREADY_KNOWN') . " : </h3>\n<ul>\n";
            foreach ($opml->alreadyKnowns as $alreadyKnown) {
                foreach ($alreadyKnown as &$elt) $elt = htmlspecialchars($elt);
                $text = Functions::truncate($alreadyKnown->feedName, 60);
                echo "<li><a target='_parent' href='{$alreadyKnown->xmlUrl}'>"
                    . "{$text}</a></li>\n";
            }
            echo "</ul>\n";
        }
        $syncLink = "action.php?action=synchronize&format=html";
        echo "<p>";
        echo "<a href='$syncLink' style='text-decoration:none;font-size:3em'>"
            . "↺</a>";
        echo "<a href='$syncLink'>" . _t('CLIC_HERE_SYNC_IMPORT') . "</a>";
        echo "<p></body></html>\n";
        break;
         *
         */

        echo $twig->render('settings.twig',
            [
                'action' => 'folder',
                'section' => 'feeds/import',
                'trans' => $trans,
                'otpEnabled' => false,
                'currentTheme' => $config['theme'],
                'config' => $config
            ]
        );

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/{option} (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/{option}', function ($option) use ($twig, $trans, $logger, $config, $cookiedir, $db, $categoryObject) {

        //$serviceUrl', rtrim($_SERVER['HTTP_HOST'] . $cookiedir, '/'));

        // gestion des thèmes
        $themesDir = 'templates/';
        $dirs = scandir($themesDir);
        foreach ($dirs as $dir) {
            if (is_dir($themesDir . $dir) && !in_array($dir, array(".", ".."))) {
                $themeList[] = $dir;
            }
        }
        sort($themeList);

        $results = $db->query('SELECT * FROM categories c ORDER BY name ');

        /*
        while ($rows = $results->fetch_array()) {
            $folders['id'] = $rows['id'];
        }*/

        $resultsFlux = $db->query('SELECT * FROM flux f ORDER BY name ');
        while ($rows = $resultsFlux->fetch_array()) {
            $feeds['id'] = $rows['id'];
        }

        $logger->info('Section: ' . $option);

        echo $twig->render('settings.twig',
            [
                'action' => 'folder',
                'section' => $option,
                'trans' => $trans,
                'themeList' => $themeList,
                'otpEnabled' => false,
                'currentTheme' => $config['theme'],
                'folders' => $categoryObject->getFeedsByCategories(),
                'feeds' => $feeds,
                'config' => $config,
                'user' => $_SESSION['user']
            ]
        );

    });

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /action/readAll (GET)
/* ---------------------------------------------------------------- */


$router->get('/action/readAll/{id}', function ($id) use ($twig, $db,$logger,$trans,$config, $fluxObject) {

    $fluxObject->setId($id);
    $fluxObject->markAllRead();


    header('location: /');


});

/* ---------------------------------------------------------------- */
// Route: /action/readContent/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/action/readContent/{id}', function ($id) use ($twig, $db, $logger, $trans, $config) {

    $result = $db->query("update items set unread = 0 where guid = '" . $id . "'");

});

/* ---------------------------------------------------------------- */
// Route: /action/unreadContent/{id}(GET)
/* ---------------------------------------------------------------- */

$router->get('/action/unreadContent/{id}', function ($id) use ($twig, $db, $logger, $trans, $config) {

    $result = $db->query("update items set unread = 1 where guid = '" . $id . "'");

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /search (GET)
/* ---------------------------------------------------------------- */

$router->get('/search', function () use ($twig, $db, $logger, $trans, $config) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $search = $this->escape_string($_GET['plugin_search']);
    $requete = 'SELECT title,guid,content,description,link,pubdate,unread, favorite
            FROM `' . MYSQL_PREFIX . 'items`
            WHERE title like \'%' . htmlentities($search) . '%\'';
    if (isset($_GET['search_option']) && $_GET['search_option'] == "1") {
        $requete = $requete . ' OR content like \'%' . htmlentities($search) . '%\'';
    }
    $requete = $requete . ' ORDER BY pubdate desc';

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /action/readFolder (GET)
/* ---------------------------------------------------------------- */
/*
$router->get('/action/{readFolder}', function () use ($twig, $db,$logger,$trans,$config) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $whereClause = array();
    $whereClause['unread'] = '1';
    if (isset($_['feed'])) $whereClause['feed'] = $_['feed'];
    if (isset($_['last-event-id'])) $whereClause['id'] = '<= ' . $_['last-event-id'];
    $eventManager->change(array('unread' => '0'), $whereClause);
    if (!Functions::isAjaxCall()) {
        header('location: ./index.php');
    }

});
*/
// @todo

/* ---------------------------------------------------------------- */
// Route: /action/updateConfiguration (GET)
/* ---------------------------------------------------------------- */

$router->get('/action/updateConfiguration', function () use ($twig, $db, $logger, $trans, $config) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    /*
    $whereClause = array();
    $whereClause['unread'] = '1';
    if (isset($_['feed'])) $whereClause['feed'] = $_['feed'];
    if (isset($_['last-event-id'])) $whereClause['id'] = '<= ' . $_['last-event-id'];
    $eventManager->change(array('unread' => '0'), $whereClause);
    if (!Functions::isAjaxCall()) {
        header('location: ./index.php');
    }
    */

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /qrcode
/* ---------------------------------------------------------------- */

$router->mount('/qrcode', function () use ($router, $twig, $db, $logger, $trans, $config) {

    $router->get('/qr', function () {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        /*
        Functions::chargeVarRequest('label', 'user', 'key', 'issuer', 'algorithm', 'digits', 'period');
        if (empty($key)) {
            $key = "**********";
        }
        $qrCode = "otpauth://totp/{$label}:{$user}?secret={$key}";
        foreach (array('issuer', 'algorithm', 'digits', 'period') as $champ)
            if (!empty(${$champ}))
                $qrCode .= "&{$champ}={${$champ}}";


        Functions::chargeVarRequest('_qrSize', '_qrMargin');
        if (empty($_qrSize)) $_qrSize = 3;
        if (empty($_qrMargin)) $_qrMargin = 4;

        QRcode::png($qrCode, false, 'QR_LEVEL_H', $_qrSize, $_qrMargin);
    });

    $router->get('/text', function () use ($twig, $trans, $logger, $config) {

        $qrCode = substr($_SERVER['QUERY_STRING'], 1 + strlen($methode));
        */

    });

});


/* ---------------------------------------------------------------- */
// Route: /feed/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/feed/{id}', function ($id) use (
    $twig,
    $logger,
    $trans,
    $scroll,
    //$target,
    //$allEvents,
    //$allFeedsPerFolder,
    $config,
    $db,
    $itemsObject,
    $fluxObject,
    $categoryObject
) {

    $fluxObject->setId($id);
    $flux = $fluxObject->getFluxById();
    $itemsObject->setFeed($id);
    $numberOfItem = $itemsObject->countUnreadItemPerFlux();

    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $pages = ceil($numberOfItem / $config['articlePerPages']);
    $startArticle = ($page - 1) * $config['articlePerPages'];

    $offset = ($page - 1) * $config['articlePerPages'];

    $row_count = $config['articlePerPages'];

    echo $twig->render('index.twig',
        [
            'action' => 'feed',
            //'allEvents' => $allEvents,
            //'allFeedsPerFolder' => $allFeedsPerFolder,
            'events' => $itemsObject->loadUnreadItemPerFlux($offset, $row_count),
            'feed' => $flux,
            'folders' => $categoryObject->getFeedsByCategories(),
            'numberOfItem' => $numberOfItem,
            'page' => $page,
            'pages' => $pages,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll,
            //'target' => $target,
            'trans' => $trans,
            'config' => $config

        ]
    );

});

/* ---------------------------------------------------------------- */
// Route: /install
/* ---------------------------------------------------------------- */

$router->mount('/install', function () use ($router, $trans,$twig, $cookiedir, $logger) {

    /* ---------------------------------------------------------------- */
    // Route: /install (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/', function () use ($twig, $cookiedir,$trans) {

        $_SESSION['install'] = true;

        $filelist = glob("locales/*.json");

        foreach ($filelist as $file) {
            $locale = explode(".", basename($file));
            $list_lang[] = $locale[0];
        }

        $templateslist = glob("templates/*");
        foreach ($templateslist as $tpl) {
            $tpl_array = explode(".", basename($tpl));
            $listTemplates[] = $tpl_array[0];
        }

        echo $twig->render('install.twig',
            [
                'list_lang' => $list_lang,
                'list_templates' => $listTemplates,
                'trans' => $trans
            ]);

    });

    /* ---------------------------------------------------------------- */
    // Route: /install (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/', function () use ($twig, $cookiedir) {

        $install = new Install();
        /* Prend le choix de langue de l'utilisateur, soit :
         * - lorsqu'il vient de changer la langue du sélecteur ($lang)
         * - lorsqu'il vient de lancer l'installeur ($install_changeLngLeed)
         */
        $lang = '';
        if (isset($_GET['lang'])) $lang = $_GET['lang'];
        elseif (isset($_POST['install_changeLngLeed'])) $lang = $_POST['install_changeLngLeed'];
        $installDirectory = dirname(__FILE__) . '/install';
        // N'affiche que les langues du navigateur
        // @TODO: il faut afficher toutes les langues disponibles
        //        avec le choix par défaut de la langue préférée
        $languageList = Functions::getBrowserLanguages();
        if (!empty($lang)) {
            // L'utilisateur a choisi une langue, qu'on incorpore dans la liste
            array_unshift($languageList, $lang);
            $liste = array_unique($languageList);
        }
        unset($i18n); //@TODO: gérer un singleton et le choix de langue / liste de langue
        $currentLanguage = i18n_init($languageList, $installDirectory);
        $languageList = array_unique($i18n->languages);
        if (file_exists('constant.php')) {
            die(_t('ALREADY_INSTALLED'));
        }
        define('DEFAULT_TEMPLATE', 'influx');
        $templates = scandir('templates');
        if (!in_array(DEFAULT_TEMPLATE, $templates)) die('Missing default template : ' . DEFAULT_TEMPLATE);
        $templates = array_diff($templates, array(DEFAULT_TEMPLATE, '.', '..')); // Répertoires non voulus sous Linux
        sort($templates);
        $templates = array_merge(array(DEFAULT_TEMPLATE), $templates); // le thème par défaut en premier
// Cookie de la session
        $cookiedir = '';
        if (dirname($_SERVER['SCRIPT_NAME']) != '/') $cookiedir = dirname($_SERVER["SCRIPT_NAME"]) . '/';
        session_set_cookie_params(0, $cookiedir);
        session_start();
// Protection des variables
        $_ = array_merge($_GET, $_POST);
        $installActionName = 'installButton';
        $install->launch($_, $installActionName);

        $constant = "<?php
//Host de Mysql, le plus souvent localhost ou 127.0.0.1
define('MYSQL_HOST','{$this->options['db']['mysqlHost']}');
//Identifiant MySQL
define('MYSQL_LOGIN','{$this->options['db']['mysqlLogin']}');
//mot de passe MySQL
define('MYSQL_MDP','{$this->options['db']['mysqlMdp']}');
//Nom de la base MySQL ou se trouvera leed
define('MYSQL_BDD','{$this->options['db']['mysqlBase']}');
//Prefix des noms des tables leed pour les bases de données uniques
define('MYSQL_PREFIX','{$this->options['db']['mysqlPrefix']}');
?>";

        file_put_contents(self::CONSTANT_FILE, $constant);
        if (!is_readable(self::CONSTANT_FILE))
            die('"' . self::CONSTANT_FILE . '" not found!');

        header('location: /login');

    });

});

$router->run();