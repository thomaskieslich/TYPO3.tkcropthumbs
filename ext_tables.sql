#
# Table structure for table 'tx_tkcropthumbs'
#
CREATE TABLE `tx_tkcropthumbs` (
`id` int(11) unsigned NOT NULL auto_increment,
`image` varchar(255) NOT NULL default '',
`uid` int(11) unsigned NOT NULL default '0',
`x` int(11) unsigned NOT NULL default '0',
`y` int(11) unsigned NOT NULL default '0',
`x2` int(11) NOT NULL default '0',
`y2` int(11) NOT NULL default '0',
`tstamp` int(11) NOT NULL default '0',
PRIMARY KEY  (`id`)
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
tx_tkcropthumbs_aspectratio tinyint(3) DEFAULT '0' NOT NULL
);