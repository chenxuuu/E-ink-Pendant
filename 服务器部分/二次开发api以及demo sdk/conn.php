<?php
$conn = @mysql_connect("127.0.0.1","root","root");
if (!$conn){
	die("连接数据库失败：" . mysql_error());
}
mysql_select_db("wordpress", $conn);

/*
CREATE TABLE `user` (
  `uid` mediumint(8) unsigned NOT NULL auto_increment,
  `username` varchar(16) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `email` varchar(40) NOT NULL default '',
  `regdate` int(10) unsigned NOT NULL default '0',
  `truename` varchar(16) NOT NULL default '',
  `year` varchar(16) NOT NULL default '',
  `learn` varchar(32) NOT NULL default '',
  `work` varchar(255) NOT NULL default '',
  `tel` varchar(32) NOT NULL default '',
  `location` varchar(255) NOT NULL default '',
  `usr_type` varchar(255) NOT NULL default '',
  `eink_imei` varchar(20) NOT NULL default '',
  `eink_set` varchar(20) NOT NULL default '',
  `eink_api` varchar(2048) NOT NULL default '',
  `eink_pic` varchar(12000) NOT NULL default '',
  PRIMARY KEY  (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `e_ink_log` (
  `uid` mediumint(8) unsigned NOT NULL auto_increment,
  `time` varchar(20) NOT NULL default '',
  `imei` varchar(20) NOT NULL default '',
  `set` varchar(20) NOT NULL default '',
  `data` varchar(12000) NOT NULL default '',
  PRIMARY KEY  (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
*/
?>
