<?php

class species_order extends sql
{
var $schema = "CREATE TABLE `species_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_species_class` int(11) NOT NULL,
  `is_valid` int(11) NOT NULL,
  `scientific_name` varchar(50) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scientific_name` (`scientific_name`),
  KEY `FK_id_species_class` (`id_species_class`),
  CONSTRAINT `FK_id_species_class` FOREIGN KEY (`id_species_class`) REFERENCES `species_class` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=593 DEFAULT CHARSET=utf8";

var $field = array("id","id_species_class","is_valid","scientific_name","date_created","date_updated");

var $validate = array(
	'id_species_class' => array(
		'reference_to' => array('The constraint to species_class.id isn't respected.','species_class', 'id')
	),
	'is_valid' => array(
		'numeric' => array('This must be an int.')
	),
	'scientific_name' => array(
		'not_empty' => array('This field is requiered.')
	),
	'date_created' => array(
		'time' => array('This must be a time.')
	),
	'date_updated' => array(
		'time' => array('This must be a time.')
	),
);

function get_validate()
{
return $this->validate;
}
}
