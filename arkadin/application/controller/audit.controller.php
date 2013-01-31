<?php

define('AUDIT_ELEM_PER_PAGE', 50);
define('AUDIT_NB_PAGE_TO_DISPLAY_MAX', 10);
define('ID_RUN', 2);

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class audit extends controller
{

	function index()
	{

		$this->layout_name = "admin";
		$this->title = "Audit query capgemini";
		$this->ariane = "> " . $this->title;

		$data = array();
		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "select * from cash_audit_main order by `table`";

		$res = $_SQL->sql_query($sql);
		$data['audit'] = $_SQL->sql_to_array($res);

		$this->set('data', $data);
	}

	function readdir($dir)
	{
		if ( is_dir($dir) )
		{
			if ( $dh = opendir($dir) )
			{
				while ( ($file = readdir($dh)) !== false )
				{
//echo "filename: $file : filetype: " . filetype($dir . $file) . "\n";
//echo "filename: " . $file . "\n";

					echo "gggg" . $dir . $file . "---------------\n";

					if ( is_dir($dir . $file) )
					{
						echo $file . " => directory !\n";
					}
					else
					{
						echo $file . " => file !\n";
					}
				}
				closedir($dh);
			}
		}
	}

	/**
	 * directory_list
	 * return an array containing optionally all files, only directiories or only files at a file system path
	 * @author     cgray The Metamedia Corporation www.metamedia.us
	 *
	 * @param    $base_path         string    either absolute or relative path
	 * @param    $filter_dir        boolean    Filter directories from result (ignored except in last directory if $recursive is true)
	 * @param    $filter_files    boolean    Filter files from result
	 * @param    $exclude        string    Pipe delimited string of files to always ignore
	 * @param    $recursive        boolean    Descend directory to the bottom?
	 * @return    $result_list    array    Nested array or false
	 * @access public
	 * @license    GPL v3
	 */
	function directory_list($directory_base_path, $filter_dir = false, $filter_files = false, $exclude = ".|..|.DS_Store|.svn", $recursive = true)
	{
		$directory_base_path = rtrim($directory_base_path, "/") . "/";

		if ( !is_dir($directory_base_path) )
		{
			error_log(__FUNCTION__ . "File at: $directory_base_path is not a directory.");
			return false;
		}

		$result_list = array();
		$exclude_array = explode("|", $exclude);

		if ( !$folder_handle = opendir($directory_base_path) )
		{
			error_log(__FUNCTION__ . "Could not open directory at: $directory_base_path");
			return false;
		}
		else
		{
			while ( false !== ($filename = readdir($folder_handle)) )
			{
				if ( !in_array($filename, $exclude_array) )
				{
					if ( is_dir($directory_base_path . $filename . "/") )
					{
						if ( $recursive && strcmp($filename, ".") != 0 && strcmp($filename, "..") != 0 )
						{ // prevent infinite recursion
							error_log($directory_base_path . $filename . "/");
							$result_list[$filename] = $this->directory_list("$directory_base_path$filename/", $filter_dir, $filter_files, $exclude, $recursive);
						}
						elseif ( !$filter_dir )
						{
							$result_list[] = $filename;
						}
					}
					elseif ( !$filter_files )
					{
						$result_list[] = $filename;
					}
				}
			}
			closedir($folder_handle);
			return $result_list;
		}
	}

	function create_table()
	{

		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);

		$i = 0;
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{

			$i++;
			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");

			$sql2 = "SELECT [name],  [xtype]  FROM [ARKADIN_AUDIT].[dbo].[sysobjects] WHERE xtype='U' order by name";

			$res2 = mssql_query($sql2);

			while ( $ob2 = mssql_fetch_object($res2) )
			{

				if ( !strstr($ob2->name, "-") )
				{
					continue;
				}

				$sql4 = "CREATE TABLE IF NOT EXISTS `capgemini_audit_" . $ob2->name . "` (\n";
				$sql4 .= "`id` int(11) NOT NULL AUTO_INCREMENT,\n";

				$sql3 = "SELECT   COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT,IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
                            FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_NAME='" . $ob2->name . "'";
				$res3 = mssql_query($sql3);

				while ( $ob3 = mssql_fetch_object($res3) )
				{
					if ( in_array($ob3->COLUMN_NAME, array('IsCorrected', 'CorrectionComment')) )
					{
						continue;
					}

					switch ( $ob3->DATA_TYPE )
					{
						case 'int':
						case 'bit':
							$sql4 .= "`_" . $ob3->COLUMN_NAME . "` int(11) NULL";
//$sql4 .= "`" . $ob3->COLUMN_NAME . "` varchar(100)";
							break;

						case 'nvarchar':
						case 'varchar':
							$sql4 .= "`_" . $ob3->COLUMN_NAME . "` varchar(" . $ob3->CHARACTER_MAXIMUM_LENGTH . ")";
							break;

						case 'datetime':
							$sql4 .= "`_" . $ob3->COLUMN_NAME . "` datetime";
							break;

						case 'nchar':
						case 'char':
							$sql4 .= "`_" . $ob3->COLUMN_NAME . "` char(" . $ob3->CHARACTER_MAXIMUM_LENGTH . ")";
							break;

						case 'decimal':
							$sql4 .= "`_" . $ob3->COLUMN_NAME . "` float(11) NULL";

							break;


						default:
							die("add : " . $ob3->DATA_TYPE);
							break;
					}
					$sql4 .= ",\n";
				}

//$sql4 = substr($sql4,0,-2);
				$sql4 .= "functional_key char(40),\n";
				$sql4 .= "PRIMARY KEY (`id`),\n";
				$sql4 .= "UNIQUE KEY (`_ID_RUN`,`functional_key`),\n";
				$sql4 .= "index `functional_key` (`functional_key`)\n";
				$sql4 .= ") ENGINE=MyIsam DEFAULT CHARSET=utf8\n";



				$_SQL->sql_query($sql4);

				echo $sql4;

//echo $ob2->name . "\n";
			}
		}
	}

	function extract_data()
	{

		$this->view = false;
		$this->layout_name = false;


		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{


			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");

			$sql2 = "SELECT [name], [xtype]  FROM [ARKADIN_AUDIT].[dbo].[sysobjects] WHERE xtype='U' order by name desc";

			$res2 = mssql_query($sql2);

			while ( $ob2 = mssql_fetch_object($res2) )
			{
				if ( !strstr($ob2->name, "-") )
				{
					continue;
				}


				$sql = "SELECT count(1) FROM ARKADIN_AUDIT..[" . $ob2->name . "]";





				echo "table : " . $ob2->name . "\n";

				$cmd = "freebcp ARKADIN_AUDIT..[" . $ob2->name . "] out '" . $ob2->name . ".txt' -S 10.102.28.5 -U sa -P 3fN4uL -c -t'`|' -r'||\n'";

				shell_exec($cmd);
			}
		}
	}

	function import_data()
	{
		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);

		$i = 0;
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{

			$i++;
			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");

			$sql2 = "SELECT [name], [xtype]  FROM [ARKADIN_AUDIT].[dbo].[sysobjects] WHERE xtype='U' order by name desc";

			$res2 = mssql_query($sql2);

			while ( $ob2 = mssql_fetch_object($res2) )
			{

				if ( !strstr($ob2->name, "-") )
				{
					continue;
				}

				echo "table : " . $ob2->name . "\n";

				$sql3 = "SELECT * FROM [" . $ob2->name . "]";
				$res3 = mssql_query($sql3);

				$j = 0;
				while ( $tab3 = mssql_fetch_array($res3, MSSQL_ASSOC) )
				{


					if ( $j % 1000 == 0 )
					{
						echo "line : " . $j . "\n";
					}
					$j++;

					$data = array();
					$data["capgemini_audit_" . $ob2->name] = $tab3;

					unset($tab3['OID']);
					unset($tab3['ID_RUN']);

					$key = sha1(json_encode($tab3));
					$data["capgemini_audit_" . $ob2->name]['functional_key'] = $key;

					if ( !$_SQL->sql_save($data) )
					{
						debug($data);
						debug($_SQL->sql_error());
						die("no good !");
					}
				}
			}
		}
	}

	//deprecated
	function cleanup()
	{
		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SHOW TABLES WHERE Tables_in_arkadin like 'capgemini_audit_%'";

		$res = $_SQL->sql_query($sql);

		while ( $ob = $_SQL->sql_fetch_object($res) )
		{
			$sql2 = "DROP TABLE `" . $ob->Tables_in_arkadin . "`";
			$_SQL->sql_query($sql2);
		}
	}

