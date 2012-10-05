<?php

class photo extends controller {

    public $module_group = "Media";

    function index() {
        $this->title = __("Members");
        $this->ariane = "> " . $this->title;

        $_SQL = Singleton::getInstance(SQL_DRIVER);


        $sql = "select * from user_main 
		INNER JOIN geolocalisation_city ON geolocalisation_city.id = user_main.id_geolocalisation_city
		where is_valid ='1' order by points DESC LIMIT 50";
        $res = $_SQL->sql_query($sql);

        $data = $_SQL->sql_to_array($res);

        $this->set("data", $data);
    }

    function admin_import() {
        $module['picture'] = "administration/photo.gif";
        $module['name'] = __("Pictures");
        $module['description'] = __("Upload, convert and edit a picture");
        return $module;
    }

    function admin_crop() {

        $this->layout_name = "admin";
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        if (from() == "administration.controller.php") {

            $sql = "select count(1) as cpt from species_picture_in_wait a
			inner join species_tree_id b ON a.id_species_main = b.id_species_main
			WHERE b.id_species_family ='438' AND id_history_etat=1";

            $res = $_SQL->sql_query($sql);
            $data = $_SQL->sql_to_array($res);

            $module['count'] = $data[0]['cpt'];
            $module['picture'] = "administration/crop2.png";
            $module['name'] = __("Crop a picture");
            $module['description'] = __("Resize an image");

            $this->title = __($module['name']);
            $this->ariane = "> " . __("Administration") . " > " . $this->title;

            
            return $module;
        }

  die(from());
        $this->title = __("Crop a picture");
        $this->ariane = "> <a href=\"" . LINK . "administration/\">" . __("Administration") . "</a> > " . $this->title;



        /*         * ****************************************************************************************************** */

        //118570
        //id_photo // id_species_picture_main

        if ($_SERVER['REQUEST_METHOD'] == "POST") {

            if (!empty($_POST['idontknow'])) {
                //we don't know so we require a new picture
                header("location: " . LINK . "photo/admin_crop/");
                die();
            }


            $species_picture_in_wait['species_picture_in_wait']['id'] = $_POST['species_picture_main']['id'];
            $species_picture_in_wait['species_picture_in_wait']['id_species_picture_info'] = $_POST['species_picture_main']['id_species_picture_info2'];

            if (!empty($_POST['irefuse'])) {


                $_SQL->set_history_type(9);
                if (!$_SQL->sql_save($species_picture_in_wait)) {

                    $error = $_SQL->sql_error();

                    if (is_array($_SESSION['ERROR'])) {

                        $_SESSION['ERROR'] = array_merge($_SESSION['ERROR'], $error);
                    } else {
                        $_SESSION = $error;
                    }


                    if (!empty($_SESSION['ERROR']['species_picture_in_wait']['id_species_picture_info'])) {
                        $_SESSION['ERROR']['species_picture_in_wait']['id_species_picture_info2'] = $_SESSION['ERROR']['species_picture_in_wait']['id_species_picture_info'];
                        unset($_SESSION['ERROR']['species_picture_in_wait']['id_species_picture_info']);
                    }


                    $title = $GLOBALS['_LG']->getTranslation(__("Error"));
                    $msg = $GLOBALS['_LG']->getTranslation(__("Please verify, you have selected the reason of your choice."));

                    set_flash("error", $title, $msg);

                    $ret = array();
                    foreach ($_POST['species_picture_main'] as $var => $val) {
                        $ret[] = "species_picture_main:" . $var . ":" . $val;
                    }

                    $param = implode("/", $ret);



                    //die();
                    header("location: " . LINK . "photo/admin_crop/" . $param . "/id_photo:" . $_POST['species_picture_main']['id']);
                    die();
                } else {
                    $GLOBALS['_SQL']->set_history_type(8);
                    $_SQL->sql_delete($species_picture_in_wait);

                    $title = $GLOBALS['_LG']->getTranslation(__("Success"));
                    $msg = $GLOBALS['_LG']->getTranslation(__("The photo has been successfully deleted"));

                    set_flash("success", $title, $msg);
                }

                header("location: " . LINK . "photo/admin_crop/");
                die();
            }

            if (!empty($_GET['id_species_picture_main'])) {
                $species_picture_main['species_picture_main']['id'] = $_POST['species_picture_main']['id'];
            }


            $sql = "SELECT * FROM species_picture_in_wait where id='" . $_POST['species_picture_main']['id'] . "'";

            $res = $_SQL->sql_query($sql);

            $this->data['species_picture_main'] = $_SQL->sql_to_array($res);
            $species_picture_main['species_picture_main'] = $this->data['species_picture_main'][0];
            $species_picture_main['species_picture_main']['id_species_picture_info'] = $_POST['species_picture_main']['id_species_picture_info'];
            $species_picture_main['species_picture_main']['id_species_sub'] = $_POST['species_picture_main']['id_species_sub'];
            $species_picture_main['species_picture_main']['id_species_main'] = $_POST['species_picture_main']['id_species_main'];
            $species_picture_main['species_picture_main']['id_species_pictures_type'] = 1;


            ( $_POST['species_picture_main']['id_species_sub'] === NULL ) ? $_POST['species_picture_main']['id_species_sub'] = 0 : NULL;

            (empty($species_picture_main['species_picture_main']['id_species_sub'])) ? 0 : $_POST['species_picture_main']['id_species_sub'];
            $species_picture_main['species_picture_main']['crop'] = $_POST['species_picture_main']['crop_x1'] . ";" . $_POST['species_picture_main']['crop_y1'] . ";" . $_POST['species_picture_main']['crop_x2'] . ";" . $_POST['species_picture_main']['crop_y2'];
            $species_picture_main['species_picture_main']['date_validated'] = Date("Y-m-d H:i:s");





            $glob = unserialize(base64_decode($_POST['img']));


            //die();

            switch ($glob['license']['text']) {
                case "Tous droits réservés":
                    $species_picture_main['species_picture_main']['id_licence'] = 1;
                    break;

                case "Certains droits réservés (licence Creative Commons)":
                    switch ($glob['license']['url']) {
                        case "http://creativecommons.org/licenses/by/2.0/":
                            $species_picture_main['species_picture_main']['id_licence'] = 5;
                            break;
                        case "http://creativecommons.org/licenses/by-sa/2.0/":
                            $species_picture_main['species_picture_main']['id_licence'] = 6;
                            break;
                        case "http://creativecommons.org/licenses/by-nd/2.0/":
                            $species_picture_main['species_picture_main']['id_licence'] = 7;
                            break;
                        case "http://creativecommons.org/licenses/by-nc/2.0/":
                            $species_picture_main['species_picture_main']['id_licence'] = 8;
                            break;
                        case "http://creativecommons.org/licenses/by-nc-sa/2.0/":
                            $species_picture_main['species_picture_main']['id_licence'] = 9;
                            break;
                        case "http://creativecommons.org/licenses/by-nc-nd/2.0/":
                            $species_picture_main['species_picture_main']['id_licence'] = 10;
                            break;
                        default:
                            die("need to add a new license CC");
                            break;
                    }
                    break;

                default:
                    $species_picture_main['species_picture_main']['id_licence'] = 11;
                    break;
            }


            if (empty($glob['author'])) {
                $glob['author'] = $species_picture_main['species_picture_main']['author'];
            }





            if (!empty($glob['author'])) {
                $author["species_author"]["surname"] = $glob['author'];

                $sql = "SELECT id from species_author where surname ='" . $_SQL->sql_real_escape_string($glob['author']) . "'";
                $res = $_SQL->sql_query($sql);

                if ($_SQL->sql_num_rows($res) == 1) {
                    $ob = $_SQL->sql_fetch_object($res);

                    $species_picture_main['species_picture_main']['id_author'] = $ob->id;
                } else {

                    if (!$_SQL->sql_save($author)) {
                        die("problem insertion author");
                    } else {
                        $species_picture_main['species_picture_main']['id_author'] = $_SQL->sql_insert_id();
                    }
                }
            } else {
                $species_picture_main['species_picture_main']['id_author'] = 1;
            }


            $GLOBALS['_SQL']->set_history_type(1);
            if ($_SQL->sql_save($species_picture_main)) {


                //effacement de la ligne dans la table species_picture_in_wait
                //todo pb avec delete SELECT count(1) as cpt FROM history_action WHERE `id` = ''
                $GLOBALS['_SQL']->set_history_type(11);
                $_SQL->sql_delete($species_picture_in_wait);

                // traitement des tag
                $tag = array();
                if (!empty($glob['tag'])) {
                    $link = array();

                    foreach ($glob['tag'] as $value) {
                        unset($tag);
                        $tag['species_picture_tag']['tag'] = trim(mb_strtolower($value, 'UTF-8'));
                        $id_species_picture_tag = $_SQL->sql_save($tag);

                        if ($id_species_picture_tag) {
                            unset($link);
                            $link['link__species_picture__species_picture_tag']['id_species_picture_main'] = $species_picture_main['species_picture_main']['id'];
                            $link['link__species_picture__species_picture_tag']['id_species_picture_tag'] = $id_species_picture_tag;

                            if (!$_SQL->sql_save($link)) {
                                debug($link);
                                debug($_SQL->sql_error());
                                die("problem insertion link tag picture");
                            }
                        } else {
                            debug($_SQL->sql_error());

                            debug($_SQL->query);
                            die("problem insertion tag");
                        }
                    }
                }
                // traitement des tag

                $sql = "SELECT * FROM species_tree_name where id = '" . $species_picture_main['species_picture_main']['id_species_main'] . "'";
                $res = $_SQL->sql_query($sql);
                $ob = mysql_fetch_object($res);

                $species_name = str_replace(" ", "_", $ob->species_);
                $path = "Eukaryota/{$ob->kingdom}/{$ob->phylum}/{$ob->class}/{$ob->order2}/{$ob->family}/{$ob->genus}/" . $species_name;
                $picture_name = $species_picture_main['species_picture_main']['id'] . "-" . $species_name . ".jpg";

                exec("mkdir -p " . TMP . "crop/" . SIZE_SITE_MAX . "x/" . $path);

                $path_890 = TMP . "crop/" . SIZE_SITE_MAX . "x/" . $path . DS . $picture_name;
                $cmd = "mv " . TMP . "picture/" . SIZE_SITE_MAX . "/" . $species_picture_main['species_picture_main']["name"] . " " . $path_890;
                shell_exec($cmd);

                //pour le backup
                $url_dest = DATA . "img/" . $path;

                exec("mkdir -p " . $url_dest);

                $path_1024 = $url_dest . DS . $picture_name;

                if ($this->data['species_picture_main'][0]["width"] > SIZE_BACKUP) {
                    include_once LIB . 'imageprocessor.lib.php';
                    $ImageProcessor = new ImageProcessor();
                    $ImageProcessor->Load(TMP . "photos_in_wait/" . $this->data['species_picture_main'][0]["name"]);
                    $ImageProcessor->Resize(SIZE_BACKUP, null, RESIZE_STRETCH);
                    $ImageProcessor->Save($path_1024, 100);
                } else {
                    $cmd = "cp " . TMP . "photos_in_wait/" . $species_picture_main['species_picture_main']["name"] . " " . $path_1024;
                    shell_exec($cmd);
                }

                //end backup
                //generation miniature 250px
                $url_dest_pic_big = TMP . "crop/" . SIZE_MINIATURE_BIG . "x" . SIZE_MINIATURE_BIG . "/" . $path;
                $url_dest_pic_min = TMP . "crop/" . SIZE_MINIATURE_SMALL . "x" . SIZE_MINIATURE_SMALL . "/" . $path;

                exec("mkdir -p " . $url_dest_pic_big);
                exec("mkdir -p " . $url_dest_pic_min);

                include_once LIB . 'imageprocessor.lib.php';
                $ImageProcessor = new ImageProcessor();
                $ImageProcessor->Load($path_890);
                $ImageProcessor->Crop($_POST['species_picture_main']['crop_x1'], $_POST['species_picture_main']['crop_y1'], $_POST['species_picture_main']['crop_x2'], $_POST['species_picture_main']['crop_y2']);
                $ImageProcessor->Resize(SIZE_MINIATURE_BIG, SIZE_MINIATURE_BIG, RESIZE_STRETCH);
                $ImageProcessor->Save($url_dest_pic_big . "/" . $picture_name, 100);

                //generation miniature 158px
                $ImageProcessor->Resize(SIZE_MINIATURE_SMALL, SIZE_MINIATURE_SMALL, RESIZE_STRETCH);
                $ImageProcessor->Save($url_dest_pic_min . "/" . $picture_name, 100);


                $title = $GLOBALS['_LG']->getTranslation(__("Picture croped"));
                $msg = $GLOBALS['_LG']->getTranslation(__("The picture has been croped with success"));

                set_flash("success", $title, $msg);



                header("location: " . LINK . "photo/admin_crop/");
                die();
            } else {
                //error

                $error = $_SQL->sql_error();
                $_SESSION['ERROR'] = $error;

                if (is_array($_SESSION['ERROR'])) {

                    $_SESSION['ERROR'] = array_merge($_SESSION['ERROR'], $error);
                } else {
                    $_SESSION = $error;
                }

                if (count($_SESSION['ERROR']['species_picture_main']) != 0) {
                    $li = "invalid field :<br /><ul>";

                    foreach ($_SESSION['ERROR']['species_picture_main'] as $key => $value) {
                        $li .= "<li>" . $key . " : " . __($value) . "</li>";
                    }
                    $li .= "</ul>";
                }


                $title = $GLOBALS['_LG']->getTranslation(__("Error"));
                $msg = $GLOBALS['_LG']->getTranslation($li);

                set_flash("error", $title, $msg);

                $ret = array();
                foreach ($_POST['species_picture_main'] as $var => $val) {
                    $ret[] = "species_picture_main:" . $var . ":" . $val;
                }

                $param = implode("/", $ret);



                //die();
                header("location: " . LINK . "photo/admin_crop/" . $param . "/id_photo:" . $_POST['species_picture_main']['id']);
                die();
            }
        }





        /*         * ******************************** */






        if (!empty($_GET['id_species_picture_main'])) {

            $sql = "SELECT a.id_species_main, a.*, c.`scientific_name`,b.id as id_photo, b.*, c.scientific_name,e.*,z.*
			FROM `species_tree_id` a
			INNER JOIN species_picture_main b ON b.id_species_main = a.id_species_main
			INNER JOIN species_main c ON c.id = a.id_species_main
			INNER JOIN species_tree_name e ON e.id = a.id_species_main
			INNER JOIN species_translation z ON z.id_row = a.id_species_main and id_table = 7
			WHERE b.id = '" . mysql_real_escape_string($_GET['id_species_picture_main']) . "'";
        } else {
            if (!empty($_GET['id_photo'])) {
                $contrainte = " and b.id = '" . mysql_real_escape_string($_GET['id_photo']) . "'";
            } else {
                $contrainte = "";
            }

            $sql = "SELECT count(1) FROM `species_tree_id` a
			INNER JOIN species_picture_in_wait b ON b.id_species_main = a.id_species_main
			where id_species_picture_info = 0 AND id_species_family = 438 AND id_history_etat = '1' " . $contrainte;

            //echo $sql;

            $r = $_SQL->sql_query($sql);
            $d = mysql_fetch_row($r);

            $rand = rand(0, $d[0]);
            if (empty($rand))
                $rand = 0;
            if ($d[0] == 1)
                $rand = 0;

            $sql = "SELECT a.id_species_main, a.*, c.`scientific_name`,b.id as id_photo, b.*, c.scientific_name,e.*,z.*
			FROM `species_tree_id` a
			INNER JOIN species_picture_in_wait b ON b.id_species_main = a.id_species_main
			INNER JOIN species_main c ON c.id = a.id_species_main
			INNER JOIN species_tree_name e ON e.id = a.id_species_main
			LEFT JOIN species_translation z ON z.id_row = a.id_species_main and id_table = 7
			
			WHERE id_species_picture_info = 0 AND id_species_family =438
			AND b.id_history_etat = '1'
			" . $contrainte . " 
			LIMIT " . $rand . ", 1";

            //echo $sql;
        }


        $res = $_SQL->sql_query($sql);



        if (mysql_num_rows($res) > 0) {
            $this->data['species'] = $_SQL->sql_to_array($res);
        } else {
            die("error / pas d'images pour cette espA?A?ce");
        }


        if (!empty($this->data['species']['0']['crop'])) {
            $crop = explode(";", $this->data['species']['0']['crop']);
        } else {
            $crop = array(0, 0, 250, 250);
        }

        $this->javascript = array("jquery-1.4.2.min.js", "jquery.imgareaselect.pack.js", "jquery.autocomplete.min.js");


        $this->code_javascript[] = '$("#species_picture_main-id_author-auto").autocomplete("' . LINK . 'user/author/", {
		mustMatch: true,
		autoFill: true,
		max: 100,
		scrollHeight: 302,
		delay:0}
	);';

        $sql = "SELECT id, scientific_name as libelle FROM species_kingdom order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['species_kingdom'] = $_SQL->sql_to_array($res);


        $sql = "SELECT id, scientific_name as libelle FROM species_phylum WHERE id_species_kingdom = " . $this->data['species']['0']['id_species_kingdom'] . " order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['species_phylum'] = $_SQL->sql_to_array($res);


        $sql = "SELECT id, scientific_name as libelle FROM species_class WHERE id_species_phylum = " . $this->data['species']['0']['id_species_phylum'] . " order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['species_class'] = $_SQL->sql_to_array($res);


        $sql = "SELECT id, scientific_name as libelle FROM species_order WHERE id_species_class = " . $this->data['species']['0']['id_species_class'] . " order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['species_order'] = $_SQL->sql_to_array($res);


        $sql = "SELECT id, scientific_name as libelle FROM species_family WHERE id_species_order = " . $this->data['species']['0']['id_species_order'] . " order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['species_family'] = $_SQL->sql_to_array($res);


        $sql = "SELECT id, scientific_name as libelle FROM species_genus WHERE id_species_family = " . $this->data['species']['0']['id_species_family'] . " order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['species_genus'] = $_SQL->sql_to_array($res);


        $sql = "SELECT id, scientific_name as libelle FROM species_main WHERE id_species_genus = " . $this->data['species']['0']['id_species_genus'] . " order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['species_main'] = $_SQL->sql_to_array($res);


        $sql = "SELECT id, scientific_name as libelle FROM species_sub WHERE id_species_main = " . $this->data['species']['0']['id_species_main'] . " order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['species_sub'] = $_SQL->sql_to_array($res);



        $sql = "SELECT id, libelle as libelle FROM licence order By id";
        $res = $_SQL->sql_query($sql);
        $this->data['licence'] = $_SQL->sql_to_array($res);




