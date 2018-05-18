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

/*****************
 * Konfiguration *
 *****************/
const moShBox_RefreshInterval = 1500;	/* ms */
const moShBox_TimeoutSpan = 1000 * 60 * 10;	/* ms */
const T_THEME_PATH = "styles/subsilver2/theme";



/**********************
 * Globale Variablen. *
 **********************/
const XMLHttpRequest_UNSENT = 0;
const XMLHttpRequest_DONE = 4;
const XMLHttpRequest_found = 200;

/* Objekte müssen verwendet werden, damit diese by reference übergeben werden.
 * Sie werden mit null initialisiert, damit sie vor der Initialisierung durch
 * moShBox_InitMsgs() (davor können sie schon sichtbar sein!) keinen Quatsch enthalten. */
var msgTIMEOUT = Object(); msgTIMEOUT.valueOf = null;
var msgREFRESH = Object(); msgREFRESH.valueOf = null;
var msgUSAGE = Object(); msgUSAGE.valueOf = null;
var msgSUBMIT = Object(); msgSUBMIT.valueOf = null;
var msgSMILEY_SELECTOR = Object(); msgSMILEY_SELECTOR.valueOf = null;
var msgSAVE_HISTORY = Object(); msgSAVE_HISTORY.valueOf = null;
var msgAUTOSCROLL_OFF = Object(); msgAUTOSCROLL_OFF.valueOf = null;
var msgAUTOSCROLL_ON = Object(); msgAUTOSCROLL_ON.valueOf = null;

var moShBox_SmileySelector;
var autoscrollEnabled = true;

var timerRefresh;
var timerTimeout;

var ShoutTextOld;
var CallBoxTextOld;

/* LoadShouts() bekommt ein festes Objekt zugewiesen,
 * damit Ladevorgänge sich nicht überschneiden können. */
var LoadShouts_ajaxObject = getXMLHttpRequestObject();



/************************************************************************
 * Initialisierung. Wird erst durchgeführt, wenn die Seite vollständig  *
 * geladen ist und 'moShBox_Shouts' auf der aktuellen Seite existiert.  *
 ************************************************************************/
function moShBox_Init() {
	if (document.getElementById("moShBox_Shouts") != null) {
		moShBox_InitMsgs();

		ShoutTextOld = "";
		CallBoxTextOld = "";

		moShBox_Refresh();
	}
}



/*******************************************************
 * Browserspezifisches XMLHttpRequest-Objekt erzeugen. *
 * Rückgabe: Objekt.                                   *
 *******************************************************/
function getXMLHttpRequestObject() {
	if (window.XMLHttpRequest) {
		return new XMLHttpRequest();	/* Mozilla, Safari, Opera */
	} else if (window.ActiveXObject) {
		try {
			return new ActiveXObject('Msxml2.XMLHTTP'); /* IE 5 */
		} catch (e) {
			try {
				return new ActiveXObject('Microsoft.XMLHTTP'); /* IE 6 */
			} catch (e) {
				alert("XMLHttpRequest-Objekt konnte nicht erzeugt werden.");
			}
		}
	}
}

/**************************************************
 * AJAX-Request: Sende Daten 'data' an URL 'url'. *
 * Bei Abschluss Funktion 'callback' aufrufen.    *
 * Benutze Objekt 'object'.                       *
 * Rückgabe: Keine.                               *
 **************************************************/
function ajaxRequest(object, url, data, callback) {
	object.open("POST", url, true);
	object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
	object.onreadystatechange = callback;
	object.send(data);
}

/*************************************************************
 * Umgebende Leerzeichen von Zeichenkette 'str' abschneiden. *
 * Rückgabe: Getrimmte Zeichenkette.                         *
 *************************************************************/
function trim(str) {
	str = str.replace(/^\s*(.*)/, "$1");
	str = str.replace(/(.*?)\s*$/, "$1");
	return str;
}



/**********************************************************
 * Eine feste Liste sprachspezifischer Nachrichten laden. *
 * Rückgabe: Keine.                                       *
 **********************************************************/
function moShBox_InitMsgs() {
	moShBox_GetMessage(msgTIMEOUT, "MOSHBOX_TIMEOUT");
	moShBox_GetMessage(msgREFRESH, "MOSHBOX_REFRESH");
	moShBox_GetMessage(msgUSAGE, "MOSHBOX_USAGE");
	moShBox_GetMessage(msgSUBMIT, "MOSHBOX_SUBMIT");
	moShBox_GetMessage(msgSMILEY_SELECTOR, "MOSHBOX_SMILEY_SELECTOR");
	moShBox_GetMessage(msgSAVE_HISTORY, "MOSHBOX_SAVE_HISTORY");
	moShBox_GetMessage(msgAUTOSCROLL_OFF, "MOSHBOX_AUTOSCROLL_OFF");
	moShBox_GetMessage(msgAUTOSCROLL_ON, "MOSHBOX_AUTOSCROLL_ON");
}


