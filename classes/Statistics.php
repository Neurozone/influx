<?php

namespace Influx;

class Statistics
{

    function __construct($db, $logger)
    {
        $this->db = $db;
        $this->db->set_charset('utf8mb4');
        $this->db->query('SET NAMES utf8mb4');
        $this->logger = $logger;
    }

    public function getStatisticsByLastPublished()
    {
        $requete = 'select lf.name, FROM_UNIXTIME(max(le.pubdate)) last_published from flux lf inner join items le on lf.id = le.feed group by lf.name order by 2';
    }

}