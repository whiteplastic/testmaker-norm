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
	'ui.title'     => 'testMaker-Installation',
	'ui.t_welcome' => 'Willkommen zur testMaker-Installation',
	'ui.t_finish'  => 'testMaker-Installation beendet!',

	'ui.phpversion' => 'testMaker setzt PHP ab Version 5 voraus. Installiert ist PHP ' . PHP_VERSION . '.',
	'ui.step.config_edit'         => 'Konfigurationsdatei einrichten',
	'ui.step.config_edit.no_db'   => 'Die angegebene Datenbankkonfiguration funktioniert nicht. Bitte korrigieren Sie eventuelle Fehler.',
	'ui.step.config_edit.title'   => 'Konfigurationsdatei einrichten',
	'ui.step.config_edit.success' => 'Konfigurationsdatei erstellt und gespeichert.',
	'ui.step.upload_perms'        => 'Upload-Berechtigungen prüfen',
	'ui.step.upload_perms.title'  => 'Upload-Berechtigungen prüfen',
	'ui.step.upload_perms.success'=> 'Upload-Berechtigungen sind korrekt eingerichtet.',

	'ui.step.config_edit.no_db_host'   => 'Textfeld Adresse des Datenbankservers ist leer.',
	'ui.step.config_edit.no_db_user'   => 'Textfeld Datenbank-Benutzername ist leer.',
	'ui.step.config_edit.no_db_name'   => 'Textfeld Name der Datenbank ist leer.',
	'ui.step.config_edit.no_db_prefix'   => 'Textfeld Präfix für Tabellennamen ist leer.',
	'ui.step.config_edit.no_system_mail' => 'Textfeld System-E-Mail-Adresse ist leer.',



	'ui.step.db_init'         => 'Datenbank einrichten',
	'ui.step.db_init.title'   => 'Datenbank einrichten',
	'ui.step.db_init.success' => 'Die Datenbank wurde erfolgreich eingerichtet.',

	'ui.step.guest_init'         => 'Gastbenutzer anlegen',
	'ui.step.guest_init.title'   => 'Gastbenutzer anlegen',
	'ui.step.guest_init.success' => 'Der Gastbenutzer wurde erfolgreich angelegt.',

	'ui.step.groups_init'         => 'Standardgruppen anlegen',
	'ui.step.groups_init.title'   => 'Standardgruppen anlegen',
	'ui.step.groups_init.success' => 'Die Standardgruppen wurden erfolgreich angelegt.',

	'ui.step.adv_groups_init'         => 'Virtuelle Gruppen anlegen',
	'ui.step.adv_groups_init.title'   => 'Virtuelle Gruppen anlegen',
	'ui.step.adv_groups_init.success' => 'Die virtuellen Gruppen wurden erfolgreich angelegt.',

	'ui.step.item_templates_init'		=> 'Standard-Templates einrichten',
	'ui.step.item_templates_init.title'	=> 'Standard-Templates einrichten',
	'ui.step.item_templates_init.success'	=> 'Die Standard-Templates wurden erfolgreich eingerichtet',

	'ui.step.create_admin'         => 'Administratorkonto anlegen',
	'ui.step.create_admin.title'   => 'Administratorkonto anlegen',
	'ui.step.create_admin.success' => 'Ein Administratorkonto wurde angelegt.',

	'ui.step.blob_test_runs'         => 'Testlaufdaten konvertieren',
	'ui.step.blob_test_runs.title'   => 'Testlaufdaten konvertieren',
	'ui.step.blob_test_runs.success' => 'Die Testlaufdaten wurden fuer die aktuelle Version konvertiert.',
	
	'ui.step.test_run_duration' 		=> 'Testlaufdaten konvertieren',
	'ui.step.test_run_duration2' 		=> 'Testlaufdaten konvertieren',
	'ui.step.test_run_duration.title'   => 'Testlaufdaten konvertieren',
	'ui.step.test_run_duration.success' 	=> 'Die Testlaufdaten wurden fuer die aktuelle Version konvertiert.',

	'ui.step.test_run_gn2id' 			=> 'Testlaufdaten aktualisieren',
	'ui.step.test_run_gn2id.title'   	=> 'Testlaufdaten aktualisieren',
	'ui.step.test_run_gn2id.success' 	=> 'Die Testlaufdaten wurden fuer die aktuelle Version aktualisiert.',
	
	'ui.step.enter_feedback' 			=> 'Testmaker Feedback',
	'ui.step.enter_feedback.title'   	=> 'Testmaker Feedback',
	'ui.step.enter_feedback.success' 	=> 'Testmaker Feedback erfolgreich versendet.',
	
	'ui.step.kill_test_structure_redu' => 'Testlaufdaten konvertieren',
	
	'ui.step.finish' => 'Installation abschließen',
	'ui.step.cleanMedia' => 'Median Cleaner',
);

?>