//accept
        $sql = "SELECT id, libelle as libelle, type FROM species_picture_info where `type` = 1 order BY type, cf_order";
        $res = $_SQL->sql_query($sql);

        $i = 0;
        while ($ob = mysql_fetch_object($res)) {
            $i++;

            $this->data['pic_info'][$i]['id'] = $ob->id;
            $this->data['pic_info'][$i]['libelle'] = __($ob->libelle);

            if (empty($this->data['species']['0']['id_species_picture_info'])) {
                $this->data['species']['0']['id_species_picture_info'] = 0;
            }
        }


        //refuse
        $sql = "SELECT id, libelle as libelle, type FROM species_picture_info where `type` = 3 order BY type, cf_order";
        $res = $_SQL->sql_query($sql);

        $i = 0;
        while ($ob = mysql_fetch_object($res)) {
            $i++;

            $this->data['pic_info2'][$i]['id'] = $ob->id;
            $this->data['pic_info2'][$i]['libelle'] = __($ob->libelle);

            if (empty($this->data['species']['0']['id_species_picture_info2'])) {
                $this->data['species']['0']['id_species_picture_info2'] = 0;
            }
        }


        if (empty($this->data['species']['0']['data'])) {

            include(LIBRARY . "Glial/parser/flickr/flickr.php");
            include_once (LIB . "wlHtmlDom.php");



            //use gliale\flickr;
            $this->data['img'] = flickr::get_photo_info($this->data['species']['0']['url_context']);

            if ($this->data['img']) {

                unset($tmp);
                $tmp['species_picture_in_wait']['id'] = $this->data['species']['0']['id_photo'];
                $tmp['species_picture_in_wait']['data'] = base64_encode(serialize($this->data['img']));

                $GLOBALS['_SQL']->set_history_user(9);
                $GLOBALS['_SQL']->set_history_type(10);
                if (!$_SQL->sql_save($tmp)) {
                    die("Problem insertion data dans species_picture_in_wait");
                    set_flash("error", "Error", "Hum really strange !");
                }
            }
        } else {
            $this->data['img'] = unserialize(base64_decode($this->data['species']['0']['data']));
        }

        //debug(TMP);

        $file = TMP . "photos_in_wait/" . $this->data['species'][0]["name"];



        if ($this->data['img']) {
            if (file_exists($file)) {
                $size = getimagesize($file);

                switch ($size['mime']) {
                    case "image/gif":

                        $cmd = "rm " . TMP . "photos_in_wait/" . $this->data['species'][0]["name"];
                        shell_exec($cmd);

                        $cmd = "cd " . TMP . "photos_in_wait/; wget " . $this->data['img']['photo'] . "";

                        shell_exec($cmd);

                        $elem = explode("/", $this->data['img']['photo']);

                        $this->data['species'][0]["name"] = $elem[count($elem) - 1];


                        $file = TMP . "photos_in_wait/" . $this->data['species'][0]["name"];
                        //die();

                        break;
                    case "image/jpeg":
                        //echo "Image is a jpeg";
                        break;
                    case "image/png":
                        //echo "Image is a png";
                        break;
                    case "image/bmp":
                        //echo "Image is a bmp";
                        break;
                }
            }
        }

        if (!file_exists($file)) {
            if (!file_exists($this->data['species']['0']['url_found'])) {

                set_flash("caution", "Error", "On DL avec WGET !");

                $cmd = "cd " . TMP . "photos_in_wait/; wget " . $this->data['species']['0']['url_found'] . "";
                shell_exec($cmd);
            } else {
                die("not found : " . $this->data['species']['0']['url_found']);

                header("location: " . LINK . "photo/admin_crop/");
            }
        }

        if ($this->data['species'][0]["width"] > SIZE_SITE_MAX) {
            include_once LIB . 'imageprocessor.lib.php';

            $ImageProcessor = new ImageProcessor();
            $ImageProcessor->Load(TMP . "photos_in_wait/" . $this->data['species'][0]["name"]);
            $ImageProcessor->Resize(SIZE_SITE_MAX, null, RESIZE_STRETCH);
            //$ImageProcessor->Rotate(90);
            $ImageProcessor->Save(TMP . "picture/" . SIZE_SITE_MAX . "/" . $this->data['species'][0]["name"], 100);
        } else {
            $cmd = "cp " . TMP . "photos_in_wait/" . $this->data['species'][0]["name"] . " " . TMP . "picture/" . SIZE_SITE_MAX . "/" . $this->data['species'][0]["name"];
            shell_exec($cmd);
        }




        //debug($this->data['img']);
        //debug($this->data['species']);
        $this->set('data', $this->data);



        $size = getimagesize(TMP . "picture/" . SIZE_SITE_MAX . "/" . $this->data['species'][0]["name"]);


        $this->code_javascript[] = "