/*********************************************************************
 * Sprachspezifische phpBB3-Nachricht 'id' in Objekt 'object' laden. *
 * Rückgabe: Keine.                                                  *
 *********************************************************************/
function moShBox_GetMessage(object, id) {
	var ajaxObject = getXMLHttpRequestObject();

	ajaxRequest(ajaxObject, "moshbox/api.php", "action=GetMessage&id=" + id,
		function () {
			if (ajaxObject.readyState == XMLHttpRequest_DONE && ajaxObject.status == XMLHttpRequest_found) {
				object.valueOf = ajaxObject.responseText;
			}
		}
	);
}

/**************************
 * moShBox aktualisieren. *
 * Rückgabe: Keine.       *
 **************************/
function moShBox_Refresh() {
	/* Timeout nach definierter Zeitspanne erzwingen. */
	window.clearTimeout(timerTimeout);	/* Löschen, da diese Funktion im laufenden Script mehrmals aufgerufen wird. */
	timerTimeout = window.setTimeout("moShBox_Timeout()", moShBox_TimeoutSpan);

	window.clearInterval(timerRefresh);	/* Löschen, da diese Funktion im laufenden Script mehrmals aufgerufen wird. */
	timerRefresh = window.setInterval("moShBox_LoadShouts()", moShBox_RefreshInterval);

	moShBox_LoadShouts();
	//moShBox_UpdateCallBox();
}

/********************
 * moShBox-Timout.  *
 * Rückgabe: Keine. *
 ********************/
function moShBox_Timeout() {
	/* Refresh- und Timeout-Timer stoppen. */
	window.clearInterval(timerRefresh);
	window.clearTimeout(timerTimeout);

	/* Nachricht und Refresh-Button anzeigen. */
	document.getElementById("moShBox_Shouts").innerHTML =
		'<form>' +
		'	<div style="text-align: center;">' +
		'		<br><br><br>' +
		'		' + msgTIMEOUT.valueOf + '<br><br>' +
		'		<button type="button" onClick="moShBox_Init();">' +
		'			<img src="' + T_THEME_PATH + '/images/moshbox-refresh.png" alt="' + msgREFRESH.valueOf +'"><br>' +
		'			<span style="font-weight: bold; font-size: 10px;">' + msgREFRESH.valueOf + '</span>' +
		'		</button>' +
		'	</div>' +
		'</form>';
}

/********************
 * Shout speichern. *
 * Rückgabe: Keine. *
 ********************/
function moShBox_StoreShout() {
	/* String formatieren. */
	var ShoutText = escape(trim(document.moShBox_Form.ShoutText.value));

	/* Keine leeren Strings senden. */
	if (ShoutText != '') {
		var ajaxObject = getXMLHttpRequestObject();

		ajaxRequest(ajaxObject, "moshbox/api.php", "action=StoreShout&message=" + encodeURIComponent(ShoutText),
			function () {
				if (ajaxObject.readyState == XMLHttpRequest_DONE) {
					/* Timeouts resetten und Shoutbox aktualisieren */
					moShBox_Refresh();
				}
			}
		);
	}

	document.moShBox_Form.ShoutText.value = "";
	document.moShBox_Form.ShoutText.focus();
}

/********************
 * Shouts laden.    *
 * Rückgabe: Keine. *
 ********************/
function moShBox_LoadShouts() {
	/* Nachrichten nur Laden, wenn noch kein Timeout erfolgte. Muss sein,
	 * um Aufrufe dieser Routine kurz nach dem Timeout zu unterdrücken.
	 * Außerdem folgenden Code nur ausführen, wenn gerade kein Request
	 * auf diesem Objekt aktiv ist. */
	if (timerTimeout != 0 && (LoadShouts_ajaxObject.readyState == XMLHttpRequest_DONE || LoadShouts_ajaxObject.readyState == XMLHttpRequest_UNSENT)) {
		ajaxRequest(LoadShouts_ajaxObject, "moshbox/api.php", "action=GetShouts",
			function () {
				if (LoadShouts_ajaxObject.readyState == XMLHttpRequest_DONE) {
					var ShoutText = LoadShouts_ajaxObject.responseText;

					if (ShoutText != ShoutTextOld) {
						ShoutTextOld = ShoutText;

						var divShouts = document.getElementById('moShBox_Shouts');
						divShouts.innerHTML = ShoutText;
						if (autoscrollEnabled) divShouts.scrollTop = divShouts.scrollHeight;
					}
				}
			}
		);
	}
}


/*************************************
 * Löscht den Shout mit der ID 'id'. *
 * Rückgabe: Keine.                  *
 *************************************/
function moShBox_RemoveShout(id) {
	var ajaxObject = getXMLHttpRequestObject();

	ajaxRequest(ajaxObject, "moshbox/api.php", "action=RemoveShout&id=" + id, function () {});
}



