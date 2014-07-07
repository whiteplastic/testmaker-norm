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
	'database.default_groups.guests.name' => 'Guests',
	'database.default_groups.guests.description' => 'Permissions for unregistered users',

	'database.default_groups.members.name' => 'Normal users',
	'database.default_groups.members.description' => 'Permissions for newly registered users',

	'database.default_groups.creators.name' => 'Creators',
	'database.default_groups.creators.description' => 'Can create tests',

	'database.default_groups.admins.name' => 'Administrators',
	'database.default_groups.admins.description' => 'Can administrate testMaker',

	'database.advanced_groups.tan.name' => 'TAN access virtual group',
	'database.advanced_groups.tan.description' => 'Virtual group that controls TAN-based access',

	'database.advanced_groups.password.name' => 'Password access virtual group',
	'database.advanced_groups.password.description' => 'Virtual group that controls password-based access',
);

?>
