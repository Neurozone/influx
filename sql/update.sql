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