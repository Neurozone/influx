<?php

error_reporting(E_ALL & ~E_NOTICE);

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
$twig = new \Twig\Environment($loader, ['cache' => __DIR__ . '/cache', 'debug' => true,]);

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
    $userObject = new User($db, $logger);
    $categoryObject = new Category($db, $logger);
    $opmlObject = new Opml($db, $logger);

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
    $unreadEventsForCategory = 0;
    $hightlighted = 0;

    $page = 1;

} else {
    if (!isset($_SESSION['install'])) {
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

if (isset($config['language'])) {
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
} else {
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

$router->before('GET|POST|PUT|DELETE|PATCH|OPTIONS', '/.*', function () use ($logger) {

    $logger->info("before");
    $logger->info($_SERVER['REQUEST_URI']);
    $logger->info(getClientIP());

    if (!isset($_SESSION['install']) && !isset($_SESSION['user']) && $_SERVER['REQUEST_URI'] !== '/login') {
        header('Location: /login');
        exit();
    } else if (isset($_SESSION['install']) && $_SESSION['install'] && $_SERVER['REQUEST_URI'] !== '/install') {
        header('Location: /install');
        exit();
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

    $action = 'all';
    $numberOfItem = $itemsObject->countAllUnreadItem();
    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $startArticle = ($page - 1) * $config['articlePerPages'];

    $offset = ($page - 1) * 25; //$config['articlePerPages'];
    $row_count = 25; //$config['articlePerPages'];

    echo $twig->render('index.twig',
        [
            'action' => $action,
            'config' => $config,
            'events' => $itemsObject->loadAllUnreadItem($offset, $row_count),
            'categories' => $categoryObject->getFluxByCategories(),
            'numberOfItem' => $numberOfItem,
            'page' => $page,
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
// Route: /password/recover (GET)
/* ---------------------------------------------------------------- */

$router->get('/password/recover', function () use ($db, $twig, $config, $logger, $trans) {

    echo $twig->render('recover.twig', []);

});

// Route: /password/new/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/password/new/{token}', function ($token) use ($db, $twig, $config, $logger, $trans, $userObject) {

    $userObject->setToken($token);
    $userInfos = $userObject->getUserInfosByToken();
    echo $twig->render('password.twig', ['token' => $token]);

});

$router->post('/password/new', function ($token) use ($db, $twig, $config, $logger, $trans, $userObject) {


    $userObject->setToken(token);
    $userInfos = $userObject->createHash($_POST['password']);
    echo $twig->render('password.twig', []);

});


/* ---------------------------------------------------------------- */
// Route: /password/recover (POST)
/* ---------------------------------------------------------------- */

$router->post('/password/recover', function () use ($db, $config, $logger) {

    $token = bin2hex(random_bytes(50));

    if ($stmt = $db->prepare("select id,login,email from user where email = ?")) {
        $stmt->bind_param("s", $_POST['email']);
        /* execute query */
        $stmt->execute();

        /* instead of bind_result: */
        $result = $stmt->get_result();

        while ($row = $result->fetch_array()) {
            $login = $row['login'];
            $email = $row['email'];

        }
    }

    if (!empty($login)) {
        $db->query("UPDATE user SET token = '" . $token . "' where email = '" . $email . "'");
    }

    $logger->error("Message could not be sent to: " . $email);
    $logger->error("Message could not be sent to: " . $_POST['email']);

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = 2;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host = SMTP_HOST;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = SMTP_AUTH;                                   // Enable SMTP authentication
        $mail->Username = SMTP_LOGIN;                     // SMTP username
        $mail->Password = SMTP_PASSWORD;                               // SMTP password
        $mail->SMTPSecure = SMTP_SECURE;                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port = SMTP_PORT;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom('rss@neurozone.fr', 'no-reply@neurozone.fr');
        $mail->addAddress($email, $login);

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Reset your password on InFlux';
        $mail->Body = 'Hi there, click on this <a href="https://influx.neurozone.fr/password/new/' . $token . '">link</a> to reset your password on our site';
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        $logger->error("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
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
    $unreadEventsForcategory,
    $config,
    $db,
    $categoryObject,
    $itemsObject,
    $fluxObject
) {

    $numberOfItem = $itemsObject->getNumberOfFavorites();
    $flux = $fluxObject->getFluxById();

    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $startArticle = ($page - 1) * $config['articlePerPages'];

    $offset = ($page - 1) * $config['articlePerPages'];
    $row_count = $config['articlePerPages'];

    echo $twig->render('index.twig',
        [

            'events' => $itemsObject->getAllFavorites($offset, $row_count),
            'category' => $categoryObject->getFluxByCategories(),
            'numberOfItem' => $numberOfItem,
            'page' => $page,
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
    // Route: /article/flux/ (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/flux', function () use ($twig, $db, $logger, $config) {

        $scroll = $_POST['scroll'];
        $nblus = isset($_POST['nblus']) ? $_POST['nblus'] : 0;
        $hightlighted = $_POST['hightlighted'];
        $action = $_POST['action'];
        $category = $_POST['category'];
        $flux = (int)$_POST['flux'];

        $nblus = isset($_POST['nblus']) ? $_POST['nblus'] : 0;

        $articleConf['startArticle'] = ($scroll * 50) - $nblus;

        $logger->info($articleConf['startArticle']);
        $logger->info($config['articlePerPages']);

        $offset = $articleConf['startArticle'];
        $rowcount = $articleConf['startArticle'] + $config['articlePerPages'];

        if ($articleConf['startArticle'] < 0) {
            $articleConf['startArticle'] = 0;
        }

        $q = 'SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.flux,le.favorite,le.pubdate,le.syncId, lf.name as flux_name
                FROM items le 
                    inner join flux lf on lf.id = le.flux 
                where le.flux = ' . $flux . ' 
                ORDER BY pubdate desc,unread desc LIMIT ' . $offset . ',' . $rowcount;

        $logger->info($q);

        $results = $db->query($q);

        while ($rows = $results->fetch_array()) {

            $items[] = array(
                'id' => $rows['guid'],
                'guid' => $rows['guid'],
                'title' => $rows['title'],
                'creator' => $rows['creator'],
                'content' => $rows['content'],
                'description' => $rows['description'],
                'link' => $rows['link'],
                'unread' => $rows['unread'],
                'flux' => $rows['flux'],
                'favorite' => $rows['favorite'],
                'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
                'syncId' => $rows['syncId'],
                'flux_name' => $rows['flux_name'],
            );

        }

        echo $twig->render('article.twig',
            [
                'events' => $items,
                'scroll' => $scroll,
            ]
        );

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/category/{id} (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/category/{id}', function () use ($twig, $db, $logger, $trans, $config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        header('location: /settings/manage');

    });

});

/* ---------------------------------------------------------------- */
// Route: /settings (GET)
/* ---------------------------------------------------------------- */

$router->mount('/settings', function () use ($router, $twig, $trans, $logger, $config, $db, $cookiedir, $categoryObject, $fluxObject, $opmlObject) {

    $router->get('/', function () use ($twig, $cookiedir) {

        header('location: /settings/manage');

    });

    $router->get('/settings/user', function () use ($twig, $cookiedir) {

        header('location: /settings/manage');

    });


    /* ---------------------------------------------------------------- */
    // Route: /settings/synchronize/all (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/synchronize', function ($option) use ($twig, $trans, $logger, $config, $cookiedir) {



    });

    /* ---------------------------------------------------------------- */
    // Route: /statistics (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/statistics', function () use ($twig, $trans, $logger, $config) {

        echo '
	<section id="leedStatslBloc" class="leedStatslBloc" style="display:none;">
		<h2>' . _t('P_LEEDSTATS_TITLE') . '</h2>

		<section class="preferenceBloc">
		<h3>' . _t('P_LEEDSTATS_RESUME') . '</h3>
	';

        //Nombre global d'article lus / non lus / total / favoris
        $requete = 'SELECT
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'flux`) as nbFlux,
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
                    <td class="leedStats_border leedStats_textright">' . $data['nbFlux'] . '</td>
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
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.unread=1 and le1.flux = le2.flux) as nbUnread,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.unread=0 and le1.flux = le2.flux) as nbRead,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.favorite=1 and le1.flux = le2.flux) as nbFavorite
                FROM `' . MYSQL_PREFIX . 'flux` lf1
                INNER JOIN `' . MYSQL_PREFIX . 'items` le1 on le1.flux = lf1.id
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

        $requete = 'select lf.name, FROM_UNIXTIME(max(le.pubdate)) last_published from flux lf inner join items le on lf.id = le.flux group by lf.name order by 2';

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
    // Route: /settings/flux/add (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/flux/add', function () use ($twig, $trans, $logger, $config, $fluxObject) {

        $cat = isset($_POST['newUrlCategory']) ? $_POST['newUrlCategory'] : 1;
        $sp = new SimplePie();

        $fluxObject->setUrl($_POST['newUrl']);

        if ($fluxObject->notRegistered()) {

            //$fluxObject->getInfos();
            $fluxObject->setcategory((isset($_POST['newUrlCategory']) ? $_POST['newUrlCategory'] : 1));
            $fluxObject->add($sp);

        } else {

            $logger->info($trans['FEED_ALREADY_STORED']);
        }
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/remove/{id} (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/flux/remove/{id}', function ($id) use ($twig, $trans, $logger, $config, $fluxObject) {


        $fluxObject->setId($id);
        $logger->info($fluxObject->getId($id));
        $fluxObject->remove();

        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/rename (POST)
    // Action: Rename flux
    /* ---------------------------------------------------------------- */

    $router->post('/flux/rename', function () use ($logger, $fluxObject) {

        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}

        $fluxObject->setId($_POST['id']);
        $fluxObject->setName($_POST['name']);
        $fluxObject->setUrl($_POST['url']);

        return $fluxObject->rename();

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/category/{id} (GET)
    // Action: Rename flux
    /* ---------------------------------------------------------------- */

    $router->get('/flux/category/{id}', function ($id) use ($twig, $trans, $logger, $config, $db, $fluxObject) {

        $fluxObject->setCategory($_GET['id']);
        $fluxObject->setName($_GET['name']);
        $fluxObject->setUrl($_GET['url']);

        $fluxObject->changeCategory();

        header('location: /settings');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/add (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/category/add', function () use ($twig, $db, $logger, $trans, $config, $categoryObject) {

        $name = $_POST['categoryName'];
        $categoryObject->setName($name);
        if (isset($_POST['categoryName']) && !$categoryObject->exist()) {

            $categoryObject->add();
        }
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/remove/{id} (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/category/remove/{id}', function ($id) use ($twig, $db, $logger, $trans, $config) {


        if (isset($id) && is_numeric($id) && $id > 0) {
            //$eventManager->customQuery('DELETE FROM `' . MYSQL_PREFIX . 'items` WHERE `' . MYSQL_PREFIX . 'event`.`flux` in (SELECT `' . MYSQL_PREFIX . 'flux`.`id` FROM `' . MYSQL_PREFIX . 'flux` WHERE `' . MYSQL_PREFIX . 'flux`.`category` =\'' . intval($_['id']) . '\') ;');
            //$fluxManager->delete(array('category' => $id));
            //$categoryManager->delete(array('id' => $id));
        }
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/rename (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/category/rename', function () use ($twig, $db, $logger, $trans, $config, $categoryObject) {

        $id = $_POST['id'];
        $name = $_POST['name'];
        $categoryObject->setId($id);
        $categoryObject->setName($name);

        $logger->info(" avant le if rename");

        if (isset($_POST['id']) && $categoryObject->exist()) {

            $logger->info(" on rentre dans le if rename");
            $categoryObject->rename();
        }
        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/export (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/flux/export', function () use ($twig, $db, $logger, $trans, $config, $opmlObject) {

        header('Content-Disposition: attachment;filename=export.opml');
        header('Content-Type: text/xml');

        echo $opmlObject->export();

    });

    $router->get('/fluxs/import', function () use ($twig, $db, $logger, $trans, $config) {


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
                $text = Functions::truncate($alreadyKnown->fluxName, 60);
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
                'action' => 'category',
                'section' => 'fluxs/import',
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

        $resultsFlux = $db->query('SELECT * FROM flux f ORDER BY name ');
        while ($rows = $resultsFlux->fetch_array()) {
            $flux['id'] = $rows['id'];
        }

        $logger->info('Section: ' . $option);

        echo $twig->render('settings.twig',
            [
                'action' => 'category',
                'section' => $option,
                'trans' => $trans,
                'themeList' => $themeList,
                'otpEnabled' => false,
                'currentTheme' => $config['theme'],
                'categories' => $categoryObject->getFluxByCategories(),
                'flux' => $flux,
                'config' => $config,
                'user' => $_SESSION['user']
            ]
        );

    });

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /action/read/all (GET)
/* ---------------------------------------------------------------- */


$router->get('/action/read/all', function () use ($twig, $db, $logger, $trans, $config, $fluxObject) {

    $fluxObject->markAllRead();

    header('location: /');

});

/* ---------------------------------------------------------------- */
// Route: /action/read/flux/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/action/read/flux/{id}', function ($id) use ($twig, $db, $logger, $trans, $config, $itemsObject) {

    $itemsObject->setGuid($id);
    $itemsObject->markItemAsReadByGuid();

});

/* ---------------------------------------------------------------- */
// Route: /action/unread/flux/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/action/unread/flux/{id}', function ($id) use ($twig, $db, $logger, $trans, $config) {

    $result = $db->query("update items set unread = 1 where guid = '" . $id . "'");

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /search (GET)
/* ---------------------------------------------------------------- */

$router->get('/search', function () use ($twig, $db, $logger, $trans, $config) {

    $search = $this->escape_string($_GET['plugin_search']);
    $requete = "SELECT title,guid,content,description,link,pubdate,unread, favorite FROM items 
            WHERE title like '%" . htmlentities($search) . '%\'  OR content like \'%' . htmlentities($search) . '%\' ORDER BY pubdate desc';

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /action/read/category/{id} (GET)
/* ---------------------------------------------------------------- */
/*
$router->get('/action/read/category/{id}', function () use ($twig, $db,$logger,$trans,$config) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $whereClause = array();
    $whereClause['unread'] = '1';
    if (isset($_['flux'])) $whereClause['flux'] = $_['flux'];
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
    if (isset($_['flux'])) $whereClause['flux'] = $_['flux'];
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
// Route: /flux/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/flux/{id}', function ($id) use (
    $twig,
    $logger,
    $trans,
    $scroll,
    $config,
    $db,
    $itemsObject,
    $fluxObject,
    $categoryObject
) {

    $fluxObject->setId($id);
    $flux = $fluxObject->getFluxById();
    $itemsObject->setFlux($id);
    $numberOfItem = $itemsObject->countUnreadItemPerFlux();

    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $startArticle = ($page - 1) * $config['articlePerPages'];

    $offset = ($page - 1) * $config['articlePerPages'];
    $row_count = $config['articlePerPages'];

    echo $twig->render('index.twig',
        [
            'action' => 'flux',
            'events' => $itemsObject->loadUnreadItemPerFlux($offset, $row_count),
            'flux' => $flux,
            'fluxId' => $id,
            'categories' => $categoryObject->getFluxByCategories(),
            'numberOfItem' => $numberOfItem,
            'page' => $page,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll,
            'trans' => $trans,
            'config' => $config

        ]
    );

});

/* ---------------------------------------------------------------- */
// Route: /install
/* ---------------------------------------------------------------- */

$router->mount('/install', function () use ($router, $trans, $twig, $cookiedir, $logger) {

    /* ---------------------------------------------------------------- */
    // Route: /install (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/', function () use ($twig, $cookiedir, $trans) {

        /*
        if(file_exists('conf/config.php'))
        {
            session_unset();
            session_destroy();
            header('location: /login');
        }
        */

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
    // Route: /install/check (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/check', function () use ($twig, $cookiedir,$trans) {

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
    // Route: /install/database (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/database', function () use ($twig, $cookiedir,$trans) {

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
    // Route: /install/user (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/user', function () use ($twig, $cookiedir,$trans) {

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
    // @todo
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