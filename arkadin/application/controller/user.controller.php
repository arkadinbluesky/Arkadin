<?php

class user extends controller {

    public $module_group = "Users & access management";
    public $method_administration = array("user", "roles");

    function index() {
        $this->title = __("Members");
        $this->ariane = "> " . $this->title;

        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "SELECT a.id, a.firstname, a.name, b.id_country, b.libelle, COUNT( d.point ) AS points, e.name as rank, date_last_connected
			FROM user_main a
			INNER JOIN geolocalisation_city b ON b.id = a.id_geolocalisation_city
			INNER JOIN `group` e ON a.id_group = e.id
			LEFT JOIN history_main c ON c.id_user_main = a.id
			LEFT JOIN history_action d ON d.id = c.id_history_action
			WHERE a.is_valid =  '1'
			GROUP BY a.id, e.name, b.libelle
			ORDER BY points DESC,  date_last_connected desc
			LIMIT 50";

        $res = $_SQL->sql_query($sql);

        $data = $_SQL->sql_to_array($res);

        $this->set("data", $data);
    }

    function login($bypass = false) {

        $_SQL = Singleton::getInstance(SQL_DRIVER);

        if ($_SERVER['REQUEST_METHOD'] == "POST" || $bypass) {
            if (!empty($_POST['login']) && !empty($_POST['password'])) {

                if (!$bypass) {
                    $password = $_SQL->sql_real_escape_string(sha1(sha1($_POST['password'] . sha1($_POST['login']))));
                } else {
                    $password = $_POST['password'];
                }

                $sql = "select * from user_main where login = '" . $_SQL->sql_real_escape_string($_POST['login']) . "'"; // and password ='" . $password . "'
                $res = $_SQL->sql_query($sql);

                if ($_SQL->sql_num_rows($res) == 1) {
                    $ob = $_SQL->sql_fetch_object($res);

                    if ($ob->password === $password) {
                        //HTTP_USER_AGENT
                        //problem avec chrome pour les cookies
                        SetCookie("IdUser", $ob->id, time() + 60 * 60 * 24 * 365, '/', $_SERVER['SERVER_NAME'], false, true);
                        SetCookie("Passwd", sha1($password . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']), time() + 60 * 60 * 24 * 365, '/', $_SERVER['SERVER_NAME'], false, true);
                        SetCookie("test", $password . "-" . $_SERVER['HTTP_USER_AGENT'] . "-" . $_SERVER['REMOTE_ADDR'], time() + 60 * 60 * 24 * 365, '/', $_SERVER['SERVER_NAME'], false, true);

                        $sql = "UPDATE user_main SET date_last_login = now() where id='" . $_SQL->sql_real_escape_string($ob->id) . "'";
                        $_SQL->sql_query($sql);

                        $this->log($ob->id, true);

                        if ($bypass) {
                            return;
                        }



                        header("location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    } else {
                        $this->log($ob->id, false);

                        $msg = $GLOBALS['_LG']->getTranslation(__("Your Password is Incorrect! Please try again."));
                        $title = $GLOBALS['_LG']->getTranslation(__("Error"));

                        set_flash("error", $title, $msg);

                        header("location: " . $_SERVER['HTTP_REFERER']);
                        exit;
                    }
                } else {

                    $msg = $GLOBALS['_LG']->getTranslation(__("Your login information was incorrect. Please try again."));
                    $title = $GLOBALS['_LG']->getTranslation(__("Invalid login !"));

                    set_flash("error", $title, $msg);

                    header("location: " . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            }

            if (!empty($_POST['logout'])) {
                SetCookie("Passwd", "", time() + 60 * 60 * 24 * 365, '/', $_SERVER['SERVER_NAME'], false, true);

                $title = $GLOBALS['_LG']->getTranslation(__("Logout !"));
                $msg = $GLOBALS['_LG']->getTranslation(__("You have been fully disconnectionected from your account"));

                set_flash("success", $title, $msg);

                header("location: " . WWW_ROOT);
                exit;
            }
        }

        $this->data['new_mail'] = $this->get_new_mail();
        $this->set("data", $this->data);
    }

    function is_logged() {

        die(); // voir dans le boot.php

        global $_SITE;
        $_SQL = Singleton::getInstance(SQL_DRIVER);
        $_SITE['IdUser'] = -1;
        $_SITE['id_group'] = 1;

        if (!empty($_COOKIE['IdUser']) && !empty($_COOKIE['Passwd'])) {
            $sql = "select * from user_main where id = '" . $_SQL->sql_real_escape_string($_COOKIE['IdUser']) . "'";
            $res = $_SQL->sql_query($sql);

            //debug("wdxfrgwdfgwdfg");
            //die();


            if ($_SQL->sql_num_rows($res) == 1) {
                $ob = $_SQL->sql_fetch_object($res);

                debug($ob);

                if ($ob->password === $_COOKIE['Passwd']) {
                    $_SITE['IdUser'] = $_COOKIE['IdUser'];
                    $_SITE['Name'] = $ob->name;
                    $_SITE['FirstName'] = $ob->firstname;
                    $_SITE['id_group'] = $ob->id_group;

                    $GLOBALS['_SITE']['id_group'] = $_SITE['id_group'];

                    $sql = "UPDATE user_main SET date_last_connected = now() where id='" . $_SQL->sql_real_escape_string($_SITE['IdUser']) . "'";
                    $_SQL->sql_query($sql);
                }
            }
        }


        $this->set("_SITE", $_SITE);
    }

    function block_newsletter() {
        //Vous ï¿½tes maintenant abonnï¿½ ï¿½ la lettre d'information.
        //Veuillez renseigner le champ correctement...
        //include_once("class/mail.lib.php");
        $_MSG = "";

        if (!empty($_POST['newsletter'])) {
            if (mail::IsSyntaxEmail($_POST['newsletter'])) {
                $sql = "select * from UserNewsLetter where Email = '" . $_SQL->sql_real_escape_string($_POST['newsletter']) . "'";
                $res = sql::sql_query($sql);


                if ($_SQL->sql_num_rows($res) != 0) {
                    $_MSG = __("You are removed from our newslettter");
                    $sql = "DELETE FROM UserNewsLetter where Email = '" . $_SQL->sql_real_escape_string($_POST['newsletter']) . "'";
                    sql::sql_query($sql);
                } else {
                    $sql = "INSERT INTO UserNewsLetter SET 
					Email = '" . $_SQL->sql_real_escape_string($_POST['newsletter']) . "', 
					IP='" . $_SERVER['REMOTE_ADDR'] . "', 
					UserAgent='" . $_SERVER['HTTP_USER_AGENT'] . "', 
					DateInserted=now()";

                    sql::sql_query($sql);

                    $_MSG = __("Your Email has been added !");
                }
            } else {

                $_MSG = __("Your Email is not valid !");
            }
        }
    }

    function city() {
        /*
          [path] => en/user/city/
          [q] => paris
          [limit] => 10
          [timestamp] => 1297207840432
          [lg] => en
          [url] => user/city/

         */


        $this->layout_name = false;
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "SELECT libelle, id FROM geolocalisation_city WHERE libelle LIKE '" . $_SQL->sql_real_escape_string($_GET['q']) . "%' 
		AND id_geolocalisation_country='" . $_SQL->sql_real_escape_string($_GET['country']) . "' ORDER BY libelle LIMIT 0,100";
        $res = $_SQL->sql_query($sql);
        $data = $_SQL->sql_to_array($res);
        $this->set("data", $data);
    }

    function author() {
        /*
          [path] => en/user/city/
          [q] => paris
          [limit] => 10
          [timestamp] => 1297207840432
          [lg] => en
          [url] => user/city/
         */


        $this->layout_name = false;
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "SELECT firstname, name, id FROM species_author WHERE name LIKE '" . $_SQL->sql_real_escape_string($_GET['q']) . "%' OR firstname LIKE '" . $_SQL->sql_real_escape_string($_GET['q']) . "%'
		ORDER BY name, firstname LIMIT 0,100";
        $res = $_SQL->sql_query($sql);
        $data = $_SQL->sql_to_array($res);
        $this->set("data", $data);
    }

    function register() {

        $this->title = __("Registration");
        $this->ariane = "> <a href=\"" . LINK . "user/\">" . __("Members") . "</a> > " . $this->title;

        $this->javascript = array("jquery.1.3.2.js", "jquery.autocomplete.min.js");
        $this->code_javascript[] = '$("#user_main-id_geolocalisation_city-auto").autocomplete("' . LINK . 'user/city/", {
		extraParams: {
			country: function() {return $("#user_main-id_geolocalisation_country").val();}
		},
		mustMatch: true,
		autoFill: true,
		max: 100,
		scrollHeight: 302,
		delay:0
		});
		$("#user_main-id_geolocalisation_city-auto").result(function(event, data, formatted) {
			if (data)
				$("#user_main-id_geolocalisation_city").val(data[1]);
		});
		$("#user_main-id_geolocalisation_country").change( function() 
		{
			$("#user_main-id_geolocalisation_city-auto").val("");
			$("#user_main-id_geolocalisation_city").val("");
		} ); 

		';

        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "SELECT id, libelle from geolocalisation_country where libelle != '' order by libelle asc";
        $res = $_SQL->sql_query($sql);
        $this->data['geolocalisation_country'] = $_SQL->sql_to_array($res);

        $this->set('data', $this->data);

        if (!empty($_POST['user_main'])) {

            if (!empty($_COOKIE['IdUser'])) {

                $msg = $GLOBALS['_LG']->getTranslation(__("You are already registered under the account id : ") . $_COOKIE['IdUser']);
                $title = $GLOBALS['_LG']->getTranslation(__("Error"));
                set_flash("error", $title, $msg);
                header("location: " . WWW_ROOT);
                exit;
            }


            include_once APP_DIR . DS . "model" . DS . "user_main" . ".php";
            include_once APP_DIR . DS . "model" . DS . "geolocalisation_country" . ".php";

            $data = array();
            $data['user_main'] = $_POST['user_main'];
            $data['user_main']['login'] = $data['user_main']['email'];
            $data['user_main']['ip'] = $_SERVER['REMOTE_ADDR'];
            $data['user_main']['date_last_login'] = "0000-00-00";
            $data['user_main']['date_last_connected'] = "0000-00-00";
            $data['user_main']['date_created'] = date("c");
            $data['user_main']['key_auth'] = sha1(uniqid());
            $data['user_main']['name'] = mb_convert_case($data['user_main']['name'], MB_CASE_UPPER, "UTF-8");

            $data['user_main']['password'] = sha1(sha1($data['user_main']['password'] . sha1($data['user_main']['email'])));
            $_POST['user_main']['password2'] = sha1(sha1($data['user_main']['password2'] . sha1($data['user_main']['email'])));

            //to set uppercase to composed name like 'Jean-Louis'
            $firstname = str_replace("-", " - ", $data['user_main']['firstname']);
            $firstname = mb_convert_case($firstname, MB_CASE_TITLE, "UTF-8");

            $data['user_main']['firstname'] = str_replace(" - ", "-", $firstname);

            if (!$_SQL->sql_save($data)) {

                $error = $_SQL->sql_error();
                $_SESSION['ERROR'] = $error;

                $title = $GLOBALS['_LG']->getTranslation(__("Registration error"));
                $msg = $GLOBALS['_LG']->getTranslation(__("One or more problem came when you try to register your account, please verify your informations"));

                set_flash("error", $title, $msg);

                unset($_POST['user_main']['password']);
                unset($_POST['user_main']['password2']);

                $ret = array();
                foreach ($_POST['user_main'] as $var => $val) {
                    $ret[] = "user_main:" . $var . ":" . urlencode($val);
                }

                $param = implode("/", $ret);

                header("location: " . LINK . "user/register/" . $param);
                exit;
            } else {

                $subject = __("Confirm your registration on Arkadin");

                $msg = __('Hello') . ' ' . $data['user_main']['firstname'] . ' ' . $data['user_main']['name'] . ' !<br />
				' . __('Thank you for registering on Arkadin.') . '<br />
				<br />
				' . __("To finalise your registration, please click on the confirmation link below. Once you've done this, your registration will be complete.") . '<br />
				' . __('Please') . ' <a href="' . LINK . 'user/confirmation/' . $data['user_main']['email'] . "/" . $data['user_main']['key_auth'] . '"> ' . __('click here') . '</a> ' . __('to confirm your registration
				or copy and paste the following URL into your browser:') . '
				' . LINK . 'user/confirmation/' . $data['user_main']['email'] . '/' . $data['user_main']['key_auth'] . '<br />
                <br />
				' . __('Many thanks');

                $msg = $GLOBALS['_LG']->getTranslation($msg);

                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

                // En-tetes additionnels
                $headers .= 'To: ' . $data['user_main']['firstname'] . ' ' . $data['user_main']['name'] . ' <' . $data['user_main']['email'] . '>' . "\r\n";
                $headers .= 'From: Contact <noreply@arkadin.com>' . "\r\n";
                //$headers .= 'Cc: anniversaire_archive@example.com' . "\r\n";
                //$headers .= 'Bcc: anniversaire_verif@example.com' . "\r\n";

                mail($data['user_main']['email'], $subject, $msg, $headers) or die("error mail");


                $msg = __('Welcome! You are now registered as a member.') . "<br/>";
                $msg .= __("In a few seconds you'll receive an email from our system with the link of validation of your account. Remember to configure your account preferences. Hope you can enjoy our services.") . "<br /><br />";
                $msg .= __("Thank you for registering on Arkadin !") . "<br/>";

                $msg = $GLOBALS['_LG']->getTranslation($msg);
                $title = $GLOBALS['_LG']->getTranslation(__("New user account created !"));
                set_flash("success", $title, $msg);


                $_POST['login'] = $data['user_main']['login'];
                $_POST['password'] = $data['user_main']['password'];


                $this->login(true);

                header("location: " . LINK . "home/");
                exit;
            }
        }
    }

    function lost_password() {
        $this->title = __("Password forgotten ?");
        $this->ariane = "> <a href=\"" . LINK . "user/\">" . __("Members") . "</a> > " . $this->title;

        if (!empty($_POST['user_main']['email'])) {

            $_SQL = Singleton::getInstance(SQL_DRIVER);

            $sql = "SELECT * FROM user_main WHERE email='" . $_SQL->sql_real_escape_string($_POST['user_main']['email']) . "'";

            $res = $_SQL->sql_query($sql);

            if ($_SQL->sql_num_rows($res) === 0) {

                $title = $GLOBALS['_LG']->getTranslation(__("Error"));
                $msg = $GLOBALS['_LG']->getTranslation(__("This email does not exist in our database"));
                set_flash("error", $title, $msg);

                $ret = array();
                foreach ($_POST['user_main'] as $var => $val) {
                    $ret[] = "user_main:" . $var . ":" . urlencode($val);
                }

                $param = implode("/", $ret);

                header("location: " . LINK . "user/lost_password/" . $param);
                exit;
            } else {

                $ob = $_SQL->sql_fetch_object($res);

                $recover = array();
                $recover['user_main']['id'] = $ob->id;
                $recover['user_main']['key_auth'] = sha1(uniqid());
                if (!$_SQL->sql_save($recover)) {
                    die('problem with set key_auth');
                }

                $subject = __("Instructions to Recover your password on : ") . " Arkadin";
                $msg = __('Hello') . ' ' . $ob->firstname . ' ' . $ob->name . ' !<br />
				<br />
				' . __("To finalise of recover your password, please click on the following link :") . '<br />
				' . LINK . 'user/password_recover/' . $ob->email . '/' . $recover['user_main']['key_auth'] . '<br />
                <br />
				' . __('Many thanks');

                $subject = $GLOBALS['_LG']->getTranslation($subject);
                $msg = $GLOBALS['_LG']->getTranslation($msg);

                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

                // En-tetes additionnels
                $headers .= 'To: ' . $ob->firstname . ' ' . $ob->name . ' <' . $ob->email . '>' . "\r\n";
                $headers .= 'From: Contact <noreply@arkadin.com>' . "\r\n";
                //$headers .= 'Cc: anniversaire_archive@example.com' . "\r\n";
                //$headers .= 'Bcc: anniversaire_verif@example.com' . "\r\n";


                mail($ob->email, $subject, $msg, $headers) or die("error mail");

                $title = $GLOBALS['_LG']->getTranslation(__("Instructions sent !"));
                $msg = $GLOBALS['_LG']->getTranslation(__("In a few seconds you'll receive an email from our system with the informations to recover your password."));
                set_flash("success", $title, $msg);


                header("location: " . LINK . "user/lost_password/");
                exit;
            }
        }
    }

    function password_recover($param) {

        $_SQL = Singleton::getInstance(SQL_DRIVER);


        $this->title = __("Recover your password");
        $this->ariane = "> <a href=\"" . LINK . "user/\">" . __("Members") . "</a> > " . $this->title;

        $sql = "SELECT * FROM user_main WHERE email='" . $_SQL->sql_real_escape_string($param[0]) . "'
			AND key_auth='" . $_SQL->sql_real_escape_string($param[1]) . "'";

        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) === 0) {
            $title = $GLOBALS['_LG']->getTranslation(__("Error"));
            $msg = $GLOBALS['_LG']->getTranslation(__("This link to recover your password is not valid anymore. Make a new request."));
            set_flash("error", $title, $msg);

            header("location: " . LINK . "user/lost_password/" . $param);
            exit;
        } else {
            if ($_SERVER['REQUEST_METHOD'] == "POST") {

                $ob = $_SQL->sql_fetch_object($res);

                $recover = array();
                $recover['user_main']['id'] = $ob->id;
                $recover['user_main']['password'] = $_POST['user_main']['password'];


                if ($_SQL->sql_save($recover)) {
                    $tmp = array();
                    $tmp['user_main']['id'] = $ob->id;
                    $tmp['user_main']['key_auth'] = "";
                    $tmp['user_main']['password'] = sha1(sha1($_POST['user_main']['password'] . sha1($ob->email)));
                    $_POST['user_main']['password2'] = sha1(sha1($_POST['user_main']['password'] . sha1($ob->email)));

                    if (!$_SQL->sql_save($tmp)) {
                        $error = $_SQL->sql_error();
                        print_r($error);
                        print_r($tmp);

                        die('problem with delete key_auth');
                    }

// zeb33tlndoorsos

                    $_POST['login'] = $ob->login;
                    $_POST['password'] = $tmp['user_main']['password'];
                    $this->login(true);

                    $title = $GLOBALS['_LG']->getTranslation(__("Success"));
                    $msg = $GLOBALS['_LG']->getTranslation(__("Your password has been updated successfully"));

                    set_flash("success", $title, $msg);
                    header("location: " . LINK . "home/index");
                    die();
                } else {
                    $error = $_SQL->sql_error();
                    $_SESSION['ERROR'] = $error;

                    $title = $GLOBALS['_LG']->getTranslation(__("Error"));
                    $msg = $GLOBALS['_LG']->getTranslation(__("One or more problem came when you try to update your password, please verify your informations"));
                    set_flash("error", $title, $msg);

                    header("location: " . LINK . "user/password_recover/" . $param[0] . "/" . $param[1]);
                    exit;
                }
            }
        }
    }

    function block_last_registered() {
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "select a.name, a.firstname, lower(b.iso) as iso, a.date_created, a.id from user_main a
		INNER JOIN geolocalisation_country b ON a.id_geolocalisation_country = b.id
		where is_valid ='1' order by date_created DESC LIMIT 10";
        $res = $_SQL->sql_query($sql);
        $data = $_SQL->sql_to_array($res);
        $this->set("data", $data);
    }

    function admin_user() {
        $module = array();
        $module['picture'] = "administration/ico-users.gif";
        $module['name'] = __("Users");
        $module['description'] = __("Manage users who can access");

        return $module;
    }

    function confirmation($data) {

        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "SELECT * FROM user_main WHERE email = '" . $_SQL->sql_real_escape_string($data[0]) . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);

            if (($ob->key_auth == $data[1]) && !empty($ob->key_auth)) {
                $type = "success";
                $title = "New user account confirmed !";
                $msg = "Your registration is now complete !";

                $sql = "UPDATE user_main SET is_valid = 1, key_auth ='',id_group=2  WHERE email = '" . $_SQL->sql_real_escape_string($data[0]) . "'";
                $_SQL->sql_query($sql);


                $_POST['login'] = $ob->login;
                $_POST['password'] = $ob->password;
                $this->login(true);
            } else {
                $type = "error";
                $title = "Error";
                $msg = "This confirmation is not valid anymore !";
            }
        } else {
            $type = "error";
            $title = "Error";
            $msg = "This account doesn't exist anymore !";
        }


