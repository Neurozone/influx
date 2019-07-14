<?php
/*
@name leedStats
@author Cobalt74 <http://www.cobestran.com>
@link http://www.cobestran.com
@licence CC by nc sa http://creativecommons.org/licenses/by-nc-sa/2.0/fr/
@version 1.1.0
@description Permet d'avoir des petites statistiques sur les flux de votre environnement Leed.
*/

function leedStats_plugin_setting_link(&$myUser){
	echo '<li><a class="toggle" href="#leedStatslBloc">'._t('P_LEEDSTATS_TITLE').'</a></li>';
}

function leedStats_plugin_setting_bloc(&$myUser){
	$mysqli = new MysqlEntity();
	$configurationManager = new Configuration();
	$configurationManager->getAll();

    echo '
	<section id="leedStatslBloc" class="leedStatslBloc" style="display:none;">
		<h2>'._t('P_LEEDSTATS_TITLE').'</h2>

		<section class="preferenceBloc">
		<h3>'._t('P_LEEDSTATS_RESUME').'</h3>
	';

    //Nombre global d'article lus / non lus / total / favoris
    $requete = 'SELECT
                (SELECT count(1) FROM `'.MYSQL_PREFIX.'feed`) as nbFeed,
                (SELECT count(1) FROM `'.MYSQL_PREFIX.'event` WHERE unread = 1) as nbUnread,
                (SELECT count(1) FROM `'.MYSQL_PREFIX.'event` WHERE unread = 0) as nbRead,
                (SELECT count(1) FROM `'.MYSQL_PREFIX.'event`) as nbTotal,
                (SELECT count(1) FROM `'.MYSQL_PREFIX.'event` WHERE favorite = 1) as nbFavorite
                ';
    $query = $mysqli->customQuery($requete);
    if($query!=null){
        echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBFEED').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBART').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBART_NONLU').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBART_LU').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBFAV').'</th>
        ';
        while($data = $query->fetch_array()){
            echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">'.$data['nbFeed'].'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['nbTotal'].'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['nbUnread'].'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['nbRead'].'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['nbFavorite'].'</td>
                </tr>
            ';
        }
        echo '</table>
            </div>';
    }
	echo '
            <h3>'._t('P_LEEDSTATS_NBART_BY_FEED_TITLE').'</h3>

    ';
    //Nombre global d'article lus / non lus / total / favoris
    $requete = 'SELECT name, count(1) as nbTotal,
                (SELECT count(1) FROM `'.MYSQL_PREFIX.'event` le2 WHERE le2.unread=1 and le1.feed = le2.feed) as nbUnread,
                (SELECT count(1) FROM `'.MYSQL_PREFIX.'event` le2 WHERE le2.unread=0 and le1.feed = le2.feed) as nbRead,
                (SELECT count(1) FROM `'.MYSQL_PREFIX.'event` le2 WHERE le2.favorite=1 and le1.feed = le2.feed) as nbFavorite
                FROM `'.MYSQL_PREFIX.'feed` lf1
                INNER JOIN `'.MYSQL_PREFIX.'event` le1 on le1.feed = lf1.id
                GROUP BY name
                ORDER BY name
                ';
    $query = $mysqli->customQuery($requete);
    if($query!=null){
        echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_FEED').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBART').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBART_NONLU').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBART_LU').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_NBFAV').'</th>
        ';
        while($data = $query->fetch_array()){
            echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">'.short_name($data['name'],32).'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['nbTotal'].'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['nbUnread'].'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['nbRead'].'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['nbFavorite'].'</td>
                </tr>
            ';
        }
        echo '</table>
            </div>';
    }

    echo '
            <h3>'._t('P_LEEDSTATS_LASTPUB_BY_FEED_TITLE').'</h3>

    ';

    $requete = 'select lf.name, FROM_UNIXTIME(max(le.pubdate)) last_published from leed_feed lf inner join leed_event le on lf.id = le.feed group by lf.name order by 2';
    
    $query = $mysqli->customQuery($requete);
    if($query!=null){
        echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_FEED').'</th>
                        <th class="leedStats_border leedStats_th">'._t('P_LEEDSTATS_LASTPUB').'</th>
        ';
        while($data = $query->fetch_array()){
            echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">'.short_name($data['name'],32).'</td>
                    <td class="leedStats_border leedStats_textright">'.$data['last_published'].'</td>
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
}

function short_name($str, $limit)
{
    // Make sure a small or negative limit doesn't cause a negative length for substr().
    if ($limit < 3)
    {
        $limit = 3;
    }

    // Now truncate the string if it is over the limit.
    if (strlen($str) > $limit)
    {
        return substr($str, 0, $limit - 3) . '...';
    }
    else
    {
        return $str;
    }
}

// Ajout de la fonction au Hook situé avant l'affichage des évenements
$myUser = (isset($_SESSION['currentUser'])?unserialize($_SESSION['currentUser']):false);
if($myUser!=false) {
    Plugin::addHook('setting_post_link', 'leedStats_plugin_setting_link');
    Plugin::addHook('setting_post_section', 'leedStats_plugin_setting_bloc');
}

?>
