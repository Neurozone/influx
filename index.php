<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

error_reporting(E_ALL & ~E_NOTICE);

require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use Sinergi\BrowserDetector\Language;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Influx\Flux;
use Influx\Items;
use Influx\User;
use Influx\Category;
use Influx\Opml;
use Influx\Configuration;
use Influx\Statistics;

if (defined('LOGS_DAYS_TO_KEEP')) {
    $handler = new RotatingFileHandler(__DIR__ . '/logs/influx.log', LOGS_DAYS_TO_KEEP);
} else {
    $handler = new RotatingFileHandler(__DIR__ . '/logs/influx.log', 7);
}

$logger = new Logger('influxLogger');
$logger->pushHandler($handler);

$router = new \Bramus\Router\Router();

session_start();

if (file_exists('conf/config.php')) {
    require_once('conf/config.php');

    /* ---------------------------------------------------------------- */
    // Database
    /* ---------------------------------------------------------------- */

    $_SESSION['install'] = false;

    $db = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_MDP, MYSQL_BDD);
    $db->set_charset('utf8mb4');
    $db->query('SET NAMES utf8mb4');

    $conf = new Configuration($db);
    $config = $conf->getAll();

    $templateName = 'influx-adminlte-3';
    $templatePath = __DIR__ . '/templates/' . $templateName;

    $loader = new \Twig\Loader\FilesystemLoader($templatePath);
    $twig = new \Twig\Environment($loader, ['cache' => __DIR__ . '/cache', 'debug' => true,]);
    $twig->addExtension(new \Twig\Extension\DebugExtension());

    $fluxObject = new Flux($db, $logger);
    $itemsObject = new Items($db, $logger);
    $userObject = new User($db, $logger);
    $categoryObject = new Category($db, $logger);
    $opmlObject = new Opml($db, $logger);

    $synchronisationCode = $config['synchronisationCode'];

    mb_internal_encoding('UTF-8');
    $start = microtime(true);

    /* ---------------------------------------------------------------- */
    // Timezone
    /* ---------------------------------------------------------------- */

    $timezone_default = 'Europe/Paris';
    date_default_timezone_set($timezone_default);

    $unreadEventsForCategory = 0;

} else {
    if (!isset($_SESSION['install'])) {
        $_SESSION['install'] = true;
        header('location: /install');
        exit();
    }

}

function siteURL()
{
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName;
}

define('SITE_URL', siteURL());
$logger->info('url: ' . SITE_URL);

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
// i18n
/* ---------------------------------------------------------------- */

$language = new Language();

if (isset($config['language'])) {
    if ($language->getLanguage() == $config['language'] && is_file('locales/' . $config['language'] . '.json')) {
        $_SESSION['language'] = $language->getLanguage();
        $l_trans = json_decode(file_get_contents('templates/' . $templateName . '/locales/' . $config['language'] . '.json'), true);
    } elseif ($language->getLanguage() != $config['language'] && is_file('locales/' . $config['language'] . '.json')) {
        $_SESSION['language'] = $language->getLanguage();
        $l_trans = json_decode(file_get_contents('templates/' . $templateName . '/locales/' . $config['language'] . '.json'), true);
    } elseif (!is_file('locales/' . $config['language'] . '.json')) {
        $_SESSION['language'] = 'en';
        $l_trans = json_decode(file_get_contents('templates/' . $templateName . '/locales/' . $_SESSION['language'] . '.json'), true);
    }
} else {
    $_SESSION['language'] = 'en';
    $l_trans = json_decode(file_get_contents('templates/' . $templateName . '/locales/' . $_SESSION['language'] . '.json'), true);
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
    $logger->info($_SESSION['user']);
    $logger->info($_SERVER['REQUEST_METHOD']);
    $logger->info($_SERVER['HTTP_HOST']);

    if (file_exists('installed') && !isset($_SESSION['user']) && $_SERVER['REQUEST_URI'] == '/password/recover') {
        header('Location: /password/recover');
        exit();
    } elseif (file_exists('installed') && !isset($_SESSION['user']) && $_SERVER['REQUEST_URI'] !== '/login') {
        header('Location: /login');
        exit();
    } else if (!file_exists('installed') && $_SERVER['REQUEST_URI'] !== '/install') {
        header('Location: /install');
        exit();
    } else {
        $logger->info("on passe dans ce before");
    }

});