        $title = $GLOBALS['_LG']->getTranslation(__($title));
        $msg = $GLOBALS['_LG']->getTranslation(__($msg));

        //unset($_SESSION['msg_flash']);
        set_flash($type, $title, $msg);


        header("location: " . LINK . "home/");
        exit;
    }

    private function log($id_user, $success) {
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $data = array();
        $data['user_main_login']['id_user_main'] = $id_user;
        $data['user_main_login']['date'] = date("c");
        $data['user_main_login']['ip'] = $_SERVER['REMOTE_ADDR'];
        $data['user_main_login']['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $data['user_main_login']['is_logged'] = $success;

        if (!$gg = $_SQL->sql_save($data)) {
            var_dump($success);
            debug($_SQL->error);
            debug($gg);
            die();
        }
    }

    public function profil($param) {

        $this->layout_name = "admin";

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (!empty($_POST['shoutbox']['text'])) {
                $data = array();
                $data['shoutbox'] = $_POST['shoutbox'];
                $data['shoutbox']['id_user_main'] = $GLOBALS['_SITE']['IdUser'];
                $data['shoutbox']['id_user_main__box'] = $GLOBALS['_SQL']->sql_real_escape_string($param[0]);
                $data['shoutbox']['date'] = date("c");
                $data['shoutbox']['id_history_etat'] = 1;

                if (!$GLOBALS['_SQL']->sql_save($data)) {
                    debug($GLOBALS['_SQL']->sql_error());
                    die("problem to save msg en shoutbox");
                }

                header("location: " . LINK . "user/profil/" . $param[0]);
                exit;
            }
        }
        $this->data['id'] = $GLOBALS['_SQL']->sql_real_escape_string($param[0]);

        $sql = "SELECT  a.id_user_main, a.date, a.text, name,firstname, c.iso, b.id
			FROM shoutbox a
			INNER JOIN user_main b ON a.id_user_main = b.id
			INNER JOIN geolocalisation_country c ON c.id = b.id_geolocalisation_country
			WHERE a.id_history_etat=1
			AND id_user_main__box = " . $GLOBALS['_SQL']->sql_real_escape_string($param[0]) . "
			ORDER BY a.date asc";

        $res = $GLOBALS['_SQL']->sql_query($sql);
        $this->data['shoutbox'] = $GLOBALS['_SQL']->sql_to_array($res);



        $sql = "SELECT * FROM user_main a
		INNER JOIN geolocalisation_country b ON a.id_geolocalisation_country = b.id
		INNER JOIN geolocalisation_city c ON a.id_geolocalisation_city = c.id
		
where a.id ='" . $GLOBALS['_SQL']->sql_real_escape_string($param[0]) . "'";
        $res = $GLOBALS['_SQL']->sql_query($sql);

        $user = $GLOBALS['_SQL']->sql_to_array($res);
        $this->data['user'] = $user[0];

        $this->title = $this->data['user']['firstname'] . ' ' . $this->data['user']['name'];
        $this->ariane = "> <a href=\"" . LINK . "user/\">" . __("Members") . "</a> > " . $this->title;

        $this->data['name'] = $this->title;

        $sql = "SELECT title, id, point FROM history_action WHERE point !=0 ORDER BY title";
        $res = $GLOBALS['_SQL']->sql_query($sql);

        $this->data['actions'] = $GLOBALS['_SQL']->sql_to_array($res);

        $sql = "SELECT d.id, COUNT( d.point ) AS points, point
FROM history_main c
LEFT JOIN history_action d ON d.id = c.id_history_action
WHERE c.id_user_main =  '" . $GLOBALS['_SQL']->sql_real_escape_string($param[0]) . "' and d.point != 0
GROUP BY d.id";
        $res = $GLOBALS['_SQL']->sql_query($sql);
        $tab_point = $GLOBALS['_SQL']->sql_to_array($res);


        foreach ($tab_point as $line) {
            $this->data['points'][$line['id']] = $line['points'];
        }

        $this->set("data", $this->data);
    }

    function mailbox($param) {


        $this->layout_name = "admin";

        $this->data['options'] = array("all_mails", "inbox", "sent_mail", "trash", "compose", "msg");
        $this->data['display'] = array("All mails", "Inbox", "Sent mail", "Trash", "Compose", "Message");


        $this->data['request'] = $param[0];

        if (!in_array($param[0], $this->data['options'])) {
            exit;
        }

        $_SQL = Singleton::getInstance(SQL_DRIVER);


        $sql = "SELECT * FROM user_main a
		INNER JOIN geolocalisation_country b ON a.id_geolocalisation_country = b.id
		INNER JOIN geolocalisation_city c ON a.id_geolocalisation_city = c.id
		
where a.id ='" . $GLOBALS['_SQL']->sql_real_escape_string($GLOBALS['_SITE']['IdUser']) . "'";
        $res = $GLOBALS['_SQL']->sql_query($sql);

        $user = $GLOBALS['_SQL']->sql_to_array($res);
        $this->data['user'] = $user[0];


        $i = 0;
        foreach ($this->data['options'] as $line) {
            if ($line === $this->data['request']) {
                $this->title = __($this->data['display'][$i]);

                $this->ariane = "> <a href=\"" . LINK . "user/\">" . __("Members") . "</a> > "
                        . '<a href="' . LINK . 'user/' . $GLOBALS['_SITE']['IdUser'] . '">' . $this->data['user']['firstname'] . ' ' . $this->data['user']['name'] . '</a>'
                        . ' > ';

                ($this->data['request'] != "all_mails") ? $this->ariane .= '<a href="' . LINK . 'user/mailbox/all_mails">' . __('Mailbox') . '</a>' : $this->ariane .= __('Mailbox');
                ($this->data['request'] != "all_mails") ? $this->ariane .= ' > ' . $this->title : "";

                break;
            }
            $i++;
        }

        switch ($this->data['request']) {

            case "compose":
                if ($_SERVER['REQUEST_METHOD'] == "POST") {

                    debug($_POST);

                    if (!empty($_POST['mailbox_main']['id_user_main__to'])) {
                        $data = array();
                        $data['mailbox_main'] = $_POST['mailbox_main'];
                        $data['mailbox_main']['date'] = date('c');
                        $data['mailbox_main']['id_user_main__box'] = $GLOBALS['_SITE']['IdUser'];
                        $data['mailbox_main']['id_user_main__from'] = $GLOBALS['_SITE']['IdUser'];
                        $data['mailbox_main']['id_mailbox_etat'] = 2;
                        $data['mailbox_main']['id_history_etat'] = 1;

                        if ($GLOBALS['_SQL']->sql_save($data)) {
                            $data['mailbox_main']['id_user_main__box'] = $_POST['mailbox_main']['id_user_main__to'];
                            if ($GLOBALS['_SQL']->sql_save($data)) {
                                $msg = $GLOBALS['_LG']->getTranslation(__("Your message has been sent."));
                                $title = $GLOBALS['_LG']->getTranslation(__("Success"));

                                set_flash("success", $title, $msg);

                                header("location: " . LINK . "user/mailbox/inbox/");
                                exit;
                            } else {
                                die("Problem insertion boite 2");
                            }
                        } else {
                            die("Problem insertion boite 1");
                        }
                    }
                }

                $this->javascript = array("jquery.1.3.2.js", "jquery.autocomplete.min.js");
                $this->code_javascript[] = '$("#mailbox_main-id_user_main__to-auto").autocomplete("' . LINK . 'user/user_main/", {
					
					mustMatch: true,
					autoFill: false,
					max: 100,
					scrollHeight: 302,
					delay:1
					});
					$("#mailbox_main-id_user_main__to-auto").result(function(event, data, formatted) {
						if (data)
							$("#mailbox_main-id_user_main__to").val(data[1]);
					});


					';
                break;

            case 'inbox':

                $sql = "SELECT a.id,a.title,a.date,id_mailbox_etat,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on b.id_geolocalisation_country = y.id
					
						WHERE id_user_main__box = '" . $GLOBALS['_SITE']['IdUser'] . "'
						AND id_user_main__to = '" . $GLOBALS['_SITE']['IdUser'] . "'
							AND id_history_etat = 1
							ORDER BY date DESC";
                $res = $GLOBALS['_SQL']->sql_query($sql);
                $this->data['mail'] = $GLOBALS['_SQL']->sql_to_array($res);



                break;

            case 'sent_mail':

                $sql = "SELECT a.id,a.title,a.date,id_mailbox_etat,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on b.id_geolocalisation_country = y.id
						WHERE id_user_main__box = '" . $GLOBALS['_SITE']['IdUser'] . "'
						AND id_user_main__from = '" . $GLOBALS['_SITE']['IdUser'] . "'
							AND id_history_etat = 1
							ORDER BY date DESC";
                $res = $GLOBALS['_SQL']->sql_query($sql);
                $this->data['mail'] = $GLOBALS['_SQL']->sql_to_array($res);



                break;


            case 'all_mails':

                $sql = "SELECT a.id,a.title,a.date,id_mailbox_etat,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on b.id_geolocalisation_country = y.id
					
						WHERE id_user_main__box = '" . $GLOBALS['_SITE']['IdUser'] . "'
							AND id_history_etat = 1
							ORDER BY date DESC";
                $res = $GLOBALS['_SQL']->sql_query($sql);
                $this->data['mail'] = $GLOBALS['_SQL']->sql_to_array($res);



                break;


            case 'trash':

                $sql = "SELECT a.id,a.title,a.date,id_mailbox_etat,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on b.id_geolocalisation_country = y.id
						WHERE id_user_main__box = '" . $GLOBALS['_SITE']['IdUser'] . "'
							AND id_history_etat = 3
							ORDER BY date DESC";
                $res = $GLOBALS['_SQL']->sql_query($sql);
                $this->data['mail'] = $GLOBALS['_SQL']->sql_to_array($res);



                break;


            case 'msg':
                $sql = "SELECT a.id,a.title,a.date,a.text as msg,id_mailbox_etat,id_user_main__from,id_user_main__to,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on b.id_geolocalisation_country = y.id
					
						WHERE a.id = '" . $GLOBALS['_SQL']->sql_real_escape_string($param[1]) . "' 
						AND id_user_main__box = '" . $GLOBALS['_SITE']['IdUser'] . "'
							
							AND id_history_etat = 1
							ORDER BY date DESC";
                $res = $GLOBALS['_SQL']->sql_query($sql);
                $this->data['mail'] = $GLOBALS['_SQL']->sql_to_array($res);




                if ($this->data['mail'][0]['id_mailbox_etat'] == 2) {
                    $sql = "UPDATE mailbox_main SET id_mailbox_etat = 1
						WHERE id_user_main__from = '" . $this->data['mail'][0]['id_user_main__from'] . "'
						AND id_user_main__to = '" . $this->data['mail'][0]['id_user_main__to'] . "'
						AND date = '" . $this->data['mail'][0]['date'] . "'";

                    $GLOBALS['_SQL']->sql_query($sql);
                }





                break;


            case 'delete':

                $del = array();

                /*
                  foreach ()
                  {

                  }
                  $sql = "
                 */
                break;
        }


        $this->set("data", $this->data);
    }

    function user_main() {
        /*
          [path] => en/user/city/
          [q] => paris
          [limit] => 10
          [timestamp] => 1297207840432
          [lg] => en
          [url] => user/city/

         */


        $this->layout_name = false;
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "SELECT name, firstname, id FROM user_main WHERE 
			firstname != 'BOT'
			AND id_group > 1
			AND firstname LIKE '" . $_SQL->sql_real_escape_string($_GET['q']) . "%' 
			OR name LIKE '" . $_SQL->sql_real_escape_string($_GET['q']) . "%' 
		ORDER BY firstname,name LIMIT 0,100";
        $res = $_SQL->sql_query($sql);
        $data = $_SQL->sql_to_array($res);
        $this->set("data", $data);
    }

    function settings($param) {

        $this->data['request'] = $param[0];

        if (!empty($param[1])) {
            $this->data['item'] = $param[1];
        } else {
            $this->data['item'] = '';
        }

        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $this->layout_name = "admin";


        $sql = "SELECT * FROM user_main a
			LEFT JOIN user_settings b ON a.id = b.id_user_main
			WHERE a.id='" . $GLOBALS['_SITE']['IdUser'] . "'";

        $res = $_SQL->sql_query($sql);
        $data = $_SQL->sql_to_array($res);
        $this->data['user'] = $data[0];

        $this->title = __("Settings");
        $this->ariane = "> <a href=\"" . LINK . "user/\">" . __("Members") . "</a> > "
                . '<a href="' . LINK . 'user/' . $GLOBALS['_SITE']['IdUser'] . '">' . $this->data['user']['firstname'] . ' ' . $this->data['user']['name'] . '</a>'
                . ' > '
                . $this->title;




        switch ($this->data['request']) {
            case 'main':

                break;
        }


        $this->set("data", $this->data);
    }

    function photo($param) {

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            
        }
    }

    private function get_new_mail() {
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "SELECT count(1) as cpt FROM mailbox_main
			WHERE id_user_main__box = '" . $GLOBALS['_SITE']['IdUser'] . "'
				AND id_user_main__to = '" . $GLOBALS['_SITE']['IdUser'] . "'
					AND id_mailbox_etat =2
					AND id_history_etat = 1";



        $res = $_SQL->sql_query($sql);
        $data = $_SQL->sql_to_array($res);
        return $data[0]["cpt"];
    }

    function block_last_online() {
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $sql = "select a.name, a.firstname, lower(b.iso) as iso, a.date_last_connected, a.id from user_main a
                INNER JOIN geolocalisation_country b ON a.id_geolocalisation_country = b.id
                where is_valid ='1' order by date_last_connected DESC LIMIT 10";
        $res = $_SQL->sql_query($sql);
        $data = $_SQL->sql_to_array($res);
        $this->set("data", $data);
    }

}

