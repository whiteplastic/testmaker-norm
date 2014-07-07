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
 * @package Core
 */

$TRANSLATIONS = array(
	"types.change.successful" => "�nderung war erfolgreich.",
	'types.zip.error' => 'Bei der Behandlung der ZIP-Datei ist folgender Fehler aufgetreten: [error].',
	'types.import.cantwrite' => 'Kann Mediendatei \'[file]\' auf dem Server speichern.',
	'types.import.duplicate_id' => 'Die Objektkennung \'[id]\' f�r \'[title]\' wird bereits f�r ein anderes Objekt verwendet.',
	'types.import.id_missing' => 'Die Objektkennung \'[id]\' f�r Objekttyp \'[type]\' wurde nicht definiert.',
	'types.import.invalid_type' => 'Objekttyp \'[type]\' in \'[title]\' ist ung�ltig.',
	'types.import.missing_obj_text' => 'Es fehlt ein Textinhalt eines Objekts in \'[title]\'.',
	'types.import.missing_plugin.extconds' => 'Das Plugin \'[name]\' f�r erweiterte Feedback-Anzeigebedingungen, das in diesem Test verwendet wird, ist in dieser testMaker-Installation nicht vorhanden. Der importierte Test wird deshalb voraussichtlich nicht korrekt benutzbar sein.',
	'types.import.missing_plugin.feedback' => 'Das Plugin \'[name]\' f�r dynamische Feedback-Inhalte, das in diesem Test verwendet wird, ist in dieser testMaker-Installation nicht vorhanden. Der importierte Test wird deshalb voraussichtlich nicht korrekt benutzbar sein.',
	'types.import.nomedia' => 'Die Mediendatei \'[file]\' wurde im hochgeladenen Archiv nicht gefunden.',
	'types.import.invalid_media' => 'Die Datei \'[file]\' ist keine g�ltige Mediendatei.',
	'types.import.success' => 'Test \'[title]\' erfolgreich importiert.',
	'types.import.error' => 'Test konnte nicht importiert werden.',
	'types.import.warning' => 'Die Importdatei ist besch�digt. Der Test wurde importiert, k�nnte aber fehlerhaft sein.',

	"types.user.account_deactivated" => "Ihr Benutzerkonto wurde deaktiviert.",
	"types.user.account_deleted" => "Ihr Benutzerkonto wurde gel�scht.",
	"types.user.account_activation_needed" => "Bitte erst den Account mit dem Link in der Best�tigungs-E-Mail freischalten.",
	"types.user.invalid_login" => "Benutzername und/oder Passwort ung�ltig.",
	"types.user.error.not_allowed_to_edit_admin" => "Nur der Superuser darf andere Admins bearbeiten",
	"types.user.error.username_too_short" => "Der Benutzername ist zu kurz.",
	"types.user.error.username_too_long" => "Der Benutzername ist zu lang.",
	"types.user.error.username_exists" => "Dieser Benutzername ist bereits vergeben.",
	"types.user.error.fullname_too_short" => "Der vollst�ndige Name ist zu kurz.",
	"types.user.error.fullname_too_long" => "Der vollst�ndige Name ist zu lang.",
	"types.user.error.password_too_short" => "Das Passwort ist zu kurz.",
	"types.user.error.password_mismatch" => "Die Passw�rter stimmen nicht �berein.",
	"types.user.error.password_old_false" => "Altes Passwort stimmt nicht",
	"types.user.error.date_format_false" => "Das Datumsformat ist nicht korrekt",

	"types.user_list.error.self_block" => "Sie k�nnen sich nicht selbst sperren.",
	"types.user_list.error.block_special" => "Sie k�nnen Spezialaccounts nicht deaktivieren.",
	"types.user_list.delete_user.special_account" => "Dieser Spezialaccount kann nicht gel�scht werden.",
	"types.user_list.delete_user.success" => "Der Benutzer wurde erfolgreich gel�scht.",
	"types.user_list.create_user_failed" => "Der Benutzer konnte nicht angelegt werden.", 
	"types.user_list.delete_user.error" => "Der Benutzer konnte nicht gel�scht werden.",
	"types.user_list.delete_group.success" => "Die Gruppe wurde erfolgreich gel�scht.",
	"types.user_list.confirmation_failed" => "Aktivierung fehlgeschlagen, der Code ist ung�ltig.",

	"types.group.error.name_change" => "Gruppen Name konnte nicht ge�ndert werden.",
	"types.group.error.description_change" => "Gruppen Beschreibung konnte nicht ge�ndert werden.",
	"types.group.error.auto_add" => "Auto-add Status der Gruppe konnte nicht ge�ndert werden.",

	"types.item_block.order_conflict.condition" => "Das Item \"[referrer_title]\" kann aufgrund der Anzeigebedingungen nicht vor das Item \"[target_title]\" verschoben werden.",
	"types.container_block.order_conflict.item_condition" => "Der Block \"[referrer_title]\" kann aufgrund der enthaltenen Item-Anzeigebedingungen nicht vor den Block \"[target_title]\" verschoben werden.",

	"types.structure.auto_created" => "Automatisch angelegte Strukturversion f�r einen alten, versionslosen Test",
);

?>
