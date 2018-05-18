<?php

	// Change: 2013-09-03 - https-Link Support

	/************************************************************************
	 * moShBox - mosfetkiller-ShoutBox                                      *
	 * Copyright 2008, 2009, 2010                                           *
	 *   Lukas Palm <kingmassivid@gmail.de>,                                *
	 *   Paul Wilhelm <paul@mosfetkiller.de>                                *
	 *                                                                      *
	 * Version 1.4                                                          *
	 *                                                                      *
	 * This file is part of moShBox.                                        *
	 *                                                                      *
	 * moShBox is free software: you can redistribute it and/or modify      *
	 * it under the terms of the GNU General Public License as published by *
	 * the Free Software Foundation, either version 3 of the License, or    *
	 * (at your option) any later version.                                  *
	 *                                                                      *
	 * moShBox is distributed in the hope that it will be useful,           *
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of       *
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        *
	 * GNU General Public License for more details.                         *
	 *                                                                      *
	 * You should have received a copy of the GNU General Public License    *
	 * along with moShBox.  If not, see <http://www.gnu.org/licenses/>.     *
	 ************************************************************************/

	/******************
	 * Konfiguration. *
	 ******************/
	$WORD_SPLIT_LENGTH = 150;	/* Anzahl zusammenhängender Buchstaben, nach
								 * denen ein Leerzeichen eingefügt werden soll. */



	/*********
	 * phpBB *
	 *********/
	define('IN_PHPBB', true);
	$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	include($phpbb_root_path.'common.'.$phpEx);

	$user->session_begin();
	$auth->acl($user->data);
	$user->setup();



	// SeriousD-Fix
	define(MOD_GROUP_ID,4);
	include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);



	/************************************************************************
	 * Dieses Script darf nur von eingeloggten Benutzern aufgerufen werden. *
	 ************************************************************************/
	if ($user->data['user_id'] != ANONYMOUS) {
		/* Mit MySQL-Datenbank verbinden. */
		include($phpbb_root_path.'config.php');
		$link = mysql_connect($dbhost, $dbuser, $dbpasswd) or die("moShBox: Could not connect to database server: " . mysql_error());
		mysql_select_db($dbname) or die("moShBox: Could not select database");



		/**********************************
		 * Per POST übertragene Aktionen. *
		 **********************************/
		if (isset($_POST['action'])) {

 			/*******************************
			 * Shouts aus Datenbank laden. *
			 * Rückgabe: HTML-Code.        *
			 *******************************/
			if ($_POST['action'] == "GetShouts") {
				//	Cachen verhindern
				header("Expires: Sat, 05 Nov 2005 00:00:00 GMT");
				header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);
				header("content-type: text/html; charset=utf-8");
				header("Pragma: no-cache");

				$entries = 50;

				$link = mysql_connect($dbhost, $dbuser, $dbpasswd) or die("Keine Verbindung möglich: " . mysql_error());

				mysql_select_db($dbname) or die("Auswahl der Datenbank fehlgeschlagen");

				$sql = "SET NAMES 'utf8'";
				$result = mysql_query($sql) OR die(mysql_error());

				$sql = "SELECT count(id) AS rownumbr FROM ".$table_prefix."moshbox";
				$result = mysql_query($sql) OR die(mysql_error());
				$row = mysql_fetch_assoc($result);
				$rownumbr = $row['rownumbr'];

				if ($rownumbr < $entries) $entries = $rownumbr;

				$sql = "SELECT * FROM ".$table_prefix."moshbox ORDER BY id ASC LIMIT ".($rownumbr - $entries).",".($entries + 1)."; ";
				$result = mysql_query($sql) OR die(mysql_error());

				/* Smilies einlesen. */
				$smilies_ascii[] = "";
				$smilies_code[] = "";

				$smilies_pak_path = $phpbb_root_path."images/smilies/smilies.pak";

				if (file_exists($smilies_pak_path)) {
					$smilies_pak = file($smilies_pak_path);

					foreach ($smilies_pak as $line) {
						$smilie = explode(',', $line);
						array_push($smilies_ascii, trim($smilie[5], " \t'"));
						array_push($smilies_code, '<img src="./images/smilies/'.trim($smilie[0], " \t'").'" alt="'.trim($smilie[4], " \t'").'" title="'.trim($smilie[4], " \t'").'" width="'.trim($smilie[1], " \t'").'" height="'.trim($smilie[2], " \t'").'">');
					}
				}

				/* Einträge in HTML-Tabelle ausgeben. */
				echo "<table class=\"message\" cellpadding=\"0\" cellspacing=\"0\">\n";

				$numrows = mysql_num_rows($result);		/* Anzahl der anzuzeigenden Einträge. */
				$counter = 0;							/* Aktueller Eintrag (Zähler). */
				$lastdate = "";							/* Letztes Datum (wird zum Erkennen eines Tageswechsels benötigt). */

				while($row = mysql_fetch_assoc($result)) {
					/* Benutzerreferenzen im Format "@USER:" auflösen. */
					$row['message'] = preg_replace_callback("/@(.*?):/", "user_ref_callback", $row['message']);

					/* *mach-mich-kursiv*. */
					$row['message'] = preg_replace_callback("/\*(.*?)\*/", "make_cursive_callback", $row['message']);

					/* Zu lange Wörter mit Leerzeichen aufbrechen. */
					$row['message'] = word_split($row['message'], $WORD_SPLIT_LENGTH);

					/* http-URLs in Links verwandeln. */
					$row['message'] = preg_replace_callback("|http://\S*|", "make_links_callback", $row['message']);
					/* https-URLs in Links verwandeln. */
					$row['message'] = preg_replace_callback("|https://\S*|", "make_links_callback", $row['message']);

					/* ASCII-Smilies durch Grafiken ersetzen. */
					$row['message'] = str_replace($smilies_ascii, $smilies_code, $row['message']);

					/* Benutzer-Farbe (variiert je nach Rang) und -Namen holen. */
					$sql = 'SELECT username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = '.$row['user_id'];
					$result2 = $db->sql_query($sql);
					$row2 = $db->sql_fetchrow($result2);
					$db->sql_freeresult($result2);

					/* Uhrzeit des Eintrags einlesen. */
					$time = explode(' ', $row['time']);

					if ($counter == 0) {
						/* Erster Eintrag. */
						$border = ' style="border-top: 0px;"';
					} else {
						/* Tageswechsel erkennen. */
						$thisdate = strtotime($time[0]);
						if (($thisdate > $lastdate) && ($lastdate > 0)) {
							/* Ja: Trennlinie zeichnen. */
							$border = ' style="border-top: 2px solid #a9b8c2;"';
						} else {
							/* Nein: Keine Trennlinie zeichnen. */
							$border = '';
						}
					}

					$lastdate = $thisdate;

					/* Eintrag in Tabelle einfügen. */

					echo '	<tr>'."\n";

					/* Benutzernamen in Benutzer-Farbe als Link zum Benutzer-Profil. */
					echo '		<td class="name"'.$border.'><a href="memberlist.php?mode=viewprofile&u='.$row['user_id'].'" style="color: #'.$row2['user_colour'].';">'.$row2['username'].'</span></td>'."\n";

					/* Nachricht. */
					echo '		<td class="message"'.$border.'>'.$row['message'].'</td>'."\n";

					/* Datum. */
					echo '		<td class="time"'.$border.'>'.$time[1].'</td>'."\n";


					// SeriousD-Fix
					/* Lösch-Link - Nur für Administratoren (User-Rank 1) und Globale Moderatoren (User-Rank 2). */
					if ($user->data['user_rank'] == 1 || group_memberships(MOD_GROUP_ID, $user->data['user_id'], true)) {
						echo '		<td class="removethis"'.$border.'>[<a href="javascript:moShBox_RemoveShout('.$row['id'].');" class="removethisx">X</a>]</td>'."\n";
					}

					echo '	</tr>'."\n";

					$counter++;
				}

				echo "</table>\n\n";

				exit();
			}



			/**************************************************
			 * Sprachspezifische phpBB3-Nachricht 'id' laden. *
			 * Rückgabe: Nachricht (oder Fehlermeldung).      *
			 **************************************************/
			if ($_POST['action'] == "GetMessage") {
				if (isset($_POST['id'])) {
					if (array_key_exists($_POST['id'], $user->lang)) {
						echo $user->lang[$_POST['id']];
						exit();
					} else {
						die("GetMessage: Message ID not found: ".$_POST['id']);
					}
				} else {
					die("GetMessage: Invalid ID");
				}
			}



			/*******************************************
			 * Shout 'message' in Datenbank speichern. *
			 * Rückgabe: Keine.                        *
			 *******************************************/
			if ($_POST['action'] == "StoreShout") {
				/* Die (De-)Codierung ist noch nicht optimal...da muss noch was gemacht werden. */
				$message = utf8_encode(rawurldecode($_POST['message']));
				$message = str_replace('%u20AC', '€', $message);
				$message = str_replace('%u201E', '„', $message);
				$message = str_replace('%u201C', '“', $message);
				$message = str_replace('%u201A', '‚', $message);
				$message = str_replace('%u2018', '‘', $message);
				$message = htmlspecialchars(mysql_real_escape_string($message));

				$result = $db->sql_query("SET NAMES utf8");

				$sql = "INSERT INTO ".$table_prefix."moshbox
								(user_id, message, ip, time)
							VALUES
								('".$user->data['user_id']."',
								'".$message."',
								'".$_SERVER['REMOTE_ADDR']."',
								NOW() )";

				$result = $db->sql_query($sql) or die(mysql_error());

				$db->sql_freeresult($result);

				exit();
			}



			/********************************************************************
			 * Folgende Funktionen dürfen nur von Administratoren               *
			 * oder Globalen Moderatoren benutzt werden.                        *
			 * Rückgabe: Keine.                                                 *
			 ********************************************************************/

			// SeriousD-Fix
			/**********************************************
			 * Löscht den Eintrag 'id' aus der Datenbank. *
			 **********************************************/
			if ($_POST['action'] == "RemoveShout" && isset($_POST['id']) && is_numeric($_POST['id'])) {

				if ($user->data['user_rank'] == 1 || group_memberships(MOD_GROUP_ID, $user->data['user_id'] , true)) {
					$id = intval($_POST['id']);

					$sql = "DELETE FROM ".$table_prefix."moshbox WHERE id='".$id."'";
					$result = mysql_query($sql) OR die(mysql_error());
				} else {
					die($user->lang['MOSHBOX_NO_PERMISSION']);
				}

				exit();
			}

		}



		/*********************************
		 * Per GET übertragene Aktionen. *
		 *********************************/
		if (isset($_GET['action'])) {

			/****************************************************
			 * Inhalt des Smiley-Auswähler-Fensters generieren. *
			 * Rückgabe: HTML-Seite.                            *
			 ****************************************************/
			if ($_GET['action'] == "ListSmilies") {
				/* HTML-Gerüst. */
				echo "<html><head>\n";
				echo "<title>moShBox-Smilies</title>\n";
				echo "<script language=\"JavaScript\" type=\"text/JavaScript\">\n";
				echo "<!--\n";

				echo "window.moveTo((screen.width/6 * 3)-110,(screen.height/2)-200);\n";
				echo "window.focus();\n";

				echo "-->\n";
				echo "</script>\n";
				echo "</head><body>\n";

				echo "<span style=\"font-family: DejaVu Sans, Verdana; font-size: 10px;\">[<a href=\"javascript:opener.moShBox_SmileySelector_close();\" style=\"font-weight: bold;\">".$user->lang['MOSHBOX_CLOSE_WINDOW']."</a>]</span>\n";
				echo "<br><br>\n";

				/* Smilies einlesen, falls smilies.pak vorhanden. */
				$smilies_pak_path = $phpbb_root_path."images/smilies/smilies.pak";

				if (file_exists($smilies_pak_path)) {
					$smilies_pak = file($smilies_pak_path);

					$smilies = array();

					foreach ($smilies_pak as $line) {
						$smiley = explode(',', $line);
						$smiley_ascii = trim($smiley[5], " \t'");
						$smiley_img = trim($smiley[0], " \t'");
						$smiley_desc = trim($smiley[4], " \t'");

						//	Verschiedene Smiley-Codes mit gleichen Grafiken ausfiltern
						$unique = TRUE;
						foreach ($smilies as $this_smiley) {
							if ($this_smiley[1] == $smiley_img) $unique = FALSE;
						}

						if ($unique) array_push($smilies, array($smiley_ascii, $smiley_img, $smiley_desc));
					}

					//	Smiley-Liste generieren
					$rowsize = 0;

					foreach ($smilies as $smiley) {
						$smiley_imgfile = '../images/smilies/'.$smiley[1];

						$size = getimagesize($smiley_imgfile);
						$rowsize += $size[0];

						if ($rowsize >= 220 - 64) {
							echo "	<br>\n"; $rowsize = $size[0];
						}

						echo "<a href=\"javascript:opener.moShBox_AddSmiley('$smiley[0]');\"><img src=\"$smiley_imgfile\" alt=\"$smiley[2]\" title=\"$smiley[2]\" style=\"border: none;\"></a>\n";
					}

				} else {
					echo "Smiley-Paket nicht gefunden.";
				}

				echo "<br><br>\n";
				echo "<span style=\"font-family: DejaVu Sans, Verdana; font-size: 10px;\">[<a href=\"javascript:opener.moShBox_SmileySelector_close();\" style=\"font-weight: bold;\">".$user->lang['MOSHBOX_CLOSE_WINDOW']."</a>]</span>\n";

				echo "</body></html>\n";

				exit();
			}



			/**************************************
			 * History zum Download anbieten.     *
			 * Rückgabe: Text-Datei (attachment). *
			 **************************************/
			if ($_GET['action'] == "GetHistory") {

				// UTF8-Zeichensatz
				$sql = "SET NAMES 'utf8'";
				$result = mysql_query($sql) OR die(mysql_error());


				//	Benutzernamen samt IDs in Array einlesen
				$username = array();

				$sql = "SELECT username, user_id FROM ".USERS_TABLE;
				$result = mysql_query($sql) OR die(mysql_error());

				while($row = mysql_fetch_assoc($result)) {
					$username[$row['user_id']] = $row['username'];
				}


				//	Einträge als herunterladbare Datei ausgeben
				set_time_limit(100);	// Gibt diesem PHP-Skript 100 weitere Sekunden Ausführungszeit
				header("Content-Type: x-type/subtype");
				header("Content-Disposition: attachment; filename=moShBox_history.txt");

				// Header ausgeben
				echo $user->lang['MOSHBOX_VERSION']." history dump\n".$_SERVER['SERVER_NAME']."\n\n";


				// Ermittle Anzahl der Einträge
				$sql = "SELECT * FROM ".$table_prefix."moshbox ORDER BY id DESC";
				$result = mysql_query($sql) OR die(mysql_error());
				$rows_remaining = mysql_num_rows($result);


				// Begrenze Abfrage auf maximal 1000 Einträge
				$rows_max = 1000;
				if ($rows_remaining > $rows_max) $rows_remaining = $rows_max;


				// Zerlege Abfrage in Chunks von maximal 100 Einträgen
				$chunk_size = 100;


				// Zeiger auf Beginn des ersten Chunks
				$row_start = 0;
				do {

					// Verkleinere letzten Chunk auf die Anzahl verbleibender Einträge
					if ($chunk_size > $rows_remaining) $chunk_size = $rows_remaining;

					// Ausgewählte Einträge abholen
					$sql = "SELECT * FROM ".$table_prefix."moshbox ORDER BY id DESC LIMIT ".$row_start.", ".($row_start + $chunk_size)."";
					$result = mysql_query($sql) OR die(mysql_error());

					// Einträge ausgeben
					while($row = mysql_fetch_assoc($result)) {
						printf("[%s] %s: %s\n", $row['time'], $username[$row['user_id']], $row['message']);
					}


					// Zeiger auf Beginn des nächsten Chunks
					$row_start += $chunk_size;

					// Chunk wurde gelesen
					$rows_remaining -= $chunk_size;

					// Schleife nach letztem Chunk verlassen
					if ($rows_remaining == 0) break;

				} while (1);


				// FERTIG! =)
				exit();
			}

			/* Wenn bis hierhin keine gültige Aktion aufgerufen wurde,
			 * handelt es sich vermutlich um einen Hacker oder so. Fehlermeldung! */
			die($user->lang['MOSHBOX_NO_PERMISSION']);
		}

	} else {
		/* Nachricht, wenn der User nicht eingeloggt ist. */
		echo $user->lang['MOSHBOX_NOT_LOGGED_IN'];
		exit();
	}



	/*************************************************************************/



	/***************************************************
	 * Bricht Wörter mit einer Länge > $word_maxlength *
	 * im Satz $string mit Leerzeichen auf.            *
	 * Rückgabe: String.                               *
	 ***************************************************/
	function word_split($string, $word_maxlength) {
		/*	String in Wörter auftrennen. */
		$words = explode(" ", $string);
		$string = "";

		/* Jedes einzelne Wort checken... */
		foreach($words as $word) {
			/* Wort zu lang? */
			if (strlen($word) > $word_maxlength) {
				/* Ein Leerzeichen einfügen. */
				$word = substr($word, 0, $word_maxlength)." ".substr($word, $word_maxlength, strlen($word) - $word_maxlength);

				/* Rekursiver Aufruf, um den neu erzeugten Satz erneut zu splitten. */
				$word = word_split($word, $word_maxlength);
			}

			/* Wörter mit Leerzeichen verbinden. */
			$string.= $word." ";
		}

		/* Satz zürückgeben, letztes Leerzeichen abschneiden. */
		return substr($string, 0, strlen($string) - 1);
	}



	/***************************************************
	 * Benutzerreferenzen im Format "@USER:" auflösen. *
	 * Rückgabe: HTML-Code.                            *
	 ***************************************************/
	function user_ref_callback($match) {
		/// @+*: schrottet die Box.

		global $db;

		$username = trim(strtolower($match[1]));
		$username = str_replace("+", "", $username);
		if ($username == "*") return $match[0];

		/* Namen in Benutzer-ID auflösen. */
		$sql = 'SELECT user_id, username FROM ' . USERS_TABLE . " WHERE username_clean = '".$username."'";
		$result_temp = $db->sql_query($sql);
		$row_temp = $db->sql_fetchrow($result_temp);
		$db->sql_freeresult($result_temp);

		$userid = $row_temp['user_id'];

		/* Wenn Benutzername nicht in der Datenbank, auf Wildcard '*' checken. */
		if (!$userid) {
			$wildcard_pos = strpos($username, "*");

			/* Es muss immer Text VOR dem Wildcard stehen. */
			if ($wildcard_pos !== FALSE && $wildcard_pos > 0) {
				/* Zeichen vor und nach dem Wildcard. */
				$front = substr($username, 0, $wildcard_pos);
				$back = substr($username, $wildcard_pos + 1, strlen($username) - strlen($front) - 1);

				$sql = 'SELECT user_id, username FROM ' . USERS_TABLE . " WHERE username_clean REGEXP '^".$front."(.*)".$back."'";
				$result_temp = $db->sql_query($sql);
				$row_temp = $db->sql_fetchrow($result_temp);
				$db->sql_freeresult($result_temp);
				$userid = $row_temp['user_id'];
			}

			if (!$userid) {
				/* Wenn bis hierhin nichts funktioniert hat, wird die Referenz halt nicht aufgelöst. */
				return $match[0];
			}
		}

		/* Username korrigieren. */
		$username = $row_temp['username'];

		/* Benutzer-Farbe (variiert je nach Rang) und -Namen holen. */
		$sql = 'SELECT user_colour FROM ' . USERS_TABLE . ' WHERE user_id = '.$userid;
		$result_temp = $db->sql_query($sql);
		$row_temp = $db->sql_fetchrow($result_temp);
		$db->sql_freeresult($result_temp);

		return '<span class="user_reference">@<a href="memberlist.php?mode=viewprofile&u='.$userid.'" style="color: #'.$row_temp['user_colour'].';">'.$username.'</a>:</span>';
	}



	/**************************************************************
	 * Textabschnitte im Format *mach-mich-kursiv* kursiv machen. *
	 * Rückgabe: HTML-Code.                                       *
	 **************************************************************/
	function make_cursive_callback($match) {
		return '<span style="font-style: italic;">'.$match[0].'</span>';
	}


	function make_links_callback($match) {
		return '<a href="'.$match[0].'">'.$match[0].'</a>';
	}

?>