/* ---------------------------------------------------------------- */
// Route: / (GET)
// response: html
/* ---------------------------------------------------------------- */

$router->get('/', function () use ($twig, $config, $trans, $itemsObject, $categoryObject) {

    $action = 'all';
    $numberOfItem = $itemsObject->countAllUnreadItem();

    $offset = 0;
    $row_count = $config['articlePerPages'] - 1;

    echo $twig->render('index.twig',
        [
            'action' => $action,
            'config' => $config,
            'events' => $itemsObject->loadAllUnreadItem($offset, $row_count),
            'categories' => $categoryObject->getFluxByCategories(),
            'numberOfItem' => $numberOfItem,
            'user' => $_SESSION['user'],
            'trans' => $trans,
            'url' => SITE_URL
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

$router->post('/login', function () use ($db, $config, $logger, $userObject) {

    $userObject->setLogin($_POST['login']);

    if ($userObject->checkPassword($_POST['password'])) {

        $_SESSION['user'] = $_POST['login'];
        $_SESSION['userId'] = $userObject->getId();
        $_SESSION['userEmail'] = $userObject->getEmail();
        if (isset($_POST['rememberMe'])) {
            setcookie('InfluxChocolateCookie', sha1($_POST['password'] . $_POST['login']), time() + 31536000);
        }
        header('location: /');

    } else {
        header('location: /login');
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

// @TODO: à mettre en place
/* ---------------------------------------------------------------- */
// Route: /favorites (GET)
/* ---------------------------------------------------------------- */

$router->get('/favorites', function () use ($twig, $logger, $config, $categoryObject, $itemsObject, $fluxObject) {

    $numberOfItem = $itemsObject->getNumberOfFavorites();
    $flux = $fluxObject->getFluxById();

    $offset = 0;
    $row_count = $config['articlePerPages'] - 1;

    echo $twig->render('index.twig',
        [
            'events' => $itemsObject->getAllFavorites($offset, $row_count),
            'category' => $categoryObject->getFluxByCategories(),
            'numberOfItem' => $numberOfItem,
            'user' => $_SESSION['user']
        ]
    );

});

$router->mount('/password', function () use ($router, $twig, $trans, $logger, $config, $userObject) {


    /* ---------------------------------------------------------------- */
    // Route: /password/new/{id} (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/password/new/{token}', function ($token) use ($userObject) {

        $userObject->setToken($token);
        $userInfos = $userObject->getUserInfosByToken();
        echo $twig->render('password.twig', ['token' => $token]);

    });

    $router->post('/password/new', function () use ($userObject) {

        $userObject->setToken($_POST['token']);
        $userInfos = $userObject->createHash($_POST['password']);
        header('location: /');

    });

    /* ---------------------------------------------------------------- */
    // Route: /password/recover (GET)
    // Response: html
    /* ---------------------------------------------------------------- */

    $router->get('/recover', function () use ($twig, $config, $logger, $trans) {

        echo $twig->render('recover.twig', []);

    });

    /* ---------------------------------------------------------------- */
    // Route: /password/recover (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/password/recover', function () use ($config, $logger, $userObject) {

        $token = bin2hex(random_bytes(50));

        $userObject->setEmail($_POST['email']);

        if ($userObject->userExistBy('email')) {
            $userObject->createTokenForUser();
        } else {
            $logger->error("Message could not be sent to: " . $_POST['email']);
        }

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
            $mail->addAddress($_POST['email'], $_POST['login']);

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

});

/* ---------------------------------------------------------------- */
// Route: /article (GET)
/* ---------------------------------------------------------------- */

$router->mount('/article', function () use ($router, $twig, $db, $logger, $trans, $config, $itemsObject) {

    /* ---------------------------------------------------------------- */
    // Route: /article (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/', function () use ($twig, $logger, $trans, $config) {

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

    $router->post('/flux', function () use ($twig, $db, $logger, $config, $itemsObject) {

        $page = $_POST['page'];
        $flux = (int)$_POST['flux'];
        $logger->info($config['articlePerPages']);

        /*
        $offset = $articleConf['startArticle'];
        $rowcount = $articleConf['startArticle'] + $config['articlePerPages'];

        if ($articleConf['startArticle'] < 0) {
            $articleConf['startArticle'] = 0;
        }
        */

        /*
         *
            1 0 9
            2 10 19
            3 20 29
            4 30 39
         */
        $offset = $page * $config['articlePerPages'] - $config['articlePerPages'];
        $rowCount = $page * $config['articlePerPages'] - 1;
        $logger->info('offset: ' . $offset);
        $logger->info('rowCount: ' . $rowCount);

        $itemsObject->setFlux($flux);
        $items = $itemsObject->loadUnreadItemPerFlux($offset, $rowCount);

        echo $twig->render('article.twig',
            [
                'events' => $items,
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
// Route: /category
// /category
// /category/insert	        post return 201 on success or 512 on failure
// /category/update/name    post return 200 on success or 512 on failure
// /category/read	        post return 200 on success or 512 on failure
// /category/delete	        post return 200 on success or 512 on failure
// POST: Create
// PUT: Replace/Update global (ex toute une ligne)
// PATCH: Update partiel (ex une colonne)
/* ---------------------------------------------------------------- */

$router->mount('/category', function () use ($router, $twig, $trans, $logger, $config, $db, $categoryObject, $fluxObject) {

    /* ---------------------------------------------------------------- */
    // Route: /category (GET)
    // Response: 404
    /* ---------------------------------------------------------------- */

    $router->get('/', function () {
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /category/insert (POST)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->post('/insert', function () use ($categoryObject) {

        $name = $_POST['categoryName'];
        $categoryObject->setCategoryName($name);
        if (isset($_POST['categoryName']) && !$categoryObject->existingCategory()) {

            return $categoryObject->insertCategory();
        }

    });

    /* ---------------------------------------------------------------- */
    // Route: /category/update/name (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->post('/update/name', function () use ($categoryObject) {

        $categoryObject->setCategoryId($_POST['id']);
        $categoryObject->setCategoryName($_POST['name']);
        return $categoryObject->updateCategoryName();

    });

    /* ---------------------------------------------------------------- */
    // Route: /category/read (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->post('/read', function () use ($categoryObject) {

        $categoryObject->setCategoryId($_POST['id']);
        return $categoryObject->markCategoryAsRead();

    });

    /* ---------------------------------------------------------------- */
    // Route: /category/delete (DELETE)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->post('/delete', function () use ($categoryObject) {

        $categoryObject->setCategoryId($_POST['id']);
        return $categoryObject->deleteCategory();

    });

});

/* ---------------------------------------------------------------- */
// Route: /flux
// /flux
// /flux/insert	                post return 201 on success or 512 on failure
// /flux/update/name            post return 200 on success or 512 on failure
// /flux/update/description     post return 200 on success or 512 on failure
// /flux/update/website         post return 200 on success or 512 on failure
// /flux/update/url             post return 200 on success or 512 on failure
// /flux/update/category        post return 200 on success or 512 on failure
// /flux/update/all             post return 200 on success or 512 on failure
// /flux/read	                post return 200 on success or 512 on failure
// /flux/delete	                post return 200 on success or 512 on failure
// POST: Create
// PUT: Replace/Update global (ex toute une ligne)
// PATCH: Update partiel (ex une colonne)
/* ---------------------------------------------------------------- */

$router->mount('/flux', function () use ($router, $twig, $trans, $logger, $config, $db, $fluxObject, $itemsObject, $categoryObject) {

    /* ---------------------------------------------------------------- */
    // Route: /category (GET)
    // Response: 404
    /* ---------------------------------------------------------------- */

    $router->get('/', function () {

        return http_response_code(404);

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/insert (POST)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->post('/insert', function () use ($fluxObject, $logger, $trans) {

        $sp = new SimplePie();
        $fluxObject->setFluxUrl($_POST['newUrl']);

        if ($fluxObject->notRegistered()) {

            $fluxObject->setFluxcategory((isset($_POST['newUrlCategory']) ? $_POST['newUrlCategory'] : 1));
            $fluxObject->add($sp);
        } else {
            $logger->info($trans['FEED_ALREADY_STORED']);
        }

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/update/name (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->patch('/update/name', function () use ($fluxObject) {

        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}
        $fluxObject->setId($_POST['id']);
        $fluxObject->setName($_POST['name']);
        return $fluxObject->updateFluxName();

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/update/description (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->patch('/update/description', function () use ($fluxObject) {

        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}
        $fluxObject->setId($_POST['id']);
        $fluxObject->setDescription($_POST['description']);
        return $fluxObject->updateFluxDescription();

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/update/website (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->patch('/update/website', function () use ($fluxObject) {

        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}
        $fluxObject->setId($_POST['id']);
        $fluxObject->setWebsite($_POST['website']);
        return $fluxObject->updateFluxWebsite();

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/update/url (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->patch('/update/url', function () use ($fluxObject) {

        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}
        $fluxObject->setId($_POST['id']);
        $fluxObject->setUrl($_POST['url']);
        return $fluxObject->updateFluxUrl();

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/update/category (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->patch('/update/category', function () use ($fluxObject) {

        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}
        $fluxObject->setId($_POST['id']);
        $fluxObject->setCategory($_POST['category']);
        return $fluxObject->updateFluxCategory();

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/update/all (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->patch('/update/all', function () use ($fluxObject) {

        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}
        $fluxObject->setId($_POST['id']);
        $fluxObject->setName($_POST['name']);
        $fluxObject->setDescription($_POST['description']);
        $fluxObject->setWebsite($_POST['website']);
        $fluxObject->setUrl($_POST['url']);
        $fluxObject->setCategory($_POST['category']);
        return $fluxObject->updateAll();

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/read (PATCH)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->patch('/read', function () use ($fluxObject) {

        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}
        $fluxObject->setId($_POST['id']);
        return $fluxObject->readFlux();

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/delete (DELETE)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->delete('/delete', function () use ($fluxObject, $logger) {

        $fluxObject->setId($_POST['id']);
        $logger->info($fluxObject->getId($_POST['id']));
        $fluxObject->deleteFlux();

    });

    /* ---------------------------------------------------------------- */
    // Route: /flux/{id} (GET)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->get('/{id}', function ($id) use ($twig, $logger, $trans, $config, $itemsObject, $fluxObject, $categoryObject) {

        $fluxObject->setId($id);
        $flux = $fluxObject->getFluxById();
        $itemsObject->setFlux($id);
        $numberOfItem = $itemsObject->countUnreadItemPerFlux();

        $page = (isset($_GET['page']) ? $_GET['page'] : 1);
        $offset = 0;
        $row_count = $config['articlePerPages'] - 1;

        echo $twig->render('index.twig',
            [
                'action' => 'item',
                'events' => $itemsObject->loadUnreadItemPerFlux($offset, $row_count),
                'flux' => $flux,
                'fluxId' => $id,
                'categories' => $categoryObject->getFluxByCategories(),
                'numberOfItem' => $numberOfItem,
                'user' => $_SESSION['user'],
                'trans' => $trans,
                'config' => $config,
                'url' => SITE_URL
            ]
        );

    });

});

/* ---------------------------------------------------------------- */
// Route: /item
// /item
// /item/update/flag	        post return 201 on success or 512 on failure
// /item/update/unflag	        post return 201 on success or 512 on failure
// /item/update/read	        post return 200 on success or 512 on failure
// /item/update/unread	        post return 200 on success or 512 on failure
// POST: Create
// PUT: Replace/Update global (ex toute une ligne)
// PATCH: Update partiel (ex une colonne)
/* ---------------------------------------------------------------- */

$router->mount('/item', function () use ($router, $twig, $trans, $logger, $config, $db, $itemsObject, $categoryObject, $fluxObject) {

    /* ---------------------------------------------------------------- */
    // Route: /item (GET)
    // Response:
    /* ---------------------------------------------------------------- */

    $router->get('/', function () use ($itemsObject) {

        return http_response_code(404);

    });

    /* ---------------------------------------------------------------- */
    // Route: /item/update/read (PATCH)
    // Response:
    /* ---------------------------------------------------------------- */

    $router->post('/update/read', function () use ($itemsObject) {

        $itemsObject->setItemGuid($_POST['guid']);
        $itemsObject->updateItemAsReadByGuid();

    });

    /* ---------------------------------------------------------------- */
    // Route: /item/update/unread (PATCH)
    // Response:
    /* ---------------------------------------------------------------- */

    $router->post('/update/unread', function () use ($itemsObject) {

        $itemsObject->setItemGuid($_POST['guid']);
        $itemsObject->updateItemAsUnreadByGuid();

    });

    /* ---------------------------------------------------------------- */
    // Route: /item/update/markFlaggedUnflagged (PATCH)
    // Response:
    /* ---------------------------------------------------------------- */

    $router->post('/update/markFlaggedUnflagged', function () use ($itemsObject) {

        $itemsObject->setItemGuid($_POST['guid']);
        $itemsObject->updateItemFlaggedUnflaggedByGuid();

    });

    /* ---------------------------------------------------------------- */
    // Route: /item/update/readUnread (PATCH)
    // Response:
    /* ---------------------------------------------------------------- */

    $router->post('/update/readUnread', function () use ($itemsObject) {

        $itemsObject->setItemGuid($_POST['guid']);
        $itemsObject->updateItemReadUnreadByGuid();

    });

    /* ---------------------------------------------------------------- */
    // Route: /item/select (POST)
    // Response: success 200 failure 512
    /* ---------------------------------------------------------------- */

    $router->post('/select', function () use ($fluxObject, $logger, $config, $itemsObject, $twig) {

        $page = $_POST['page'];
        if(isset($_POST['flux']) && !empty($_POST['flux'])){
            $flux = (int)$_POST['flux'];
            $isFlux = true;
        }

        $logger->info($config['articlePerPages']);

        /*
         *
            1 0 9
            2 10 19
            3 20 29
            4 30 39
         */
        $offset = $page * $config['articlePerPages'] - $config['articlePerPages'];
        $rowCount = $page * $config['articlePerPages'] - 1;
        $logger->info('offset: ' . $offset);
        $logger->info('rowCount: ' . $rowCount);

        if($isFlux){
            $itemsObject->setFlux($flux);
            $items = $itemsObject->loadUnreadItemPerFlux($offset, $rowCount);
        } else {
            $items = $itemsObject->loadAllUnreadItem($offset, $rowCount);
        }

        echo $twig->render('article.twig',
            [
                'events' => $items,
            ]
        );

    });

});

/* ---------------------------------------------------------------- */
// Route: /settings (GET)
/* ---------------------------------------------------------------- */

$router->mount('/settings', function () use ($router, $twig, $trans, $logger, $config, $db, $cookiedir, $categoryObject, $fluxObject, $opmlObject) {

    $router->get('/', function () use ($twig, $cookiedir) {

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/user (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/user', function () use ($twig, $cookiedir) {

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/statistics (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/statistics', function () use ($twig, $trans, $logger, $config, $db) {

        echo '';

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
    // Action: Change category
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

        echo $opmlObject->renderOpml();

    });

    $router->get('/flux/import', function () use ($twig, $db, $logger, $trans, $config) {


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

        // @todo
        $resultsFlux = $db->query('SELECT id FROM flux f ORDER BY name ');
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
                'user' => $_SESSION['user'],
                'url' => SITE_URL
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

$router->get('/action/read/flux/{id}', function ($id) use ($twig, $db, $logger, $trans, $config, $fluxObject) {

    $fluxObject->setId($id);
    $fluxObject->markAllRead();
    header('location: /');

});

/* ---------------------------------------------------------------- */
// Route: /action/unread/flux/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/action/unread/flux/{id}', function ($id) use ($twig, $db, $logger, $trans, $config) {

    $result = $db->query("update items set unread = 1 where guid = '" . $id . "'");

});

/* ---------------------------------------------------------------- */
// Route: /action/read/item/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/action/read/item/{id}', function ($id) use ($logger, $itemsObject) {

    $itemsObject->setGuid($id);
    $itemsObject->markItemAsReadByGuid();
    //header('location: /');

});

/* ---------------------------------------------------------------- */
// Route: /action/unread/item/{id} (GET)
/* ---------------------------------------------------------------- */

$router->get('/action/unread/item/{id}', function ($id) use ($twig, $db, $logger, $trans, $config) {

    $result = $db->query("update items set unread = 1 where guid = '" . $id . "'");

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /search (GET)
/* ---------------------------------------------------------------- */

$router->post('/search', function () use ($twig, $db, $logger, $trans, $config) {

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
// @TODO
/* ---------------------------------------------------------------- */

$router->mount('/qrcode', function () use ($router, $twig, $db, $logger, $trans, $config) {

    /* ---------------------------------------------------------------- */
    // Route: /qrcode/qr (GET)
    /* ---------------------------------------------------------------- */

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
// Route: /install
/* ---------------------------------------------------------------- */

$router->mount('/install', function () use ($router, $trans, $twig, $logger) {

    /* ---------------------------------------------------------------- */
    // Route: /install (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/', function () use ($twig, $trans) {

        $_SESSION['install'] = true;

        $installObject = new \Influx\Install();

        $templatesList = glob("templates/*");
        foreach ($templatesList as $tpl) {
            $tpl_array = explode(".", basename($tpl));
            $listTemplates[] = $tpl_array[0];
        }

        $fileList = glob("templates/influx/locales/*.json");

        foreach ($fileList as $file) {
            $locale = explode(".", basename($file));
            $list_lang[] = $locale[0];
        }

        $root = $installObject->getRoot();

        echo $twig->render('install.twig',
            [
                'action' => 'general',
                'list_lang' => $list_lang,
                'list_templates' => $listTemplates,
                'root' => $root,
                'trans' => $trans,
            ]);

    });

    /* ---------------------------------------------------------------- */
    // Route: /install/ (POST)
    /* ---------------------------------------------------------------- */

    $router->post('/', function () use ($twig, $trans) {

        if ($_POST['action'] == 'database') {
            $_SESSION['language'] = $_POST['install_changeLng'];
            $_SESSION['template'] = $_POST['template'];
            $_SESSION['root'] = $_POST['root'];
        }

        if ($_POST['action'] == 'check') {
            $_SESSION['language'] = $_POST['install_changeLng'];
            $_SESSION['template'] = $_POST['template'];
            $_SESSION['root'] = $_POST['root'];
        }

        if ($_POST['action'] == 'admin') {
            $_SESSION['login'] = $_POST['login'];
            $_SESSION['password'] = $_POST['password'];
        }

        echo $twig->render('install.twig',
            [
                'action' => $_POST['action'],
                'trans' => $trans
            ]);

    });

    /* ---------------------------------------------------------------- */
    // Route: /install/database (GET)
    /* ---------------------------------------------------------------- */

    $router->get('/database', function () use ($twig, $trans) {

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

    $router->get('/user', function () use ($twig, $trans) {

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

    $router->post('/', function () use ($twig) {

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
        //$languageList = array_unique($i18n->languages);
        if (file_exists('constant.php')) {
            die('ALREADY_INSTALLED');
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
        if (!is_readable(self::CONSTANT_FILE)) {
            die('"' . self::CONSTANT_FILE . '" not found!');
        }

        header('location: /login');

    });

});

$router->run();