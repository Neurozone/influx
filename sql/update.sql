alter table items drop column id;

DELETE t1 FROM items t1
INNER JOIN items t2
WHERE t1.pubdate < t2.pubdate AND t1.guid = t2.guid;

ALTER TABLE items ADD PRIMARY KEY(guid);
ALTER TABLE flux ADD PRIMARY KEY(id);

ALTER TABLE flux MODIFY COLUMN id INT(11) auto_increment;

create index idx_flux_pubdate on items(pubdate);
create index idx_flux_guid on items(guid);

DELETE t1 FROM leed_event t1
INNER JOIN leed_event t2
WHERE t1.id < t2.id AND t1.guid = t2.guid;

select count(guid), guid from items group by guid having count(guid) > 1 order by 1;
select count(guid), guid from leed_event group by guid having count(guid) > 1 order by 1;

CREATE TABLE `configuration` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
       `name` varchar(255) NOT NULL,
      `value` text NOT NULL,
       PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into configuration (name, value) select `key`,`value` from conf;

alter table user add column email varchar(225) NOT NULL AFTER password;
alter table user add column salt varchar(225) NOT NULL AFTER email;

alter table configuration add column userId int(11) NOT NULL AFTER id;
update configuration set userId = 1 ;

alter table items add column lastUpdate int(11) AFTER pubdate;

alter table categories add column userId int(11) NOT NULL AFTER id;
update categories set userId = 1 ;

| flux  | CREATE TABLE `flux` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `name` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                                  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                                  `website` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                                  `url` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                                  `lastupdate` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                                  `folder` int(11) NOT NULL,
                                  `isverbose` int(1) NOT NULL,
                                  `lastSyncInError` int(1) NOT NULL DEFAULT 0,
                                  PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=434 DEFAULT CHARSET=utf8mb4 |

alter table flux change folder categoriesId;
alter table items change feed fluxId;

sans cache index
    +------+-------------+-------+--------+---------------+------------------+---------+----------------+------+-------------+
    | id   | select_type | table | type   | possible_keys | key              | key_len | ref            | rows | Extra       |
    +------+-------------+-------+--------+---------------+------------------+---------+----------------+------+-------------+
    |    1 | SIMPLE      | le    | index  | NULL          | idx_flux_pubdate | 6       | NULL           |   25 | Using where |
    |    1 | SIMPLE      | lf    | eq_ref | PRIMARY       | PRIMARY          | 4       | influx.le.feed |    1 |             |
    +------+-------------+-------+--------+---------------+------------------+---------+----------------+------+-------------+

    avec

    MariaDB [influx]> create index idx_items_fluxId on items(feed);
Query OK, 0 rows affected (10.209 sec)
Records: 0  Duplicates: 0  Warnings: 0

MariaDB [influx]> create index idx_items_fluxId_unread on items(feed,unread);
Query OK, 0 rows affected (11.042 sec)
Records: 0  Duplicates: 0  Warnings: 0

MariaDB [influx]> create index idx_items_fluxId_unread_2 on items(pubdate,feed,unread);
Query OK, 0 rows affected (11.293 sec)
Records: 0  Duplicates: 0  Warnings: 0

MariaDB [influx]> explain SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name     FROM items le inner join flux lf on lf.id = le.feed where unread = 1 ORDER BY pubdate desc,unread desc LIMIT 0,25;
+------+-------------+-------+------+------------------------------------------+-------------------------+---------+--------------------+------+---------------------------------+
| id   | select_type | table | type | possible_keys                            | key                     | key_len | ref                | rows | Extra                           |
+------+-------------+-------+------+------------------------------------------+-------------------------+---------+--------------------+------+---------------------------------+
|    1 | SIMPLE      | lf    | ALL  | PRIMARY                                  | NULL                    | NULL    | NULL               |  196 | Using temporary; Using filesort |
|    1 | SIMPLE      | le    | ref  | idx_items_fluxId,idx_items_fluxId_unread | idx_items_fluxId_unread | 8       | influx.lf.id,const | 1090 |                                 |
+------+-------------+-------+------+------------------------------------------+-------------------------+---------+--------------------+------+---------------------------------+
2 rows in set (0.000 sec)
