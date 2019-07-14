<?php

class LeedSearch extends MysqlEntity {

    protected $TABLE_NAME = 'plugin_search';

    public $isSearching = false;
    public $current = "";

    const ACTION_NAMES = array(
        'add' => 'search-add',
        'remove' => 'search-remove'
    );

    public function __construct() {
        parent::__construct();
        $this->setIsSearching();
    }

    public function getSearchNames() {
        $results = $this->dbconnector->connection->query('
            SELECT search FROM `' . MYSQL_PREFIX . $this->TABLE_NAME . '`
        ');

        if($results->num_rows === 0) {
            return array();
        }
        $rows = $results->fetch_all();
        $this->searches = array_map(array( $this, 'formatSearch'), $rows);
        return $this->searches;
    }

    public function search() {
        if(!isset($_GET['plugin_search'])) {
            return false;
        }
        $search = $this->escape_string($_GET['plugin_search']);
        $requete = 'SELECT id,title,guid,content,description,link,pubdate,unread, favorite
            FROM `'.MYSQL_PREFIX.'event`
            WHERE title like \'%'.htmlentities($search).'%\'';
        if (isset($_GET['search_option']) && $_GET['search_option']=="1"){
            $requete = $requete.' OR content like \'%'.htmlentities($search).'%\'';
        }
        $requete = $requete.' ORDER BY pubdate desc';
        return $this->customQuery($requete);
    }

    public function setIsSearching() {
        if(!isset($_GET['plugin_search']) || $_GET['plugin_search'] === "") {
            return false;
        }
        $search = trim(htmlentities($_GET['plugin_search']));
        $this->isSearching = true;
        $this->current = $search;
    }

    public function isSearchExists($search) {
        $query = 'SELECT search
            FROM `'. MYSQL_PREFIX . $this->TABLE_NAME . '`
            WHERE search like \'%' . str_replace( '%', ' ' , $search ) . '%\'';
        $result = $this->customQuery($query);
        return !!$result->num_rows;
    }

    public function action() {
        if($this->current === "") {
            return false;
        }
        if(isset($_GET[self::ACTION_NAMES['add']])) {
            $this->saveSearch();
        } elseif(isset($_GET[self::ACTION_NAMES['remove']])) {
            $this->removeSearch();
        }
    }

    protected function saveSearch() {
        $request = 'INSERT INTO `' . MYSQL_PREFIX . $this->TABLE_NAME . '`
            (search) VALUES ("' . $this->current . '")';
        $result = $this->customQuery($request);
        // @TODO must return error or already known value message
    }

    protected function removeSearch() {
        $request = 'DELETE FROM `' . MYSQL_PREFIX . $this->TABLE_NAME . '`
            WHERE search="' . $this->current . '"';
        $result = $this->customQuery($request);
        // @TODO must return error or already known value message
    }

    public function getSaveToggleButtonInfos() {
        if($this->isSearchExists($this->current)) {
            return array(
                'name' => self::ACTION_NAMES['remove'],
                'text' => _t('P_SEARCH_REMOVE')
            );
        }
        return array(
            'name' => self::ACTION_NAMES['add'],
            'text' => _t('P_SEARCH_SAVE')
        );
    }

    protected function getSearchCount($search) {
        $search = '+' . $search;
        $searchCountQuery = 'SELECT COUNT(*)
                FROM `'.MYSQL_PREFIX.'event`
                WHERE MATCH(title) AGAINST("'.str_replace("%", " +", $search).'" IN BOOLEAN MODE) AND unread=1';
	$query = $this->customQuery($searchCountQuery);
        $row = $query->fetch_row();
        return $row[0];
    }

    protected function formatSearch($searchRow) {
        $search = $searchRow[0];
        $formatted = $this->escape_string($search);
        $count = $this->getSearchCount($this->escape_string(str_replace(' ', '%', $search)));
        return array(
            'name' => $search,
            'formatted' => $formatted,
            'count' => $count
        );
    }

    public function install() {
        $query = '
            CREATE TABLE IF NOT EXISTS `' . MYSQL_PREFIX . $this->TABLE_NAME . '` (
              `search` varchar(255) NOT NULL,
              PRIMARY KEY (`search`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        ';
        $this->dbconnector->connection->query($query);
        $query = 'ALTER TABLE `' . MYSQL_PREFIX . 'event` ADD FULLTEXT(title)';
        $this->dbconnector->connection->query($query);
    }

    public function uninstall() {
        $this->destroy();
        $query = 'DROP INDEX title ON `' . MYSQL_PREFIX . 'event`;';
        $this->dbconnector->connection->query($query);
    }

}
