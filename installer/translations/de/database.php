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
	'database.default_groups.guests.name' => 'Gäste',
	'database.default_groups.guests.description' => 'Rechte für unregistrierte Besucher',

	'database.default_groups.members.name' => 'Standardbenutzer',
	'database.default_groups.members.description' => 'Rechte für neu registrierte Benutzer',

	'database.default_groups.creators.name' => 'Autoren',
	'database.default_groups.creators.description' => 'Können Tests erstellen',

	'database.default_groups.admins.name' => 'Administratoren',
	'database.default_groups.admins.description' => 'Können den testMaker administrieren',

	'database.advanced_groups.tan.name' => 'Virtuelle TAN-Zugriffsgruppe',
	'database.advanced_groups.tan.description' => 'Virtuelle Gruppe, die TAN-basierten Zugriff festlegt',

	'database.advanced_groups.password.name' => 'Virtuelle Passwort-Zugriffsgruppe',
	'database.advanced_groups.password.description' => 'Virtuelle Gruppe, die passwortbasierten Zugriff festlegt',
);

?>
