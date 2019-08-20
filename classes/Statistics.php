<?php

/**
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
        $results = $this->db->query('select lf.name, FROM_UNIXTIME(max(le.pubdate)) last_published from flux lf inner join items le on lf.id = le.feed group by lf.name order by 2');

        while ($rows = $results->fetch_array()) {

            $stats[] = array(
                'name' => $rows['name'],
                'last_published' => $rows['last_published'],
            );

        }

        return $stats;
    }

    public function getGlobalStatistics()
    {
        $results = $this->db->query('SELECT
                (SELECT count(1) FROM flux) as nbFlux,
                (SELECT count(1) FROM items WHERE unread = 1) as nbUnread,
                (SELECT count(1) FROM items WHERE unread = 0) as nbRead,
                (SELECT count(1) FROM items) as nbTotal,
                (SELECT count(1) FROM items WHERE favorite = 1) as nbFavorite
                ');

        while ($rows = $results->fetch_array()) {

            $stats[] = array(
                'nbFlux' => $rows['nbFlux'],
                'nbRead' => $rows['nbRead'],
                'nbUnread' => $rows['nbUnread'],
                'nbTotal' => $rows['nbTotal'],
                'nbFavorite' => $rows['nbFavorite'],
            );

        }

        return $stats;
    }

    public function getStatisticsByFeed()
    {
        $results = $this->db->query('SELECT name, 
                count(1) as nbTotal,
                (SELECT count(1) FROM items le2 WHERE le2.unread=1 and le1.flux = le2.flux) as nbUnread,
                (SELECT count(1) FROM items le2 WHERE le2.unread=0 and le1.flux = le2.flux) as nbRead,
                (SELECT count(1) FROM items le2 WHERE le2.favorite=1 and le1.flux = le2.flux) as nbFavorite
                FROM flux lf1
                INNER JOIN items le1 on le1.flux = lf1.id
                GROUP BY name
                ORDER BY name
                ');

        while ($rows = $results->fetch_array()) {

            $stats[] = array(
                'name' => $rows['name'],
                'nbFlux' => $rows['nbFlux'],
                'nbRead' => $rows['nbRead'],
                'nbUnread' => $rows['nbUnread'],
                'nbTotal' => $rows['nbTotal'],
                'nbFavorite' => $rows['nbFavorite'],
            );

        }

        return $stats;
    }

}