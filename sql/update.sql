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

-- index flux
KEY `indexfolder` (`folder`),
  KEY `dba_idx_leed_feed_1` (`id`,`name`),
  KEY `dba_idx_leed_feed_2` (`name`,`id`),
  KEY `dba_idx_leed_feed_3` (`name`)