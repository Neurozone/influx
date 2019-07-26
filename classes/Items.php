<?php

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
    private $feed;
    private $favorite;
    private $pubdate;
    private $syncId;
    private $logger;
    private $db;

    function __construct($db, $logger)
    {
        $this->db = $db;
        $this->db->set_charset('utf8mb4');
        $this->db->query('SET NAMES utf8mb4');
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

        $resultsNbUnread = $this->db->query('SELECT count(*) as nb_items from items where unread = 1 and feed = ' . $this->feed);
        $numberOfItem = 0;

        while ($rows = $resultsNbUnread->fetch_array()) {
            $numberOfItem = $rows['nb_items'];
        }

        return $numberOfItem;

    }

    public function loadAllUnreadItem($offset, $row_count)
    {

        //$results = $this->db->query('SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    //FROM items le inner join flux lf on lf.id = le.feed where unread = 1 ORDER BY pubdate desc,unread desc LIMIT  ' . $offset . ',' . $row_count);

        $results = $this->db->query('SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM items le inner join flux lf on lf.id = le.feed where unread = 1 ORDER BY pubdate desc,unread desc LIMIT  ' . $offset . ',' . $row_count);

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
                'feed' => $rows['feed'],
                'favorite' => $rows['favorite'],
                'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
                'syncId' => $rows['syncId'],
                'feed_name' => $rows['feed_name'],
            );

        }

        return $items;

    }

    public function loadUnreadItemPerFlux($offset, $row_count)
    {

        $results = $this->db->query('SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM items le inner join flux lf on lf.id = le.feed where le.feed = ' . $this->feed . ' ORDER BY pubdate desc,unread desc LIMIT  ' . $offset . ',' . $row_count);

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
                'feed' => $rows['feed'],
                'favorite' => $rows['favorite'],
                'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
                'syncId' => $rows['syncId'],
                'feed_name' => $rows['feed_name'],
            );

        }

        return $items;

    }

    public function markItemAsRead()
    {

    }

    public function markAllItemAsRead()
    {

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
        return $this->feed;
    }

    /**
     * @param mixed $feed
     */
    public function setFeed($feed)
    {
        $this->feed = $feed;
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