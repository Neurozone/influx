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

class Opml
{

    public function __construct($db, $logger)
    {

        $this->db = $db;
        $this->db->set_charset('utf8mb4');
        $this->db->query('SET NAMES utf8mb4');
        $this->logger = $logger;

    }

    public function export()
    {

        $qCategories = 'select id, name from categories order by name';

        // b>Warning</b>:  Illegal string offset 'Amis' in <b>/data/www/influx.neurozone.fr/classes/Opml.php</b> on line <b>45</b><br />

        $outlines = array();
        $resultCat = $this->db->query($qCategories);

        while ($rows = $resultCat->fetch_array()) {
            $catId = $rows['id'];
            $catName = $rows['name'];

            $qFlux = 'SELECT url, website, name, description FROM flux WHERE  folder = ' . $catId . ' order by name';
            $this->logger->error($qFlux);

            $resultFlux = $this->db->query($qFlux);

            while ($rowsFlux = $resultFlux->fetch_array()) {

                $fluxUrl = $rowsFlux['url'];
                $fluxWebsite = $rowsFlux['website'];
                $fluxTitle = $rowsFlux['name'];
                $fluxDescription = $rowsFlux['description'];

                $this->logger->info($catName,array('url' => $fluxUrl, 'website' => $fluxWebsite, 'title' => $fluxTitle, 'description' => $fluxDescription));

                $outlines[$catName][] = array('url' => $fluxUrl, 'website' => $fluxWebsite, 'title' => $fluxTitle, 'description' => $fluxDescription);

            }

        }

        $version = '1.0';
        $encoding = 'utf-8';
        $rootName = 'root';

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument($version, $encoding);

        $xml->startElement('opml');
        $xml->writeAttribute('version', '2.0');

            $xml->startElement('head');

                $xml->writeElement("title", 'Export InFlux');
                $xml->writeElement("ownerName", 'InFlux');
                $xml->writeElement("dateCreated", date('Y-m-d'));

            $xml->endElement();

        $xml->startElement('body');

        //var_dump($outlines);

        foreach($outlines as $k => $cat)
        {

            // xml:opml:body:outline
            $xml->startElement('outline');
            $xml->writeAttribute('text', $k);

            //$this->logger->info($cat);

            foreach($cat as $l => $outline)
            {




                // xml:opml:body:outline:outline
                $xml->startElement("outline");

                $xml->writeAttribute('type', 'rss');
                $xml->writeAttribute('xmlUrl', $outline['url']);
                $xml->writeAttribute('htmlUrl', $outline['website']);
                $xml->writeAttribute('text', $outline['title']);
                $xml->writeAttribute('title', $outline['title']);
                $xml->writeAttribute('description', $outline['description']);

                // fin xml:opml:body:outline:outline
                $xml->endElement();


            }

            // fin xml:opml:body:outline
            $xml->endElement();


        }


        // end body
        $xml->endElement();

        // end opml
        $xml->endElement();


        echo $xml->outputMemory();
        
    }
}