function preview(img, selection) {
    if (!selection.width || !selection.height)
        return;
    
    var scaleX = " . SIZE_MINIATURE_SMALL . " / selection.width;
    var scaleY = " . SIZE_MINIATURE_SMALL . " / selection.height;

    $('#preview img').css({
        width: Math.round(scaleX * " . $size[0] . "),
        height: Math.round(scaleY * " . $size[1] . "),
        marginLeft: -Math.round(scaleX * selection.x1),
        marginTop: -Math.round(scaleY * selection.y1)
    });

    $('#species_picture_main-crop_x1').val(selection.x1);
    $('#species_picture_main-crop_y1').val(selection.y1);
    $('#species_picture_main-crop_x2').val(selection.x2);
    $('#species_picture_main-crop_y2').val(selection.y2);
    $('#species_picture_main-crop_weight').val(selection.width);
    $('#species_picture_main-crop_height').val(selection.height);    
}

$('#none-id_species_kingdom').change(function() {
  
  $('#none-id_species_phylum').load('" . LINK . "photo/get_options/species_phylum/'+$('#none-id_species_kingdom').val());
  $('#none-id_species_class').html('');
  $('#none-id_species_order').html('');
  $('#none-id_species_family').html('');
  $('#none-id_species_genus').html('');
  $('#species_picture_main-id_species_main').html('');
  $('#species_picture_main-id_species_sub').html('');
});

