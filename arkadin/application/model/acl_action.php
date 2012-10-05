<?php

class acl_action extends sql
{

 
	// Nous donnons donc � Gliale la structure d'un enregistrement
	var $schema = "CREATE TABLE `UserMain` (
 `Id` int(11) NOT NULL auto_increment,
 `IsValid` int(11) NOT NULL,
 `Login` varchar(50) NOT NULL,
 `Email` varchar(50) NOT NULL,
 `Password` varchar(20) NOT NULL,
 `Name` varchar(40) NOT NULL,
 `Firstname` varchar(40) NOT NULL,
 `IP` varchar(15) NOT NULL,
 `CountryIP` char(2) NOT NULL,
 `Points` int(11) NOT NULL,
 `LastLogin` datetime NOT NULL,
 `LastConnected` datetime NOT NULL,
 `LastJoined` datetime NOT NULL,
 `KeyAuth` char(32) NOT NULL,
 PRIMARY KEY  (`Id`),
 UNIQUE KEY `email` (`Email`),
 UNIQUE KEY `login` (`Login`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8";
 
	// R�gles de validation des donn�es
	
	var $field = array();
	
	var $validate = array(
	
	
		'name' => array(
			'not_empty' => array("L'email doit �tre renseign�.")
		),
		
		'id_acl_controller' => array(
			'reference_to' => array("Please select your city","acl_controller", "id")
		),
		
	);
	


	
	function get_validate()
	{
		return $this->validate;
	}
	
}




?>