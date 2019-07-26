<?php

class Flux
{

    private $id;
    private $name;
    private $description;
    private $website;
    private $url;
    private $lastupdate;
    private $folder;
    private $isverbose;
    private $lastSyncInError;
    private $logger;
    private $db;

    function __construct($db,$logger)
    {
        $this->db = $db;
        $this->db->set_charset('utf8mb4');
        $this->db->query('SET NAMES utf8mb4');
        $this->logger = $logger;
    }

    public function rename()
    {
        $q = "UPDATE flux set name = '" . $this->db->real_escape_string($this->getName()) . "', url = '" . $this->db->real_escape_string($this->getUrl()) . "', lastupdate = " . time() . " where id = " . $this->getId();

        $this->logger->info($q);

        $return = $this->db->query($q);

        if ($this->db->errno) {
            $this->logger->info("\t\tFailure: \t$this->db->error\n");
            $this->logger->error($q);

            return "\t\tFailure: \t$this->db->error\n";

        }

        return "200";
    }

    // @todo
    public function add()
    {
        $q = "UPDATE flux set name = '" . $this->db->real_escape_string($this->getName()) . "', url = '" . $this->db->real_escape_string($this->getUrl()) . "', lastupdate = " . time() . " where id = " . $this->getId();

        $this->logger->info($q);

        $return = $this->db->query($q);

        if ($this->db->errno) {
            $this->logger->info("\t\tFailure: \t$this->db->error\n");
            $this->logger->error($q);

            return "\t\tFailure: \t$this->db->error\n";

        }

        return "200";
    }

    // @todo
    public function remove()
    {
        $q = "UPDATE flux set name = '" . $this->db->real_escape_string($this->getName()) . "', url = '" . $this->db->real_escape_string($this->getUrl()) . "', lastupdate = " . time() . " where id = " . $this->getId();

        $this->logger->info($q);

        $return = $this->db->query($q);

        if ($this->db->errno) {
            $this->logger->info("\t\tFailure: \t$this->db->error\n");
            $this->logger->error($q);

            return "\t\tFailure: \t$this->db->error\n";

        }

        return "200";
    }

    public function loadInfoPerFlux()
    {
        $resultFlux = $this->db->query("select * from flux where id = " . $this->id);
        while ($rows = $resultFlux->fetch_array()) {

            $flux = array(
                'id' => $rows['id'],
                'name' => $rows['name'],
                'description' => $rows['description'],
                'website' => $rows['website'],
                'url' => $rows['url'],
                'lastupdate' => $rows['lastupdate'],
                'folder' => $rows['folder'],
                'isverbose' => $rows['isverbose'],
                'lastSyncInError' => $rows['lastSyncInError'],
            );

        }

        return $flux;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }/**
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
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param mixed $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
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
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param mixed $folder
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
    }

    /**
     * @return mixed
     */
    public function getIsverbose()
    {
        return $this->isverbose;
    }

    /**
     * @param mixed $isverbose
     */
    public function setIsverbose($isverbose)
    {
        $this->isverbose = $isverbose;
    }

    /**
     * @return mixed
     */
    public function getLastSyncInError()
    {
        return $this->lastSyncInError;
    }

    /**
     * @param mixed $lastSyncInError
     */
    public function setLastSyncInError($lastSyncInError)
    {
        $this->lastSyncInError = $lastSyncInError;
    }




}