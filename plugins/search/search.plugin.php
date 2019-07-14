<?php
/*
@name search
@author Cobalt74 <http://www.cobestran.com>
@link http://www.cobestran.com
@licence CC by nc sa http://creativecommons.org/licenses/by-nc-sa/2.0/fr/
@version 2.3.1
@description Le plugin search permet d'effectuer et de sauvegarder une recherche sur les articles de Leed. Ne perdez plus aucune information et sauvegardez vos recherches fréquentes !
*/


require 'classes/leedsearch.php';
// affichage d'un lien dans le menu "Gestion"
function search_plugin_AddLink_and_Search(){
	echo '<li><a class="toggle" href="#search">'._t('P_SEARCH_TITLE').'</a></li>';
}

// affichage d'un formulaire de recherche dans la barre de menu
function search_plugin_menuForm(){
        global $theme;
        $tag = $theme === 'marigolds' ? 'aside' : 'section';
        $leedSearch = new LeedSearch();
        $leedSearch->action();
	echo '<' . $tag . ' class="searchMenu">
			    <form action="settings.php#search" method="get">
					<input type="text" name="plugin_search" id="plugin_search" placeholder="..." value="'. $leedSearch->current .'">
					<button type="submit">'._t('P_SEARCH_BTN').'</button>
				</form>';
        $searches = $leedSearch->getSearchNames();
        if(!empty($searches)) {
            echo '<ul>';
            foreach( $searches as $search ) {
                echo '<li><a href="settings.php?plugin_search=' . $search['formatted'] . '&search_option=0&search_show=0#search">' . $search['name'] . '</a>';
                if($search['count'] > 0) {
                    echo '<span class="button">' . $search['count'] . '</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }
	echo '</' . $tag . '>';
}

// affichage des option de recherche et du formulaire
function search_plugin_AddForm(){
        $leedSearch = new LeedSearch();
	echo '<section id="search" name="search" class="search">
			<h2>'._t('P_SEARCH_TITLE_FULL').'</h2>
			<form action="settings.php#search" method="get">
				<input type="text" name="plugin_search" id="plugin_search" placeholder="..." value="'.$leedSearch->current.'">
				<span>'._t('P_SEARCH_WARN_CAR').'</span>';
        if($leedSearch->isSearching) {
            $saveSearchButtonInfos = $leedSearch->getSaveToggleButtonInfos();
            echo '<button type="submit" name="' . $saveSearchButtonInfos['name'] . '">' . $saveSearchButtonInfos['text'] . '</button>';
        }
        echo '<fieldset>
                <legend>'._t('P_SEARCH_OPT_SEARCH').'</legend>';
	if (!isset($_GET['search_option']) ? $search_option=0 : $search_option=$_GET['search_option']);
	if($search_option==0) {
		echo '      <input type="radio" checked="checked" value="0" id="search_option_title" name="search_option"><label for="search_option_title">'._t('P_SEARCH_OPT_TITLE').'</label>
					<input type="radio" value="1" id="search_option_content" name="search_option"><label for="search_option_content">'._t('P_SEARCH_OPT_CONTENT').'</label>';
	} else {
		echo '		<input type="radio" value="0" id="search_option_title" name="search_option"><label for="search_option_title">'._t('P_SEARCH_OPT_TITLE').'</label>
					<input type="radio" checked="checked" value="1" id="search_option_content" name="search_option"><label for="search_option_content">'._t('P_SEARCH_OPT_CONTENT').'</label>';
	}
	echo '      </fieldset>
				<fieldset>
					<legend>'._t('P_SEARCH_RES_SEARCH').'</legend>';
	if (!isset($_GET['search_show']) ? $search_show=0 : $search_show=$_GET['search_show']);
	if($search_show==0) {
		echo '      <input type="radio" checked="checked" value="0" id="search_show_title" name="search_show"><label for="search_show_title">'._t('P_SEARCH_OPT_TITLE').'</label>
					<input type="radio" value="1" id="search_show_content" name="search_show"><label for="search_show_content">'._t('P_SEARCH_OPT_CONTENT').'</label>';
	} else {	
		echo '		<input type="radio" value="0" id="search_show_title" name="search_show"><label for="search_show_title">'._t('P_SEARCH_OPT_TITLE').'</label>
					<input type="radio" checked="checked" value="1" id="search_show_content" name="search_show"><label for="search_show_content">'._t('P_SEARCH_OPT_CONTENT').'</label>';
	}
	echo '			</fieldset>
				<button type="submit" name="search-launch">'._t('P_SEARCH_BTN').'</button>
			</form>';
    if($leedSearch->isSearching){
        if(strlen($leedSearch->current)>=3){
                    $datas = $leedSearch->search();
                    if($datas !== false) {
                        echo '<div id="result_search" class="result_search">';
                        while($data = $datas->fetch_array()){
                                echo '<div class=search_article>
                                        <div class="search_article_title">
                                          <div class="search_buttonbBar">
                                                <span ';
                                                if(!$data['unread']){
                                                        echo 'class="pointer right readUnreadButton eventRead"';
                                                }
                                                else {
                                                        echo 'class="pointer right readUnreadButton"';
                                                }
                                                echo ' onclick="search_readUnread(this,'.$data['id'].');">'.(!$data['unread']?_t('P_SEARCH_BTN_NONLU'):_t('P_SEARCH_BTN_LU')).'</span>
                                                <span ';
                                                if($data['favorite']){
                                                        echo 'class="pointer right readUnreadButton eventFavorite"';
                                                }
                                                else {
                                                        echo 'class="pointer right readUnreadButton"';
                                                }
                                                echo ' onclick="search_favorize(this,'.$data['id'].');">'.(!$data['favorite']?_t('P_SEARCH_BTN_FAVORIZE'):_t('P_SEARCH_BTN_UNFAVORIZE')).'</span>';
                                echo '	</div>'.
                                        date('d/m/Y à H:i',$data['pubdate']).
                                        ' - <a title="'.$data['guid'].'" href="'.$data['link'].'" target="_blank">
                                                     '.$data['title'].'</a>
                                                </div>';
                                if (isset($_GET['search_show']) && $_GET['search_show']=="1"){
                                        echo '<div class="search_article_content">
                                                     '.$data['content'].'
                                             </div>';
                                }
                                echo '</div>';
                        }
                        echo '</div>';
                    }
		}else{ echo _t('P_SEARCH_WARN_CAR_FULL'); }
	}
	echo '</section>';
}


Plugin::addJs("/js/search.js");

// Ajout de la fonction au Hook situé avant l'affichage des évenements
Plugin::addHook("setting_post_link", "search_plugin_AddLink_and_Search");
Plugin::addHook("setting_post_link", "search_plugin_menuForm");
Plugin::addHook("setting_post_section", "search_plugin_AddForm");
//Ajout de la fonction au Hook situé après le menu des fluxs
Plugin::addHook("menu_pre_folder_menu", "search_plugin_menuForm");
?>
