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

/**
 * @author Florianne
 * @package Influx
 */

namespace Influx\Classes;

class Items
{
    private $guid;
    private $title;
    private $creator;
    private $content;
    private $description;
    private $link;
    private $unread;
    private $lastupdate;
    private $flux;
    private $favorite;
    private $pubdate;
    private $syncId;
    private $logger;
    private $db;

    function __construct($db, $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function countAllUnreadItem()
    {

        $resultsNbUnread = $this->db->query('SELECT count(*) as nb_items from items where unread = 1');
        $numberOfItem = 0;

        while ($rows = $resultsNbUnread->fetch_array()) {
            $numberOfItem = $rows['nb_items'];
        }

        return $numberOfItem;
    }

    public function countUnreadItemPerFlux()
    {

        $resultsNbUnread = $this->db->query('SELECT count(*) as nb_items from items where unread = 1 and fluxId = ' . $this->flux);
        $numberOfItem = 0;

        while ($rows = $resultsNbUnread->fetch_array()) {
            $numberOfItem = $rows['nb_items'];
        }

        return $numberOfItem;

    }

    public function loadAllUnreadItem($offset, $row_count)
    {

        $results = $this->db->query('SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.fluxId,le.favorite,le.pubdate,le.syncId, lf.name as flux_name
        FROM items le inner join flux lf on lf.id = le.fluxId where unread = 1 ORDER BY pubdate desc,unread desc LIMIT  ' . $offset . ',' . $row_count);

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
                'flux' => $rows['fluxId'],
                'favorite' => $rows['favorite'],
                'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
                'syncId' => $rows['syncId'],
                'flux_name' => $rows['flux_name'],
            );

        }

        return $items;

    }

    public function loadUnreadItemPerFlux($offset, $row_count)
    {

        $query = 'SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.fluxId,le.favorite,le.pubdate,le.syncId, lf.name as flux_name
    FROM items le inner join flux lf on lf.id = le.fluxId where le.fluxId = ' . $this->flux . ' ORDER BY unread desc,pubdate desc LIMIT  ' . $offset . ',' . $row_count;

        $results = $this->db->query($query);

        $this->logger->info($query);

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
                'flux' => $rows['fluxId'],
                'favorite' => $rows['favorite'],
                'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
                'syncId' => $rows['syncId'],
                'flux_name' => $rows['flux_name'],
            );

        }

        return $items;

    }

    public function markItemAsReadByGuid()
    {

        $q = "UPDATE items set unread = 0 where guid = '" . $this->getGuid() . "'";

        $this->logger->info($q);

        $return = $this->db->query($q);

        if ($this->db->errno) {
            $this->logger->info("\t\tFailure: \t$this->db->error\n");
            $this->logger->error($q);

            return "\t\tFailure: \t$this->db->error\n";

        }

        return "200";

    }

    public function markAllItemAsRead()
    {

    }

    public function search()
    {
        $search = $this->escape_string($_GET['plugin_search']);
        $requete = "SELECT title,guid,content,description,link,pubdate,unread, favorite FROM items 
            WHERE title like '%" . htmlentities($search) . '%\'  OR content like \'%' . htmlentities($search) . '%\' ORDER BY pubdate desc';
    }

    public function getAllFavorites($offset, $row_count)
    {

        $results = $this->db->query('SELECT '
        . 'le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.fluxId,le.favorite,le.pubdate,le.syncId, lf.name as flux_name '
        . 'FROM items le '
        . 'inner join flux lf on lf.id = le.fluxId where favorite = 1 '
        . 'ORDER BY pubdate desc,unread desc LIMIT   ' . $offset . ', ' . $row_count);

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
                'flux' => $rows['fluxId'],
                'favorite' => $rows['favorite'],
                'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
                'syncId' => $rows['syncId'],
                'flux_name' => $rows['flux_name'],
            );

        }

        return $items;

    }

    public function getNumberOfFavorites()
    {
        $resultsNbFavorites = $this->db->query('SELECT count(*) as nb_items from items where favorite = 1');
        $numberOfItem = 0;

        while ($rows = $resultsNbFavorites->fetch_array()) {
            $numberOfItem = $rows['nb_items'];
        }

        return $numberOfItem;
    }

    /**
     * @return mixed
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * @param mixed $guid
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param mixed $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getUnread()
    {
        return $this->unread;
    }

    /**
     * @param mixed $unread
     */
    public function setUnread($unread)
    {
        $this->unread = $unread;
    }

    /**
     * @return mixed
     */
    public function getLastupdate()
    {
        return $this->lastupdate;
    }

    /**
     * @param mixed $lastupdate
     */
    public function setLastupdate($lastupdate)
    {
        $this->lastupdate = $lastupdate;
    }

    /**
     * @return mixed
     */
    public function getFeed()
    {
        return $this->flux;
    }

    /**
     * @param mixed $flux
     */
    public function setFlux($flux)
    {
        $this->flux = $flux;
    }

    /**
     * @return mixed
     */
    public function getFavorite()
    {
        return $this->favorite;
    }

    /**
     * @param mixed $favorite
     */
    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;
    }

    /**
     * @return mixed
     */
    public function getPubdate()
    {
        return $this->pubdate;
    }

    /**
     * @param mixed $pubdate
     */
    public function setPubdate($pubdate)
    {
        $this->pubdate = $pubdate;
    }

    /**
     * @return mixed
     */
    public function getSyncId()
    {
        return $this->syncId;
    }

    /**
     * @param mixed $syncId
     */
    public function setSyncId($syncId)
    {
        $this->syncId = $syncId;
    }

}