/****************************
 * Smiley-Auswähler öffnen. *
 * Rückgabe: Keine.         *
 ****************************/
function moShBox_SmileySelector_open() {
	/* Timeouts resetten und Shoutbox aktualisieren */
	moShBox_Refresh();

	/* Fenster des Smiley-Auswählers öffnen und fokussieren. */
	moShBox_SmileySelector = window.open('moshbox/api.php?action=ListSmilies', 'moShBox_SmileySelector', 'status=no,scrollbars=yes,resizable=no,width=220,height=400');
	moShBox_SmileySelector.focus();
}

/*******************************
 * Smiley-Auswähler schließen. *
 * Rückgabe: Keine.            *
 *******************************/
function moShBox_SmileySelector_close() {
	/* Timeouts resetten und Shoutbox aktualisieren */
	moShBox_Refresh();

	/* Smiley-Auswähler schließen und dem Eingabefeld Fokus verpassen. */
	window.moShBox_SmileySelector.close();
	document.moShBox_Form.ShoutText.focus();
}

/**********************
 * Smiley hinzufügen. *
 * Rückgabe: Keine.   *
 **********************/
function moShBox_AddSmiley(smiley) {
	/* Timeouts resetten und Shoutbox aktualisieren */
	moShBox_Refresh();

	/* Smiley-Code (mit Leerzeichen umschlossen) am Ende des Eingabefeldes einfügen. */
	document.moShBox_Form.ShoutText.value += ' ' + smiley + ' ';
}


/*********************************
 * History zum Download anbieten *
 * Rückgabe: Keine.              *
 *********************************/
function moShBox_SaveHistory() {
	/* History-Datei dynamisch vom Backend erzeugen lassen und öffnen. */
	window.location.href = "moshbox/api.php?action=GetHistory";
}


/***********************
 * Autoscroll togglen. *
 * Rückgabe: Keine.    *
 ***********************/
function moShBox_ToggleAutoscroll() {
	var image = document.getElementById("moShBox_AutoscrollMinibutton");

	var img_on = T_THEME_PATH + "/images/moshbox-autoscroll-on.png";
	var img_off = T_THEME_PATH + "/images/moshbox-autoscroll-off.png";

	if (autoscrollEnabled) {
		autoscrollEnabled = false;
		image.src = img_off;
		image.alt = msgAUTOSCROLL_ON.valueOf;
		image.title = msgAUTOSCROLL_ON.valueOf;
	} else {
		autoscrollEnabled = true;
		image.src = img_on;
		image.alt = msgAUTOSCROLL_OFF.valueOf;
		image.title = msgAUTOSCROLL_OFF.valueOf;
	}

	/* Timeouts resetten und Shoutbox aktualisieren */
	moShBox_Refresh();
}


/********************************************************
 * Info-Box mit Nachricht 'id' laden. Achtung, 'id' hat *
 * nichts mit der ID von 'moShBox_GetMessage' zu tun.   *
 * Rückgabe: Keine.                                     *
 ********************************************************/
function moShBox_ShowInfo(id) {
	var msg = "";

	switch (id) {
		case "USAGE":
			msg = msgUSAGE.valueOf;
			break;

		case "SUBMIT":
			msg = msgSUBMIT.valueOf;
			break;

		case "SMILEY_SELECTOR":
			msg = msgSMILEY_SELECTOR.valueOf;
			break;

		case "SAVE_HISTORY":
			msg = msgSAVE_HISTORY.valueOf;
			break;

		case "AUTOSCROLL":
			if (autoscrollEnabled) {
				msg = msgAUTOSCROLL_OFF.valueOf;
			} else {
				msg = msgAUTOSCROLL_ON.valueOf;
			}
			break;
	}

	document.getElementById("moShBox_InfoBox").innerHTML = msg;
}

/********************
 * Info-Box leeren. *
 * Rückgabe: Keine. *
 ********************/
function moShBox_ClearInfo() {
	document.getElementById("moShBox_InfoBox").innerHTML = "";
}


/****************
 *
 ****************/
function moShBox_UpdateCallBox() {
	/* CallBox nur Laden, wenn noch kein Timeout erfolgte. Muss sein,
	 * um Aufrufe dieser Routine kurz nach dem Timeout zu unterdrücken. */
	if (timerTimeout != 0) {
		var ajaxObject = getXMLHttpRequestObject();

		ajaxRequest(ajaxObject, "moshbox/api.php", "action=GetShouts",
			function () {
				if (ajaxObject.readyState == XMLHttpRequest_DONE) {
					var CallBoxText = ajaxObject.responseText;

					if (CallBoxText != CallBoxTextOld) {
						CallBoxTextOld = CallBoxText;

						var divCallBox = document.getElementById('moShBox_CallBox');
						divCallBox.innerHTML = CallBoxText;
						divCallBox.scrollTop = divCallBox.scrollHeight;
					}
				}
			}
		);
	}
}
