<?php

class Functions
{
    private $id;
    public $debug = 0;

    /**
     * Securise la variable utilisateur entrée en parametre
     * @param <String> variable a sécuriser
     * @param <Integer> niveau de securisation
     * @return<String> variable securisée
     * @author Valentin
     */

    public static function secure($var, $level = 1)
    {
        $var = htmlspecialchars($var, ENT_QUOTES, "UTF-8");
        if ($level < 1) $var = mysqli_real_escape_string($var);
        if ($level < 2) $var = addslashes($var);
        return $var;
    }

    /**
     * Convertis la chaine passée en timestamp quel que soit sont format
     * (prend en charge les formats type dd-mm-yyy , dd/mm/yyy, yyyy/mm/ddd...)
     */
    public static function toTime($string)
    {
        $string = str_replace('/', '-', $string);
        $string = str_replace('\\', '-', $string);

        $string = str_replace('Janvier', 'Jan', $string);
        $string = str_replace('Fevrier', 'Feb', $string);
        $string = str_replace('Mars', 'Mar', $string);
        $string = str_replace('Avril', 'Apr', $string);
        $string = str_replace('Mai', 'May', $string);
        $string = str_replace('Juin', 'Jun', $string);
        $string = str_replace('Juillet', 'Jul', $string);
        $string = str_replace('Aout', 'Aug', $string);
        $string = str_replace('Septembre', 'Sept', $string);
        $string = str_replace('Octobre', 'Oct', $string);
        $string = str_replace('Novembre', 'Nov', $string);
        $string = str_replace('Decembre', 'Dec', $string);
        return strtotime($string);
    }

    /**
     * Recupere l'ip de l'internaute courant
     * @return<String> ip de l'utilisateur
     * @author Valentin
     */

    public static function getIP()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Retourne une version tronquée au bout de $limit caracteres de la chaine fournie
     * @param <String> message a tronquer
     * @param <Integer> limite de caracteres
     * @return<String> chaine tronquée
     * @author Valentin
     */
    public static function truncate($msg, $limit)
    {
        $str = html_entity_decode($msg, ENT_QUOTES, 'UTF-8');
        $count = preg_match_all('/\X/u', $str);
        if ($count <= $limit) {
            return $msg;
        }
        $fin = '…';
        $nb = $limit - 1;
        return htmlentities(mb_substr($str, 0, $nb, 'UTF-8') . $fin);
    }


    public static function convertFileSize($bytes)
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


    /** Permet la sortie directe de texte à l'écran, sans tampon.
     * Source : http://php.net/manual/fr/function.flush.php
     */
    public static function triggerDirectOutput()
    {
        // La ligne de commande n'en a pas besoin.
        if ('cli' == php_sapi_name()) return;
        if (function_exists('apache_setenv')) {
            /* Selon l'hébergeur la fonction peut être désactivée. Alors Php
               arrête le programme avec l'erreur :
               "PHP Fatal error:  Call to undefined function apache_setenv()".
            */
            @apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
        for ($i = 0; $i < ob_get_level(); $i++) {
            ob_end_flush();
        }
        ob_implicit_flush(1);
    }

    public static function relativePath($from, $to, $ps = '/')
    {
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo = explode($ps, rtrim($to, $ps));
        while (count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0])) {
            array_shift($arFrom);
            array_shift($arTo);
        }
        return str_pad("", count($arFrom) * 3, '..' . $ps) . implode($ps, $arTo);
    }


    // Nettoyage de l'url avant la mise en base
    public static function clean_url($url)
    {
        $url = str_replace('&amp;', '&', $url);
        return $url;
    }


    /**
     * Méthode de test de connexion.
     * @param server
     * @param login
     * @param pass
     * @param db facultatif, si précisé alors tente de la séléctionner
     * @return true si ok
     */
    public static function testDb($server, $login, $pass, $db = null)
    {
        /* Méthode hors des classes dédiées aux BDD afin de supporter le moins
           de dépendances possibles. En particulier, pas besoin que le fichier
           de configuration existe. */
        $link = mysqli_connect($server, $login, $pass, $db);
        if (false === $link) return false;
        mysqli_close($link);
        return true;
    }


    /**
     * @return boolean
     */
    public static function isAjaxCall()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Charge dans la portée locale des variables de $_REQUEST
     * Ex: chargeVarRequest('liste', 'var') créera $liste et $var venant de $_REQUEST
     */
    public static function chargeVarRequest()
    {
        foreach (func_get_args() as $arg) {
            global ${$arg};
            if (array_key_exists($arg, $_REQUEST)) {
                $valeur = $_REQUEST[$arg];
            } else {
                $valeur = '';
            }
            ${$arg} = $valeur;
        }
    }

    /**
     * Vide le contenu du cache de RainTpl
     *
     */
    public static function purgeRaintplCache()
    {
        $directory = raintpl::$cache_dir;
        if ($directory) {
            $files = glob($directory . '*.rtpl.php');
            if ($files) {
                foreach ($files as $file) {
                    if (!unlink($file)) {
                        error_log("Leed: cannot unlink '$file'");
                    }
                }
            } else {
                error_log('Leed: Raintpl, no file cached: ' . $directory . '   ' . getcwd());
            }
        } else {
            error_log('Leed: Raintpl cache directory not set!');
        }
    }

}

?>
