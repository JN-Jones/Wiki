<?php
$l['wiki'] = "Wiki";


/* Menü */
$l['wiki_index'] = "Übersicht";
$l['wiki_article'] = "Artikel";
$l['wiki_option'] = "Einstellungen";
$l['wiki_import'] = "Importieren";
$l['wiki_update'] = "Update";


/* Übersicht/Stats */
$l['wiki_info'] = "Wiki Infos";
$l['wiki_pl_info'] = "PluginLibrary Infos";
$l['wiki_stat'] = "Wiki Statistiken";
$l['wiki_installed'] = "Installierte Version";
$l['wiki_uploaded'] = "Version der Datei";
$l['wiki_newest'] = "Neueste Version";
$l['wiki_buildwith'] = "Aktuelle Version bei Veröffentlichung";
$l['wiki_num_art'] = "Anzahl Artikel";
$l['wiki_num_cat'] = "Anzahl Kategorien";
$l['wiki_num_trash'] = "Anzahl im Mülleimer befindlicher Artikel";
$l['wiki_num_vers'] = "Gesamtzahl geschriebener Artikel inkl Versionen";
$l['wiki_update_message'] = "Du benutzt die Dateien von Version \"{1}\", hast aber zuletzt Version \"{2}\"installiert.<br />Bitte führe ein Update durch.";
$l['wiki_version_error'] = "Du hast Version \"{1}\"installiert und die Dateien von \"{2}\".<br />Bitte lade wieder die Dateien von Version \"{1}\" hoch.";
$l['wiki_pl_version_error'] = "Die Version deiner PluginLibrary ({1}) ist kleiner  als die zum Zeitpunkt der Veröffentlichung aktuellen ({2}).<br />Dies kann zu ungewünschten Fehlern führen.";
$l['wiki_version_update'] = "Du hast Version \"{1}\"installiert, die neueste Version ist \"{2}\".<br />Bitte führe ein Update durch.";


/* Liste */
$l['wiki_list_art'] = "Artikel";
$l['wiki_list_cat'] = "Kategorien";
$l['wiki_list_trash'] = "Mülleimer";
$l['wiki_list_export'] = "Alle exportieren";
$l['wiki_list_art_desc'] = "Liste alle Artikel auf";
$l['wiki_list_cat_desc'] = "Liste alle Kategorien auf";
$l['wiki_list_trash_desc'] = "Liste alle im Mülleimer befindlichen Artikel auf";
$l['wiki_list_export_desc'] = "Exportiere alle Artikel und Kategorien";

$l['wiki_cat_title'] = "Kategorie Titel";
$l['wiki_cat_number'] = "Anzahl Artikel";
$l['wiki_cat_no'] = "Keine Kategorien vorhanden";
$l['wiki_export'] = "Exportieren";

$l['wiki_art_title'] = "Artikel Titel";
$l['wiki_art_art'] = "Artikel Art";
$l['wiki_art_art_link'] = "Link";
$l['wiki_art_art_text'] = "Text";
$l['wiki_art_art_error'] = "Fehler";
$l['wiki_art_short'] = "Kurzbeschreibung";
$l['wiki_art_user'] = "Benutzer";
$l['wiki_art_date'] = "Datum";
$l['wiki_art_no'] = "Keine Artikel vorhanden";

$l['wiki_trash_from'] = "Gelöscht von";
$l['wiki_trash_date'] = "Gelöscht am";

$l['wiki_export_cat_comment'] = "Export Datei des MyBB Wiki Plugin - Kategorie - {time}";
$l['wiki_export_art_comment'] = "Export Datei des MyBB Wiki Plugin - Artikel - {time}";
$l['wiki_export_comment'] = "Export Datei des MyBB Wiki Plugin - Komplett - {time}";
$l['wiki_export_no_data'] = "Konnte keine Daten zum exportieren bilden";


/* Import */
$l['wiki_import_desc'] = "Wähle eine .xml Datei zum Importieren.<br /><span style=\"color: #FF0000;\">Achtung: Wenn du Daten eines anderen Forums importierst, kann dies zu Fehlern bei der Anzeige des Benutzernamens führen.</span>";
$l['wiki_import_success'] = "Datei erfolgreich importiert.<br />{1} Artikel importiert<br />{2} Kategorien importiert<br />{3} Artikel im Mülleimer importiert<br />{4} Versionen importiert";
$l['wiki_import_success_new_cat'] = "<br />Alle Kategorien denen keine Kategorie zugeordnet werden konnte wurden zur Kategorie {1} hinzugefügt";
$l['wiki_import_no_file'] = "Keine Datei angegeben";
$l['wiki_import_error_file'] = "Fehler beim Hochladen der Datei";
$l['wiki_import_invalid_file'] = "Keine .xml Datei";


/* Update */
$l['wiki_update_submit'] = "Starte Update";
$l['wiki_update_submit_desc'] = "Du kannst von Version \"{1}\" auf Version \"{2}\" updaten.";
$l['wiki_update_no'] = "Kein Update";
$l['wiki_update_no_desc'] = "Es ist kein Update verfügbar. Bitte lade zuerst die neuen Dateien hoch und starte dann das Update";
$l['wiki_update_ok'] = "Zurück";
$l['wiki_canceled'] = "Update abgebrochen";
$l['wiki_success'] = "Update erfolgreich";
$l['wiki_error'] = "Unbekannter Fehler beim Update";


/* Permissions */
$l['wiki_permission_index'] = "Kann die übersicht sehen";
$l['wiki_permission_article'] = "Kann alle Artikel sehen";
$l['wiki_permission_import'] = "Kann Artikel & Kategorieren importieren";
$l['wiki_permission_update'] = "Kann ein Update durchführen";


/* Plugin Library */
$l['wiki_pl_missing'] = "Die Installation konnte nicht gestartet werden, da die <b><a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a></b> fehlt";
$l['wiki_pl_old'] = "Die Installation konnte nicht gestartet werden, da die <b><a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a></b> zu alt ist (Version 8 oder höher erforderlich)";
?>