$('#none-id_species_phylum').change(function() {
  $('#none-id_species_class').load('" . LINK . "photo/get_options/species_class/'+$('#none-id_species_phylum').val());
  $('#none-id_species_order').html('');
  $('#none-id_species_family').html('');
  $('#none-id_species_genus').html('');
  $('#species_picture_main-id_species_main').html('');
  $('#species_picture_main-id_species_sub').html('');
});


$('#none-id_species_class').change(function() {
  $('#none-id_species_order').load('" . LINK . "photo/get_options/species_order/'+$('#none-id_species_class').val());
  $('#none-id_species_family').html('');
  $('#none-id_species_genus').html('');
  $('#species_picture_main-id_species_main').html('');
  $('#species_picture_main-id_species_sub').html('');
});

$('#none-id_species_order').change(function() {
  $('#none-id_species_family').load('" . LINK . "photo/get_options/species_family/'+$('#none-id_species_order').val());
  $('#none-id_species_genus').html('');
  $('#species_picture_main-id_species_main').html('');
  $('#species_picture_main-id_species_sub').html('');
});


$('#none-id_species_family').change(function() {
  $('#none-id_species_genus').load('" . LINK . "photo/get_options/species_genus/'+$('#none-id_species_family').val());
  $('#species_picture_main-id_species_main').html('');
  $('#species_picture_main-id_species_sub').html('');
});

