<?php

class translation_main extends sql
{
var $schema = "CREATE TABLE `translation_main` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_history_etat` int(11) NOT NULL,
  `key` char(40) NOT NULL,
  `source` char(5) NOT NULL,
  `destination` char(5) NOT NULL,
  `text` text NOT NULL,
  `date_inserted` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `translate_auto` int(11) NOT NULL,
  `file_found` varchar(255) NOT NULL,
  `line_found` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=802 DEFAULT CHARSET=utf8";

var $field = array("id","id_history_etat","key","source","destination","text","date_inserted","date_updated","translate_auto","file_found","line_found");

var $validate = array(
	'id_history_etat' => array(
		'reference_to' => array('The constraint to history_etat.id isn't respected.','history_etat', 'id')
	),
	'key' => array(
		'not_empty' => array('This field is requiered.')
	),
	'source' => array(
		'not_empty' => array('This field is requiered.')
	),
	'destination' => array(
		'not_empty' => array('This field is requiered.')
	),
	'text' => array(
		'not_empty' => array('This field is requiered.')
	),
	'date_inserted' => array(
		'time' => array('This must be a time.')
	),
	'date_updated' => array(
		'time' => array('This must be a time.')
	),
	'translate_auto' => array(
		'numeric' => array('This must be an int.')
	),
	'file_found' => array(
		'not_empty' => array('This field is requiered.')
	),
	'line_found' => array(
		'numeric' => array('This must be an int.')
	),
);

function get_validate()
{
return $this->validate;
}
}
