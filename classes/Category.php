<?php

namespace Influx;

class Category
{
    private $logger;
    private $db;

    private $id;
    private $name;
    private $parent;
    private $isopen;

    /*
     *
     * | categories | CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(225) NOT NULL,
  `parent` int(11) NOT NULL,
  `isopen` int(11) NOT NULL,
  PRIMARY KEY (`id`)

     */

    public function __construct($db, $logger)
    {

        $this->db = $db;
        $this->db->set_charset('utf8mb4');
        $this->db->query('SET NAMES utf8mb4');
        $this->logger = $logger;

    }

    public function exist()
    {

        $cn = 0;

        $resultFlux = $this->db->query("select count(name) as cn from categories where id = " . $this->getId());
        while ($rows = $resultFlux->fetch_array()) {
            $cn = $rows['cn'];
        }

        if ($this->db->errno) {
            $this->logger->info("\t\tFailure: \t$this->db->error\n");
            $this->logger->error($q);

            return "\t\tFailure: \t$this->db->error\n";

        }

        if ($cn > 0) {
            return true;
        } else {
            return false;
        }

    }

    public function add()
    {

        $q = "INSERT INTO categories(name, parent, isopen) VALUES ('" . $this->getName() . "', -1,1)";

        $this->logger->info($q);

        $this->db->query($q);

        if ($this->db->errno) {
            $this->logger->info("\t\tFailure: \t$this->db->error\n");
            $this->logger->error($q);

            return "\t\tFailure: \t$this->db->error\n";

        }

        return "200";
    }

    public function rename()
    {
        $q = "UPDATE categories set name = '" . $this->db->real_escape_string($this->getName()) . "' where id = " . $this->getId();

        $this->logger->info($q);

        $this->db->query($q);

        if ($this->db->errno) {
            $this->logger->info("\t\tFailure: \t$this->db->error\n");
            $this->logger->error($q);

            return "\t\tFailure: \t$this->db->error\n";

        }

        return "200";
    }

    public function getCategoryById()
    {
        $categories = array();

        $query_cat = 'select * from categories where id = ' . $this->id;
        $result_cat = $this->db->query($query_cat);

        while ($row = $result_cat->fetch_array()) {
            $categories[] = array('id' => $row['id'], 'name' => $row['name'], 'parent' => $row['parent'], 'isopen' => $row['isopen'],);
        }

        return $categories;
    }

    public function getFluxByCategories()
    {
        $results = $this->db->query('SELECT * FROM categories c ORDER BY name ');
        while ($rows = $results->fetch_array()) {

            $resultsUnreadByFolder = $this->db->query('SELECT count(*) as unread
            FROM items le 
                inner join flux lfe on le.fluxId = lfe.id 
                inner join categories lfo on lfe.categoryId = lfo.id  
            where unread = 1 and lfo.id = ' . $rows['id']);

            while ($rowsUnreadByFolder = $resultsUnreadByFolder->fetch_array()) {
                $unreadEventsByFolder = $rowsUnreadByFolder['unread'];
            }

            $resultsFluxByFolder = $this->db->query('SELECT fe.id as feed_id, fe.name as feed_name, fe.description as feed_description, fe.website as feed_website, fe.url as feed_url, fe.lastupdate as feed_lastupdate, fe.categoryId as feed_folder,fe.lastSyncInError as feed_lastSyncInError 
            FROM categories f 
                inner join flux fe on fe.categoryId = f.id 
            where f.id = ' . $rows['id'] . " order by fe.name");


            while ($rowsFluxByFolder = $resultsFluxByFolder->fetch_array()) {

                $resultsUnreadByFeed = $this->db->query('SELECT count(*) as unread FROM categories f inner join flux fe on fe.categoryId = f.id 
                inner join items e on e.fluxId = fe.id  where e.unread = 1 and fe.id = ' . $rowsFluxByFolder['feed_id']);

                $unreadEventsByFeed = 0;

                while ($rowsUnreadByFeed = $resultsUnreadByFeed->fetch_array()) {
                    $unreadEventsByFeed = $rowsUnreadByFeed['unread'];
                }

                $fluxByCategories[] = array(
                    'id' => $rowsFluxByFolder['feed_id'],
                    'name' => $rowsFluxByFolder['feed_name'],
                    'description' => $rowsFluxByFolder['feed_description'],
                    'website' => $rowsFluxByFolder['feed_website'],
                    'url' => $rowsFluxByFolder['feed_url'],
                    'lastupdate' => $rowsFluxByFolder['feed_lastupdate'],
                    'folder' => $rowsFluxByFolder['feed_folder'],
                    'lastSyncInError' => $rowsFluxByFolder['feed_lastSyncInError'],
                    'unread' => $unreadEventsByFeed
                );
            }

            $categories[] = array(
                'id' => $rows['id'],
                'name' => $rows['name'],
                'parent' => $rows['parent'],
                'isopen' => $rows['isopen'],
                'unread' => $unreadEventsByFolder,
                'flux' => $fluxByCategories
            );

            $fluxByCategories = null;
        }

        return $categories;
    }

    public function getAll()
    {
        $categories = array();

        $query_cat = 'select * from categories';
        $result_cat = $this->db->query($query_cat);

        while ($row = $result_cat->fetch_array()) {
            $categories[] = array('id' => $row['id'], 'name' => $row['name'], 'parent' => $row['parent'], 'isopen' => $row['isopen'],);
        }

        return $categories;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getIsopen()
    {
        return $this->isopen;
    }

    /**
     * @param mixed $isopen
     */
    public function setIsopen($isopen)
    {
        $this->isopen = $isopen;
    }


}