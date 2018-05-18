moShBox 1.4 - mosfetkiller-ShoutBox
Lizensiert unter der GPLv3.


Features:
---------

Die moShBox ist eine experimentelle Shoutbox für phpBB3-Foren.
Ihr Funktionsumfang beläuft sich zum aktuellen Entwicklungszeitpunkt auf:

Ab 1.0:
	- Unterstützung aller im Forum installierten Smilies
	- 24h-Trennlinie

Ab 1.1:
	- Automatische Linkerkennung (nur mit Präfix www. bzw. http://)
	- Einfärben und Verlinken der Benutzernamen

Ab 1.2:
	- Selektive Löschfunktion für Admin und Moderatoren
	- Auflösung von Benutzerreferenzen der Art "@UserXYZ: blah"
	- Smiley-Auswähler

Ab 1.3:
  Technisches:
	- Komplette Überarbeitung aller Codes
	- Zusammenlegung des PHP-Backends in 'moshbox/api.php'
  Funktionalität:
	- Kursive Darstellung von Textabschnitten der Form  *moshbox-tollfind*
	- Unterstützung für Wildcards in Benutzerreferenzen der Art "@UserX*: blah"
	- Aufbrechen überlanger Zeichenketten (Spam!) durch Leerzeichen
	  (Defaultwert ist 150 Zeichen, kann in '/moshbox/api.php' geändert werden)
	- Refresh-Button nach Timeout hinzugefügt
	- Smiley-Auswähler verschönert
	- History-Download
	- Autoscroll ein-/ausschaltbar
	- Infobox für MouseOver-Events

Ab 1.4:
	- Max. History-Tiefe auf 1000 Einträge in Chunks à 100 Einträgen abholbar
	- MouseOver-InfoBox-Text für Eingabefeld verkürzt
	- Fancy Gänsefüßchen funktionieren nun

Eine Demo der moShBox findet sich in ihrem Zuhause, dem Forum von mosfetkiller.de:
http://forum.mosfetkiller.de/


Installation in phpBB-3.0.7-PL1:
--------------------------------

HINWEIS:

Diese Version der moShBox wurde ausschließlich in phpBB-3.0.7-PL1 getestet.
Eine Kompatibilität zu früheren oder aktuelleren Versionen von
phpBB3 wird nicht gewährleistet, ist aber sehr wahrscheinlich.
Die Integration der moShBox erfolgt ausschließlich in subsilver2,
weshalb dieses Theme in installierter Form Grundvoraussetzung ist.


Lade den Inhalt des Ordners 'moshbox-1.4' mithilfe eines
FTP-Client in das Rootverzeichnis deiner phpBB3-Installation.
Einige bereits vorhandene Dateien werden dabei überschrieben.

Öffne dann im Browser die Seite '/moshbox/index.php', relativ
zu deiner phpBB3-Installation. Dies wird die Datenbank deiner
phpBB3-Installation um eine Datenbank für die moShBox erweitern.
Diese Datei muss nur einmalig aufgerufen werden und kann danach
gelöscht werden, was jedoch nicht zwingend notwendig ist, aber aus
Sicherheitsgründen empfohlen wird.

Betrete nun den Administrations-Bereich deines phpBB3-Forums
und leere den Cache (Button 'Den Cache leeren'). Dies zwingt
phpBB3, die Änderungen an der Forenstruktur zu übernehmen.

Folgende Prozedur ist notwendig, um in der moShBox Smilies
verwenden zu können.

Wechsle zum Einbinden der Smilies im Administations-Bereich zur
Seite 'Beiträge' und klicke links im Menü auf 'Smilies'.
Eine neue Seite öffnet sich; klicke dort oben rechts auf den Link
'Smilie-Paket erzeugen'. Ein Link zur eben generierten Datei
'smilies.pak' wird erscheinen. Lade diese Datei auf deinen PC und
lade sie auf den Server in das Verzeichnis '/images/smilies'
innerhalb deiner phpBB3-Installation.

Die Installation ist nun abgeschlossen.

Viel Spaß mit der moShBox! :-)


Benutzung:
----------

Die Benutzung ist intuitiv. :-) Einfach ausprobieren!


Lizenz:
-------

Die moShBox steht unter der GPLv3. Siehe 'gpl.txt'.


Haftung:
--------

Die moShBox befindet sich in der Entwicklung, Bugs sind nicht auszuschließen.
Die Entwickler dieser Shoutbox übernehmen keine Haftung für eventuelle Schäden,
die durch die Benutzung dieser Software entstehen. Siehe 'gpl.txt' für nähere
Lizenzangaben.


Grüße und Dank an:
------------------

- Lukas Palm [kingmassivid] <kingmassivid@gmail.de>
	für das Grundgerüst der moShBox 1.0.

- Paul Wilhelm [Paul] <paul@mosfetkiller.de>;
	für die Erweiterung der moShBox und
	die Publikation der moShBox 1.1, 1.2, 1.3

- die mosfetkiller-Community (http://forum.mosfetkiller.de/),
	ohne die es diese Shoutbox überhaupt nicht geben würde.

- Tango Desktop Project (http://tango.freedesktop.org/Tango_Desktop_Project)
    für die schönen Icons.
