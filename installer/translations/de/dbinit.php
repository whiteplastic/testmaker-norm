<?php

/* This file is part of testMaker.

testMaker is free software; you can redistribute it and/or modify
it under the terms of version 2 of the GNU General Public License as
published by the Free Software Foundation.

testMaker is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */


/**
 * Translation file
 * @package Installer
 */

$TRANSLATIONS = array(
	'dbinit.db_not_found'			=> 'Die Datenbank existiert nicht. Sie sollten zuerst eine anlegen.',
	'dbinit.db_cant_connect'		=> 'Verbindung zum Datenbankserver nicht m&ouml;glich',
	'dbinit.db_connect'				=> 'Verbindung zum Datenbankserver erfolgreich',

	'dbinit.cant_init_table'		=> 'Tabelle "[table]" konnte nicht initialisiert werden: [error]',
	'dbinit.init_table'				=> 'Tabelle "[table]" initialisiert',
	'dbinit.migrate_table'			=> 'Struktur von Tabelle "[table]" aktualisiert',
	'dbinit.migrate.dberr'			=> 'Beim Aktualisieren der Tabellenstruktur von [table] hat die Datenbank diese Fehlermeldung zur&uuml;ckgeliefert: [message]',
	'dbinit.cant_init_sequence'		=> 'Sequenz "[sequence]" konnte nicht initialisiert werden',
	'dbinit.init_sequence'			=> 'Sequenz "[sequence]" angelegt',
	'dbinit.cant_query_groups'		=> 'Gruppenliste konnte nicht ausgelesen werden',
	'dbinit.cant_create_guest_user'	=> 'Gastbenutzer konnte nicht angelegt werden',
	'dbinit.create_guest_user'		=> 'Gastbenutzer angelegt',
	'dbinit.cant_create_group'		=> 'Gruppe "[group]" konnte nicht angelegt werden',
	'dbinit.create_group'			=> 'Gruppe "[group]" angelegt',
	'dbinit.insert_templates'		=> 'Template "[template]" wurde erfolgreich eingef&uuml;gt',
	'dbinit.cant_insert_templates'	=> 'Konnte Template "[template]" nicht einf&uuml;gen',
	'dbinit.cant_query_templates'	=> 'Templateliste konnte nicht ausgelesen werden.',

	'dbinit.cant_query_users'		=> 'Benutzerliste konnte nicht ausgelesen werden',
	'dbinit.cant_create_user'		=> 'Administratorkonto konnte nicht angelegt werden: [error]',

	'dbinit.init_versions'			=> 'Tabellenversionsverwaltung konnte nicht angelegt werden',
	'dbinit.update_version'			=> 'Tabellenversion von "[table]" konnte nicht aktualisiert werden',
	'dbinit.init_version'			=> 'Tabellenversion von "[table]" konnte nich initialisiert werden',

	'dbinit.create'					=> 'Datenbank erfolgreich eingerichtet',
);

?>