$('#none-id_species_genus').change(function() {
  $('#species_picture_main-id_species_main').load('" . LINK . "photo/get_options/species_main/'+$('#none-id_species_genus').val());
  $('#species_picture_main-id_species_sub').html('');
});

$('#species_picture_main-id_species_main').change(function() {
  $('#species_picture_main-id_species_sub').load('" . LINK . "photo/get_options/species_sub/'+$('#species_picture_main-id_species_main').val());
});

$(function () {
    var ias = $('#photo').imgAreaSelect({ aspectRatio: '1:1', 
		onSelectChange: preview,
		x1: " . $crop[0] . ", y1: " . $crop[1] . ", x2: " . $crop[2] . ", y2: " . $crop[3] . ",
		onInit: preview,
		minHeight: 250,
		minWidth: 250,
		handles: true
	});
	
});";
    }

    function get_options($param) {
        //debug($param);

        $table = $param[0];
        $id = $param[1];

        $id_table['species_phylum'] = "id_species_kingdom";
        $id_table['species_class'] = "id_species_phylum";
        $id_table['species_order'] = "id_species_class";
        $id_table['species_family'] = "id_species_order";
        $id_table['species_genus'] = "id_species_family";
        $id_table['species_main'] = "id_species_genus";
        $id_table['species_sub'] = "id_species_main";



        $this->layout_name = false;
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "SELECT id, scientific_name as libelle FROM `" . mysql_real_escape_string($table) . "` WHERE `" . $id_table[$table] . "` = " . mysql_real_escape_string($id) . " order By scientific_name";
        $res = $_SQL->sql_query($sql);
        $this->data['elem'] = $_SQL->sql_to_array($res);
        $this->data['id'] = $id;
        $this->set('data', $this->data);
    }

    function menu() {
        
    }

    function admin_movie() {

        $module['picture'] = "administration/movie2.gif";
        $module['name'] = __("Movies");
        $module['description'] = __("Import, edit and resize a movie");

        return $module;
    }

    function dl_picture() {
        $this->layout_name = false;

        $_SQL = Singleton::getInstance(SQL_DRIVER);
        $sql = "SELECT * from species_picture_in_wait";


        $sql = "SELECT * from species_picture_in_wait a
			inner join species_tree_id b on a.id_species_main = b.id_species_main where b.id_species_family = 438";

        $res = $_SQL->sql_query($sql);


        $i = 0;


        while ($ob = mysql_fetch_object($res)) {

            $file = TMP . "photos_in_wait/" . $ob->name;

            if (!file_exists($file)) {
                $cmd = "cd " . TMP . "photos_in_wait/; wget " . $ob->url_found . "";
                shell_exec($cmd);

                sleep(3);

                $i++;
                echo "image numA?A©ro : " . $i . "\n";
            }
        }
    }

}

?>