//deprecated
	function import()
	{
		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);
		$directory_base_path = "/home/www/arkadin/webroot/";

		$files = $this->directory_list($directory_base_path, true, false, ".|.php", false);

		foreach ( $files as $file )
		{
			if ( !strstr($file, ".txt") )
			{
				continue;
			}

			echo "currently insert : " . $file . "\n";

			$size = filesize($directory_base_path . $file);

			if ( $size == 0 )
			{
				shell_exec("mv " . $directory_base_path . $file . " " . $directory_base_path . "integrated/" . $file);
				continue;
			}

			$fp = fopen($directory_base_path . $file, "r");

			if ( $fp )
			{
				$i = 0;
				$sql = array();

				while ( ($buffer = fgets($fp, 4096)) !== false )
				{
					//$buffer = $_SQL->sql_real_escape_string($buf);
					$i++;

					//$buffer = trim(preg_replace("#([\t]+){1,}#", "\t", $buffer));

					while ( substr($buffer, -3, 2) != "||" )
					{
						//echo "hmÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¹".substr($buffer, -2);
						//die();

						$buffer = $buffer . fgets($fp, 4096);
					}

					$buffer = trim($buffer);
					$buffer = substr($buffer, 0, -2);

					$elems = explode("`|", $buffer);

					foreach ( $elems as &$elem )
					{
						$elem = $_SQL->sql_real_escape_string(trim($elem));
					}

					$nb_elem = count($elems) - 1;

					unset($elems[$nb_elem]);
					unset($elems[$nb_elem - 1]);

					$elems_without_key = $elems;
					unset($elems_without_key[1]);
					unset($elems_without_key[0]);

					//print_r($elems_without_key);

					$sql[] = "(NULL, '" . implode("', '", $elems) . "', '" . sha1(implode(",", $elems_without_key)) . "')";

					if ( $i % 500 == 0 )
					{
						$query = "INSERT IGNORE INTO `capgemini_audit_" . pathinfo($file, PATHINFO_FILENAME) . "` VALUES " . implode(",\n", $sql);
						$_SQL->sql_query($query);

						$sql = array();
					}
				}

				$query = "INSERT IGNORE INTO `capgemini_audit_" . pathinfo($file, PATHINFO_FILENAME) . "` VALUES " . implode(",\n", $sql);
				$_SQL->sql_query($query);

				shell_exec("mv " . $directory_base_path . $file . " " . $directory_base_path . "integrated/" . $file);

				if ( !feof($fp) )
				{
					echo "Error: unexpected fgets() fail\n";
				}
				fclose($fp);
			}
		}

		print_r($out);
	}

	function generate_cash()
	{
		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "DELETE FROM cash_audit_main";
		$_SQL->sql_query($sql);


		$sql = "SHOW TABLES WHERE Tables_in_arkadin like 'capgemini_audit_%'";
		$res = $_SQL->sql_query($sql);

		while ( $ob = $_SQL->sql_fetch_object($res) )
		{
			$sql2 = "SELECT _ID_RUN, count(1) as cpt FROM `" . $ob->Tables_in_arkadin . "` GROUP BY _ID_RUN";
			$res2 = $_SQL->sql_query($sql2);

			$data = array();

			$data[1] = 0;
			$data[2] = 0;

			while ( $ob2 = $_SQL->sql_fetch_object($res2) )
			{
				$data[$ob2->_ID_RUN] = $ob2->cpt;
			}

			$sql4 = "SELECT COUNT( 1 ) AS cpt FROM  `" . $ob->Tables_in_arkadin . "` GROUP BY functional_key HAVING COUNT(1)=2";
			$res4 = $_SQL->sql_query($sql4);

			$total_in_both_run = $_SQL->sql_num_rows($res4);

			$sql3 = "INSERT INTO cash_audit_main VALUES (NULL,'" . $ob->Tables_in_arkadin . "','" . array_sum($data) . "','" . $data[1] . "','" . $data[2] . "','" . ($data[2] - $total_in_both_run) . "','" . ($data[1] - $total_in_both_run) . "'  )";
			$_SQL->sql_query($sql3);
		}
	}

	function detail($param)
	{




		$this->layout_name = "admin";
		$this->title = $param[0];
		$this->ariane = '> Dashboard > <a href="' . LINK . 'audit/dashboardv1/">Audit V1</a> > ' . $this->title;


		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{

			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");
		}

		$data = array();


		if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
		{

			if ( !empty($_POST['data']['type']) )
			{


				$filter = $this->parse_filter($_GET['query']);


				$sql = "DELETE [AUDIT_BLUESKY_FOLLOWED]
					FROM [AUDIT_BLUESKY_FOLLOWED] a
					INNER JOIN [HASH_" . $param[0] . "] b ON a.HASH = b.HASH
					INNER JOIN [" . $param[0] . "]  c ON b.OID = c.OID
					WHERE [TABLE] = '" . $param[0] . "' " . $filter . " 
						";

				echo $sql;
				mssql_query($sql);

				$sql = "INSERT INTO [AUDIT_BLUESKY_FOLLOWED] ([HASH],[ID_RUN],[STATUS],[TABLE], [DATE],[COMMENT])
					SELECT b.HASH, " . ID_RUN . ", " . $_POST['data']['type'] . ", '" . $param[0] . "','" . date("Y-m-d H:i:s") . "' ,'" . $_POST['data']['text'] . "'
					FROM [" . $param[0] . "] a
					INNER JOIN [HASH_" . $param[0] . "] b ON a.OID = b.OID
					WHERE a.ID_RUN = " . ID_RUN . " " . $filter;

				echo $sql;


				mssql_query($sql);
				/* . " AND [HASH] NOT IN 
				  (SELECT HASH
				  FROM [AUDIT_BLUESKY_FOLLOWED]
				  WHERE [TABLE] = '" . $param[0] . "')"; */

				foreach ( $_GET['filter'] as $key => $val )
				{
					$ret[] = "filter:" . $key . ":" . urlencode($val);
				}

				$params = implode("/", $ret);


				$url = LINK . "audit/detail/" . $param[0] . "/" . $params . "/query:" . $_GET['query'];

				//header("location: " . LINK . "audit/detail/" . $param[0] . "/" . $params . "/query:" . $_GET['query']);
				//exit;
			}

			if ( !empty($_POST['filter']) )
			{
				$ret = array();
				$i = 0;

				foreach ( $_POST['filter'] as $var )
				{
					foreach ( $var as $key => $val )
					{
						$ret[] = "filter:" . $key . "-" . $i . ":" . urlencode($val);
					}
					$i++;
				}

				$ret[] = "filter:nbrows:" . $i;
				$params = implode("/", $ret);

				//W3siZmllbGQiOiJBY2NvdW50TmFtZSIsIm9wZXJhdG9yIjoiMSIsInNlYXJjaCI6ImR0dXgifV0=

				$filter2 = json_encode($_POST['filter']);

				$filter = base64_encode($filter2);

				header("location: " . LINK . "audit/detail/" . $param[0] . "/" . $params . "/query:" . $filter);
				exit;
			}
		}

		(empty($_GET['page'])) ? $data['page'] = 1 : $data['page'] = $_GET['page'];




		$sql = "SELECT TOP 1 * FROM [" . $param[0] . "] WHERE ID_RUN =2 ";
		$res2 = mssql_query($sql);

		$nb_fields = mssql_num_fields($res2);

		for ( $i = 3; $i + 1 < $nb_fields; $i++ )
		{
			$field = mssql_field_name($res2, $i);

			if ( $field == 'IsCorrected' )
			{
				continue;
			}

			$gg = array();

			$gg['id'] = $field;
			$gg['libelle'] = $field;


			$data['field'][] = $gg;
		}


		$data['operator'][0]['id'] = 1;
		$data['operator'][0]['libelle'] = "= ?";
		$data['operator'][1]['id'] = 2;
		$data['operator'][1]['libelle'] = "like %?";
		$data['operator'][2]['id'] = 3;
		$data['operator'][2]['libelle'] = "< ?";
		$data['operator'][3]['id'] = 4;
		$data['operator'][3]['libelle'] = "> ?";
		$data['operator'][4]['id'] = 5;
		$data['operator'][4]['libelle'] = "!= ?";



		$data['table'] = $param[0];

		$data['list_id_run'] = $this->get_list_id_run();

		$this->set('data', $data);

		$this->javascript = array("jquery-1.8.0.min.js", "jquery.easyui.min.js", "datagrid-scrollview.js");
		$this->code_javascript[] = "
				$(function(){  
					$('#tg').datagrid();  
				});  
				function load(mode){  
						$('#tg').datagrid({  
							url:'" . LINK . "audit/get_scroll/" . $data['table'] . "' 
						});
						

						
				}";

		$this->code_javascript[] = "$(function() {
	var nbline;
	var derline;
	nbline = $('tr.blah').length;
	derline = nbline;
	
	$('#add').click( function() {  
		derline++;
		nbline++;
		var clone
		clone = $('tr.blah:first').clone();
		clone.attr('id','tr-'+derline);
		clone.find('input.delete-line').attr('id','delete-'+derline);
		//clone.find('input.variante_id').attr('id','variante_id'+derline).attr('name','data[filter]['+derline+'][variante_id]');
		clone.find('select.field').attr('id','filter-'+derline+'-field').attr('name','filter['+derline+'][field]').val($('tr.blah:first select.field').val());
		clone.find('select.operator').attr('id','filter-'+derline+'-operator').attr('name','filter['+derline+'][operator]').val($('tr.blah:first select.operator').val());
		clone.find('input.search').attr('id','search-'+derline+'-search').attr('name','filter['+derline+'][search]').val($('tr.blah:first input.search').val());
		$('#variante').append(clone); 
		$('#nb_line').attr('value',derline);
		$('input.delete-line').attr('disabled',false);
		if (nbline === 10) {
			$('#add').attr('disabled',true);
		}
	});
	
	$('input.delete-line').live('click', function() {
		if (nbline > 1) {
			var numLigne = $(this).attr('id').match(/delete-(\d+)/)[1];
			$('#tr-' + numLigne).remove(); 
			nbline--;
			
			$('#add').attr('disabled',false);
			if (nbline === 1){
				$('input.delete-line').attr('disabled',true);
			}
		}
	});
	

});";

		/*
		 * 	$('select.prix').live('change', function() {
		  var valSelect = $(this).attr('id').match(/prix-(\d+)/)[1];
		  $('#flex-'+valSelect).empty().load('analyse.php?code='+$(this).val());
		  });
		 */
	}

	function create_all_index()
	{
		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);



		$sql = "SHOW TABLES WHERE Tables_in_arkadin like 'capgemini_audit_%' and Tables_in_arkadin not like 'capgemini_audit_C-V1-DC-0%' and Tables_in_arkadin not like 'capgemini_audit_C-V1-CO-0%'";
		$res = $_SQL->sql_query($sql);

		while ( $ob = $_SQL->sql_fetch_object($res) )
		{

			$res2 = $_SQL->sql_query("Show fields from `" . $ob->Tables_in_arkadin . "` where Field like '\_%' and Field != '_OID' and Field != '_ID_RUN'");


			if ( in_array($ob->Tables_in_arkadin, array("capgemini_audit_C-V1-DC-12", "capgemini_audit_C-V1-DC-11", "capgemini_audit_C-V1-DC-10")) )
			{
				continue;
			}

			$i = 0;
			while ( $ob2 = $_SQL->sql_fetch_object($res2) )
			{
				$i++;
				$sql3 = "CREATE INDEX `idx_" . str_replace("capgemini_audit_", "", $ob->Tables_in_arkadin) . "" . $ob2->Field . "` ON `" . $ob->Tables_in_arkadin . "` (`" . $ob2->Field . "`);";
				echo $sql3 . "\n";


				mysql_query($sql3);
				//$_SQL->sql_query($sql3);

				if ( $i > 40 )
				{
					break;
				}
			}
		}
	}

	function filter($param)
	{



		/*
		  [path] => en/user/city/
		  [q] => paris
		  [limit] => 10
		  [timestamp] => 1297207840432
		  [lg] => en
		  [url] => user/city/

		 */

		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT `" . $param[1] . "` FROM `" . $param[0] . "` WHERE `" . $param[1] . "` LIKE '" . $_SQL->sql_real_escape_string($_GET['q']) . "%' 
		 ORDER BY `" . $param[1] . "` LIMIT 0,100";
		$res = $_SQL->sql_query($sql);

		while ( $ob = $_SQL->sql_fetch_object($res) )
		{
			echo $ob->$param[1] . "\n";
		}
	}

	function update_table_audit()
	{
		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{
			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");
			$sql2 = "SELECT [name], [xtype]  FROM [ARKADIN_AUDIT].[dbo].[sysobjects] WHERE xtype='U' order by name";

			$res2 = mssql_query($sql2);

			while ( $ob2 = mssql_fetch_object($res2) )
			{
				if ( !strstr($ob2->name, "-") && !strstr($ob2->name, "_V2") )
				{
					continue;
				}

				if ( strstr($ob2->name, "HASH_") )
				{
					continue;
				}

				/*
				  if ("C-V1-DC-07" != $ob2->name)
				  {
				  continue;
				  } */

				$fields = array();
				$sql4 = "SELECT COLUMN_NAME,DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_NAME='" . $ob2->name . "'";

				$res4 = mssql_query($sql4);
				while ( $ob4 = mssql_fetch_object($res4) )
				{
					if ( in_array($ob4->COLUMN_NAME, array("OID", "ID_RUN", "IsCorrected", "CorrectionComment", "AuditStatus", "Comment", "AUDIT_OID")) )
					{
						continue;
					}

					if ( strstr($ob4->COLUMN_NAME, "Error_") )
					{
						continue;
					}

					switch ( $ob4->DATA_TYPE )
					{
						case 'datetime':
							$fields[] = "convert(varchar(1000),[" . $ob4->COLUMN_NAME . "],126)";
							break;

						default:
							$fields[] = "convert(varchar(1000),isnull([" . $ob4->COLUMN_NAME . "],'0'))";
							break;
					}
				}
				/*
				  $sql3 = "IF object_id('HASH_" . $ob2->name . "') is null
				  begin

				  CREATE TABLE [HASH_" . $ob2->name . "]
				  (
				  [OID] int,
				  [ID_RUN] int,
				  [HASH] varchar(32)
				  )

				  INSERT INTO [HASH_" . $ob2->name . "]
				  SELECT OID, ID_RUN, SUBSTRING(master.dbo.fn_varbintohexstr(HashBytes('MD5', " . implode("+", $fields) . ")), 3, 32)
				  FROM [" . $ob2->name . "]
				  end"; */

				if ( strstr($ob2->name, "_V2") )
				{
					$oid = "AUDIT_OID";
				}
				else
				{
					$oid = "OID";
				}


				$sql3 = "IF object_id('HASH_" . $ob2->name . "') is null
begin
	SELECT [" . $oid . "], [ID_RUN], [HASH] = CAST(SUBSTRING(master.dbo.fn_varbintohexstr(HashBytes('MD5', " . implode("+", $fields) . ")), 3, 32) as VARCHAR(32))
	INTO [HASH_" . $ob2->name . "]
	FROM [" . $ob2->name . "]
end";


				echo "Table : [HASH_" . $ob2->name . "]\n";
				//echo "\n______________________________\n" . $sql3 . "\n______________________________\n";
				//echo $sql3 . "\n";
				//sleep(3); // else problem with [C-V1-IO-10] => [HASH_C-V1-IO-10] ?
				//mssql_query($sql3);

				$cmd = 'sqsh -S10.102.28.5 -DARKADIN_AUDIT -Usa -C"' . $sql3 . '" -P3fN4uL';
				shell_exec($cmd);
			}
		}
	}

	function clear()
	{
		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{
			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");
			$sql2 = "SELECT [name], [xtype]  FROM [ARKADIN_AUDIT].[dbo].[sysobjects] WHERE xtype='U' order by name desc";
			$res2 = mssql_query($sql2);

			while ( $ob2 = mssql_fetch_object($res2) )
			{
				if ( !strstr($ob2->name, "-") )
				{
					continue;
				}

				if ( !strstr($ob2->name, "HASH_") )
				{
					continue;
				}

				$sql3 = "if object_id('" . $ob2->name . "') is not null
begin
  DROP TABLE [" . $ob2->name . "]
end
";
				//echo "\n______________________________\n".$sql3 . "\n______________________________\n";
				mssql_query($sql3);
			}
		}
	}

	function add_index()
	{
		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{
			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");
			$sql2 = "SELECT [name], [xtype]  FROM [ARKADIN_AUDIT].[dbo].[sysobjects] WHERE xtype='U' order by name desc";

			$res2 = mssql_query($sql2);

			while ( $ob2 = mssql_fetch_object($res2) )
			{
				if ( !strstr($ob2->name, "-") )
				{
					continue;
				}

				if ( strstr($ob2->name, "HASH_") )
				{
					continue;
				}

				$sql3 = "CREATE INDEX [idxHASH_" . $ob2->name . "hash] ON [HASH_" . $ob2->name . "] (HASH)";

				echo "CREATE INDEX : [HASH_" . $ob2->name . "]\n";

				$cmd = 'sqsh -S10.102.28.5 -DARKADIN_AUDIT -Usa -C"' . $sql3 . '" -P3fN4uL';
				shell_exec($cmd);
				//echo $sql3."\n";
				//mssql_query($sql3);
			}
		}
	}

	function dashboardv1()
	{
		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$this->layout_name = "admin";
		$this->title = "Audit V1";
		$this->ariane = "> Dashboard > " . $this->title;

		$sql = "SELECT * FROM audit_tree WHERE active = 0 ORDER by domain,system,reference";

		$res = $_SQL->sql_query($sql);


		$data = array();
		$data['tree'] = $_SQL->sql_to_array($res);

		//$this->javascript = array("CollapsibleLists.compressed.js");
		//$this->code_javascript[] = "CollapsibleLists.apply();";

		$this->javascript = array("jquery-1.8.0.min.js", "jquery.easyui.min.js");
		$this->code_javascript[] = "
		        function collapseAll(){
            $('#tg').treegrid('collapseAll');
        }
        function expandAll(){
            $('#tg').treegrid('expandAll');
        }
		
function formatProgress(value){
            if (value){
                var s = ''
				+'<div style=\"background:#bbb;border:1px solid #000\">' +
				'<div style=\"background:#bbb;border:1px solid #fff\">' +
                        '<div style=\"padding-left:2px;width:' + value + '%;background:#458B00;color:#fff;font-weight:700\">' + value + '%' + '</div>'
                        +'</div></div>';
                return s;
            } else {
                return '';
            }  
        } 
	

";

		/*
		  $this->code_javascript[] = "
		  $('#tg').datagrid({
		  rowStyler:function(index,row){
		  if (row.id % 2 == 0){
		  return 'background-color:pink;color:blue;font-weight:bold;';
		  }
		  }
		  });  "; */

		$data['list_id_run'] = $this->get_list_id_run();

		$this->set('data', $data);
	}

	function data2()
	{

		$this->view = false;
		$this->layout_name = false;
		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$i = 0;

		/*
		  $auditv1['id'] = $i;
		  $auditv1['name'] = "Audit V1";
		  $auditv1['run'] = 2000;
		  $auditv1['accepted'] = 2000;
		  $auditv1['refused'] = 2000;
		  $auditv1['inwait'] = 2000;
		  $auditv1['trend_accepted'] = 2000;
		  $auditv1['trend_refused'] = 2000;
		  $auditv1['trend_total'] = 2000;
		 */

		$sql2 = "select * from cash_audit_main order by `table`";
		$res2 = $_SQL->sql_query($sql2);

		$data = array();

		while ( $tab = $_SQL->sql_fetch_array($res2) )
		{
			$ref = str_replace("capgemini_audit_", "", $tab['table']);
			$data['compile'][$ref] = $tab;
		}

		$sql = "SELECT * FROM audit_tree WHERE active = 0 ORDER by domain,system,reference";
		$res = $_SQL->sql_query($sql);

		$domain = '@@';
		$system = '@@';

		$id_domain = -1;
		$id_system = -1;

		while ( $ob = $_SQL->sql_fetch_object($res) )
		{
			//debug($ob); 

			if ( $domain != $ob->domain )
			{
				$i++;
				$id_domain++;

				$tmp = array();
				$tmp['id'] = $i;
				$tmp['name'] = "<b>" . $ob->domain . "</b>";
				$tmp['run'] = 2000;
				$tmp['accepted'] = 2000;
				$tmp['refused'] = 2000;
				$tmp['inwait'] = 2000;
				$tmp['trend_accepted'] = 2000;
				$tmp['trend_refused'] = 2000;
				$tmp['trend_total'] = 90;

				$auditv1[$id_domain] = $tmp;

				$id_system = -1;
				$domain = $ob->domain;
			}

			if ( $system != $ob->system )
			{
				$i++;
				$id_system++;
				$tmp = array();
				$tmp['id'] = $i;
				$tmp['name'] = $ob->system;
				$tmp['run'] = 2000;
				$tmp['accepted'] = 2000;
				$tmp['refused'] = 2000;
				$tmp['inwait'] = 2000;
				$tmp['trend_accepted'] = 2000;
				$tmp['trend_refused'] = 2000;
				$tmp['trend_total'] = 50;

				$auditv1[$id_domain]['children'][$id_system] = $tmp;
				$system = $ob->system;
			}

			$i++;

			$tab = explode(":", $ob->name);

			$tmp = array();
			$tmp['id'] = $i;
			$tmp['name'] = '<a href="' . LINK . 'audit/detail/' . $ob->reference . '/">' . $ob->reference . " : " . trim(end($tab)) . "</a>";

			(!empty($data['compile'][$ob->reference])) ? $tmp['run'] = $data['compile'][$ob->reference]['run2'] : $tmp['run'] = 'n/a';
			(!empty($data['compile'][$ob->reference])) ? $tmp['add'] = $data['compile'][$ob->reference]['add'] : $tmp['add'] = 'n/a';
			(!empty($data['compile'][$ob->reference])) ? $tmp['del'] = $data['compile'][$ob->reference]['del'] : $tmp['del'] = 'n/a';
			if ( !empty($data['compile'][$ob->reference]) )
			{
				if ( $data['compile'][$ob->reference]['run1'] == $data['compile'][$ob->reference]['run2'] )
				{
					$tmp['trend'] = "0%";
				}
				elseif ( $data['compile'][$ob->reference]['run1'] != 0 )
				{
					$percent = ($data['compile'][$ob->reference]['run2'] - $data['compile'][$ob->reference]['run1']) / $data['compile'][$ob->reference]['run1'] * 100;
					$tmp['trend'] = round($percent, 2) . "%";
				}
				else
				{
					$tmp['trend'] = "inf%";
				}

				$tmp['trend'] .= " ";

				if ( $data['compile'][$ob->reference]['run2'] - $data['compile'][$ob->reference]['run1'] > 0 )
				{
					$tmp['trend'] .= '<img src="' . IMG . '16/Trend Down.png" height="16" width="16" />';
				}
				elseif ( $data['compile'][$ob->reference]['run2'] == $data['compile'][$ob->reference]['run1'] )
				{
					$tmp['trend'] .= '<img src="' . IMG . '16/same_trend.png" height="16" width="16" />';
				}
				else
				{
					$tmp['trend'] .= '<img src="' . IMG . '16/trend_up2.png" height="16" width="16" />';
				}
			}
			else
			{
				$tmp['trend'] = 'n/a';
			}

			$tmp['accepted'] = 0;
			$tmp['refused'] = 0;

			(!empty($data['compile'][$ob->reference])) ? $tmp['inwait'] = $data['compile'][$ob->reference]['run2'] : $tmp['inwait'] = 'n/a';

			if ( !empty($data['compile'][$ob->reference]) )
			{
				if ( $data['compile'][$ob->reference]['run2'] == 0 )
				{
					$tmp['trend_total'] = 100;
				}
				else
				{
					$tmp['trend_total'] = "0";
				}
			}
			$auditv1[$id_domain]['children'][$id_system]['children'][] = $tmp;

			//echo "\$auditv1['children'][$id_domain]['children'][$id_system]['children'][] = $tmp;<br />";
			//debug($auditv1);
		}


		echo json_encode($auditv1);
		//debug($auditv1);
	}

	function get_scroll($param)
	{

		$filter = $this->parse_filter($param[1]);

		(empty($_POST['sort'])) ? $sort = "OID" : $sort = $_POST['sort'];
		(empty($_POST['order'])) ? $order = "asc" : $order = $_POST['order'];
		(empty($_POST['rows'])) ? $rows = "50" : $rows = $_POST['rows'];
		(empty($_POST['page'])) ? $page = "1" : $page = $_POST['page'];


		$start = ($page) * $rows;

		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);
		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{

			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");

			//$sql2 = "SELECT o.name as name, max(i.rowcnt) as cpt FROM sysobjects o, sysindexes i WHERE	o.name = '" . $param[0] . "' AND i.id = o.id AND o.type = 'U' group by o.name";
			$sql2 = "SELECT count(1) as cpt FROM [" . $param[0] . "] WHERE ID_RUN =2 " . $filter;



			//echo $sql2;

			$res2 = mssql_query($sql2);


			$output = array();
			while ( $ob2 = mssql_fetch_object($res2) )
			{
				$output['total'] = $ob2->cpt;
			}


			//if ( $start < $output['total'] && empty($filter))
			//{

			$sql3 = "SELECT TOP " . AUDIT_ELEM_PER_PAGE . " a.*,b.[HASH] FROM [" . $param[0] . "] a
			INNER JOIN [HASH_" . $param[0] . "] b ON a.OID = b.OID WHERE a.ID_RUN =2 ";

			$sql3 = "SELECT * FROM ( 
         SELECT TOP " . $rows . " * FROM ( 
         SELECT TOP " . $start . " a.*,b.[HASH],c.[COMMENT],c.[STATUS]
		FROM [" . $param[0] . "] a
		INNER JOIN [HASH_" . $param[0] . "] b ON a.OID = b.OID
		LEFT JOIN [AUDIT_BLUESKY_FOLLOWED] c ON c.HASH = b.HASH and c.ID_RUN = 2 AND c.[TABLE] = '" . $param[0] . "'
		WHERE a.ID_RUN =2 " . $filter;

			if ( $order == "asc" )
			{
				$sql3 .= " ORDER BY " . $sort . " asc
				) AS tbl1 ORDER BY " . $sort . " desc 
        ) AS tbl2 ORDER BY " . $sort . " asc ";
			}
			else
			{
				$sql3 .= " ORDER BY " . $sort . " desc
				 ) AS tbl1 ORDER BY " . $sort . " asc 
        ) AS tbl2 ORDER BY " . $sort . " desc ";
			}


			//echo $sql3;


			$res3 = mssql_query($sql3);


			//$hash = array();
			while ( $tab = mssql_fetch_array($res3, MSSQL_ASSOC) )
			{
				unset($tab['IsCorrected']);
				unset($tab['CorrectionComment']);
				unset($tab['OID']);
				unset($tab['ID_RUN']);


				$type = array('Not evalued', 'Accepted', 'To be corrected');


				$status = $tab['STATUS'];
				$tab['STATUS'] = '<select name="data[status][' . $tab['HASH'] . ']" style="margin:0;">';

				$i = 1;
				foreach ( $type as $var )
				{

					if ( $status == $i )
					{
						$selected = "selected";
					}
					else
					{
						$selected = "";
					}

					$tab['STATUS'] .= '<option value="' . $i . '" ' . $selected . '>' . $var . '</option>';
					$i++;
				}

				$tab['STATUS'] .= '</select>';



				$output['rows'][] = $tab;



				//$hash[] = $tab['HASH'];
			}
		}
		//}

		echo json_encode($output);
	}

	function parse_filter($param)
	{

		$query = json_decode(base64_decode($param), true);

		$filter = '';
		if ( is_array($query) )
		{
			//debug($query);


			$where = array();
			foreach ( $query as $line )
			{

				if ( empty($line['search']) )
				{
					continue;
				}
				if ( empty($line['operator']) )
				{
					continue;
				}
				if ( empty($line['field']) )
				{
					continue;
				}

				if ( $line['field'] != -1 && $line['operator'] != -1 && !empty($line['search']) )
				{
					switch ( $line['operator'] )
					{
						case '1':
							$operator = "=";
							break;
						case '2':
							$operator = "like";
							break;
						case '3':
							$operator = "<";
							break;
						case '4':
							$operator = ">";
							break;
						case '5':
							$operator = "!=";
							break;
					}

					$where[] = " AND [" . $line['field'] . "] " . $operator . " '" . $line['search'] . "'  ";
				}
			}

			$filter .= implode(" ", $where);
		}

		return $filter;
	}

	private function get_list_id_run()
	{
		$_SQL = Singleton::getInstance(SQL_DRIVER);
		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);

		while ( $ob = $_SQL->sql_fetch_object($res) )
		{

			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");

			$sql = "SELECT DISTINCT ID_RUN from ARKA_AUDIT_MONITORING";
			$res = mssql_query($sql);

			$out = array();
			while ( $ob = mssql_fetch_object($res) )
			{
				$out[] = $ob->ID_RUN;
			}

			return $out;
		}
	}

	function create_index_on_error_filter()
	{

		$this->view = false;
		$this->layout_name = false;

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM data_dictionary_server where id=1";
		$res = $_SQL->sql_query($sql);
		while ( $ob = $_SQL->sql_fetch_object($res) )
		{
			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");
			$sql2 = "SELECT [name], [xtype]  FROM [ARKADIN_AUDIT].[dbo].[sysobjects] WHERE xtype='U' order by name";
			$res2 = mssql_query($sql2);

			$i=0;
			while ( $ob2 = mssql_fetch_object($res2) )
			{
				if ( substr($ob2->name, -3, 3) != "_V2" )
				{
					continue;
				}

				if ( strstr($ob2->name, "HASH_") )
				{
					continue;
				}

				$sql4 = "SELECT COLUMN_NAME,DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='" . $ob2->name . "'";

				$res4 = mssql_query($sql4);
				while ( $ob4 = mssql_fetch_object($res4) )
				{
					if ( substr($ob4->COLUMN_NAME, 0,6) == "Error_" )
					{
						$i++;
						$sql = "CREATE NONCLUSTERED INDEX [idx_".$ob2->name."_".$ob4->COLUMN_NAME."] ON [".$ob2->name."]([".$ob4->COLUMN_NAME."]) WHERE [".$ob4->COLUMN_NAME."]= 1";
						
						echo $i." [".date("Y-m-d H:i:s")."] ".$sql."\n";

						$cmd = 'sqsh -S'.$ob->ip.' -DARKADIN_AUDIT -U'.$ob->login.' -C"' . $sql . '" -P'.$ob->password;
						shell_exec($cmd);
					}
				}
			}
		}

		
		
	}

	function delete_index_on_error_filter()
	{
		
	}
	
	function dashboardv2()
	{
		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$this->layout_name = "admin";
		$this->title = "Audit V2";
		$this->ariane = "> Dashboard > " . $this->title;

		//$sql = "SELECT * FROM audit_tree WHERE active = 0 ORDER by domain,system,reference";
		$sql = "SELECT * FROM audit_tree_v2 ORDER by table_name,field_name,error_name";

		$res = $_SQL->sql_query($sql);


		$data = array();
		$data['tree'] = $_SQL->sql_to_array($res);

		//$this->javascript = array("CollapsibleLists.compressed.js");
		//$this->code_javascript[] = "CollapsibleLists.apply();";

		$this->javascript = array("jquery-1.8.0.min.js", "jquery.easyui.min.js");
		$this->code_javascript[] = "
		        function collapseAll(){
            $('#tg').treegrid('collapseAll');
        }
        function expandAll(){
            $('#tg').treegrid('expandAll');
        }
		
function formatProgress(value){
            if (value){
                var s = ''
				+'<div style=\"background:#bbb;border:1px solid #000\">' +
				'<div style=\"background:#bbb;border:1px solid #fff\">' +
                        '<div style=\"padding-left:2px;width:' + value + '%;background:#458B00;color:#fff;font-weight:700\">' + value + '%' + '</div>'
                        +'</div></div>';
                return s;
            } else {
                return '';
            }  
        } 
	

";

		/*
		  $this->code_javascript[] = "
		  $('#tg').datagrid({
		  rowStyler:function(index,row){
		  if (row.id % 2 == 0){
		  return 'background-color:pink;color:blue;font-weight:bold;';
		  }
		  }
		  });  "; */

		$this->set('data', $data);
	}

	function create_audit_tree_V2()
	{

		$this->view = false;
		$this->layout_name = false;

		// remove if exist audit_tree_v2 table
		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$sql = "TRUNCATE TABLE audit_tree_v2";

		$res = $_SQL->sql_query($sql);

		// fetch Audit V2 config file
		// and insert in audit_tree_v2 table
		$directory_audit_config_path = "/home/www/arkadin/data/arkadin/AUDIT/config/";
		$fp = fopen($directory_audit_config_path . "Audit_V2_Config.csv", "r");

		if ( $fp )
		{
			$i = 0;
			$sql = array();

			// remove header line
			$buffer = fgets($fp, 4096);
			while ( ($buffer = fgets($fp, 4096)) !== false )
			{

				$elems = explode(";", $buffer);


				/*
				  //insert a first line for table summarize
				  $query = "INSERT INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
				  //echo $query;
				  $_SQL->sql_query($query);

				  //insert a second for table/field summarize
				  $query = "INSERT INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "','" . trim($elems[2]) . "',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
				  //echo $query;
				  $_SQL->sql_query($query); */

				if ( $elems[4] == 1 )
				{
					$query = "INSERT IGNORE INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "','" . trim($elems[2]) . "','Nullable',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
					$_SQL->sql_query($query);
				}
				if ( $elems[5] == 1 )
				{
					$query = "INSERT IGNORE INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "','" . trim($elems[2]) . "','Unique',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
					$_SQL->sql_query($query);
				}
				if ( $elems[6] == 1 )
				{
					$query = "INSERT IGNORE INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "','" . trim($elems[2]) . "','Range',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
					$_SQL->sql_query($query);
				}
				if ( $elems[7] == 1 )
				{
					$query = "INSERT IGNORE INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "','" . trim($elems[2]) . "','Value',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
					$_SQL->sql_query($query);
				}
				if ( $elems[8] == 1 )
				{
					$query = "INSERT IGNORE INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "','" . trim($elems[2]) . "','RegExp',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
					$_SQL->sql_query($query);
				}
				if ( $elems[9] == 1 )
				{
					$query = "INSERT IGNORE INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "','" . trim($elems[2]) . "','RefTable',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
					$_SQL->sql_query($query);
				}
				if ( $elems[10] == 1 )
				{
					$query = "INSERT IGNORE INTO audit_tree_v2 VALUES (NULL, '" . trim($elems[1]) . "','" . trim($elems[2]) . "','Length',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)";
					$_SQL->sql_query($query);
				}
			}

			if ( !feof($fp) )
			{
				echo "Error: unexpected fgets() fail\n";
			}
			fclose($fp);
		}
	}

	/*
	 * generate_cash_v2
	 * 
	 * $param is an array containing:
	 * 		$param[0]: name of the table on which counters must be updated. If null all table audit_tree_v2 counters are updated
	 * 		$param[1]: name of the field on which counters must be updated. If null all audit_tree_v2 counters are updated for the given table_name
	 * 		$param[2]: name of the error on which counters must be updated. If null all audit_tree_v2 counters are updated for the given table_name, filed_name
	 * update audit_tree_v2 table with:
	 * total number of lines from ARKADIN_AUDIT related piv table
	 * total number of lines in error in current run
	 * total number of lines in error in reference run
	 * added number of lines in error in current run (nb of new lines)
	 * deleted number of lines in error from reference run (nb of deleted lines)
	 * total number of lines in current run with accepted status
	 * total number of lines in current run with to_be_corrected status
	 * total number of lines in current run with in_wait status
	 */

	function generate_cash_v2($param)
	{
		$this->view = false;
		$this->layout_name = false;
		//debug($param);

		$_SQL = Singleton::getInstance(SQL_DRIVER);

		// get list of lines to be updated in audit_tree_v2
		$sql = "SELECT * from audit_tree_v2 where '1'";
		if ( !empty($param[0]) )
		{
			$sql .= " and table_name='" . $param[0] . "'";
		}
		if ( !empty($param[1]) )
		{
			$sql .= " and field_name='" . $param[1] . "'";
		}
		if ( !empty($param[2]) )
		{
			$sql .= " and error_name='" . $param[2] . "'";
		}
		$sql .= " order by table_name, field_name, error_name asc";

		$_SQL->sql_query($sql);
		$res = $_SQL->sql_query($sql);
		//debug($res);
		// get ARKADI_AUDIT mssql server infos
		$sql2 = "SELECT * FROM data_dictionary_server where id=1";
		$res2 = $_SQL->sql_query($sql2);

		while ( $ob = $_SQL->sql_fetch_object($res2) ) // only 1 line
		{
			//open connection to ARKADIN_AUDIT DB
			$db = @mssql_connect($ob->ip, $ob->login, $ob->password);

			if ( !$db )
			{
				echo("ERROR : impossible to connect to " . $ob->ip . " - (mx : " . $ob->mx . ")\n");
				continue;
			}

			mssql_select_db("ARKADIN_AUDIT");

			// process list of lines to be updated in audit_tree_v2
			while ( $to_be_updated = $_SQL->sql_fetch_object($res) )
			{
				//debug($to_be_updated);
				$output = array();

				//total: get count of all element for the given table in ARKADIN_AUDIT related PIV
				$sql3 = "SELECT count(1) as cpt FROM [" . $to_be_updated->table_name . "]";
				$res3 = mssql_query($sql3);
				$ob2 = mssql_fetch_object($res3);
				$output['total'] = $ob2->cpt;

				// total number of lines in error in current run
				//$sql3 = "SELECT count(1) as cpt FROM [" . $to_be_updated->table_name . "_V2] where ID_RUN=" . $_GET['run_current'] . "
				$sql3 = "SELECT count(1) as cpt FROM [" . $to_be_updated->table_name . "_V2] where ID_RUN=2
					AND ERROR_" . $to_be_updated->field_name . "_" . $to_be_updated->error_name . " is not null";
				$res3 = mssql_query($sql3);
				$ob2 = mssql_fetch_object($res3);
				$output['run_current'] = $ob2->cpt;

				// total number of lines in error in reference run
				//$sql3 = "SELECT count(1) as cpt FROM [" . $to_be_updated->table_name . "_V2] where ID_RUN=" . $_GET['run_reference'] . "
				$sql3 = "SELECT count(1) as cpt FROM [" . $to_be_updated->table_name . "_V2] where ID_RUN=1
					AND ERROR_" . $to_be_updated->field_name . "_" . $to_be_updated->error_name . " is not null";
				$res3 = mssql_query($sql3);
				$ob2 = mssql_fetch_object($res3);
				$output['run_reference'] = $ob2->cpt;

				// added number of lines in error in current run (nb of new lines)
				// deleted number of lines in error from reference run (nb of deleted lines)
				//$sql3 = "SELECT count(1) as cpt FROM [" . $to_be_updated->table_name . "_V2] where ID_RUN in (" . $_GET['run_current'] . "," .
				// $_GET['run_reference'] . ")
				// where t.ID_RUN in ($_GET['run_current'],$_GET['run_refernce'])
				$sql3 = "SELECT count(1) as cpt FROM [" . $to_be_updated->table_name . "_V2] t
					join [HASH_" . $to_be_updated->table_name . "_V2] h on t.AUDIT_OID=h.AUDIT_OID
					where t.ID_RUN in (1,2)
					AND t.ERROR_" . $to_be_updated->field_name . "_" . $to_be_updated->error_name . " is not null
					GROUP BY h.HASH HAVING COUNT(1)=2";
				$res3 = mssql_query($sql3);
				$total_in_both_run = mssql_fetch_object($res3);
				$output['add'] = $output['run_current'] - $total_in_both_run;
				$output['del'] = $output['run_reference'] - $total_in_both_run;

				// total number of lines in current run with accepted status
				// total number of lines in current run with to_be_corrected status
				// total number of lines in current run with in_wait status
				// AND t.ID_RUN = "2" . $_GET['run_current'] . "
				$sql3 = "SELECT f.STATUS, count(1) as cpt FROM [" . $to_be_updated->table_name . "_V2] t
					join [HASH_" . $to_be_updated->table_name . "_V2] h on t.AUDIT_OID=h.AUDIT_OID
					left join [AUDIT_BLUESKY_FOLLOWED] f on h.HASH=f.HASH				
					where t.ERROR_" . $to_be_updated->field_name . "_" . $to_be_updated->error_name . " is not null
						AND t.ID_RUN = 2
					GROUP BY f.STATUS";
				debug($sql3);
				$res3 = mssql_query($sql3);

				$output['accpeted'] = 0;
				$output['to_be_corrected'] = 0;
				$output['in_wait'] = 0;
				while ( $ob2 = mssql_fetch_object($res3) )
				{
					if ( ($ob2->STATUS == 2 ) )
					{
						$output['accpeted'] += $ob2->cpt;
					}
					else if ( ($ob2->STATUS == 2 ) )
					{
						$output['accpeted'] += $ob2->cpt;
					}
					else if ( is_null($ob2->STATUS) || ($ob2->STATUS == 1) )
					{
						$output['in_wait'] += $ob2->cpt;
					}
					else
					{
						die('unexpected status found in ARKADIN_AUDIT..AUDIT_BLUESKY_FOLLOWED table');
					}
				}


				$sql4="UPDATE audit_tree_v2 a set
				  total=".$output['total'].",
				  run_current=".$output['run_current'].",
				  run_reference=".$output['run_reference'].",
				  run_reference=".$output['run_reference'].",
				  add=".$output['add'].",
				  del=".$output['del'].",
				  accpeted=".$output['accpeted'].",
				  to_be_corrected=".$output['to_be_corrected'].",
				  in_wait=".$output['in_wait']."
				  where a.table_name=" . $to_be_updated->table_name . " AND
				  a.field_name=" . $to_be_updated->field_name . " AND
				  a.error_name=" . $to_be_updated->error_name;
				
				debug($sql4);
				debug($output);
			}
			//close connection to ARKADIN_AUDIT DB
			mssql_close();
		}
	}

}

