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
	"types.change.successful" => "Change was successful.",
	'types.zip.error' => 'The following error occurred during ZIP file processing: [error].',
	'types.import.cantwrite' => 'Cannot store media file \'[file]\' on server.',
	'types.import.duplicate_id' => 'The textual identifier \'[id]\' of type \'[type]\' for \'[title]\' has already been used for another object.',
	'types.import.id_missing' => 'The textual identifier \'[id]\' of type \'[type]\' is not defined anywhere.',
	'types.import.invalid_type' => 'Object type \'[type]\' in \'[title]\' is invalid.',
	'types.import.missing_obj_text' => 'An object is missing its textual content in \'[title]\'.',
	'types.import.nomedia' => 'The media file \'[file]\' was not found in the uploaded archive.',
	'types.import.invalid_media' => 'The file \'[file]\' is no valid media file.',
	'types.import.success' => 'Test \'[title]\' successfully imported.',
	'types.import.error' => 'Test could not be imported.',
	'types.import.warning' => 'The import file is broken. The test has been imported, but may contain errors.',

	"types.user.account_deactivated" => "Your account was deactivated",
	"types.user.account_deleted" => "Your account was deleted",
	"types.user.account_activation_needed" => "Please check your e-mail inbox and confirm your registration first",
	"types.user.invalid_login" => "Invalid username and/or password",
	"types.user.error.not_allowed_to_edit_admin" => "Only the super user is allowed to edit other Admins",
	"types.user.error.username_too_short" => "Username is too short",
	"types.user.error.username_too_long" => "Username is too long",
	"types.user.error.username_exists" => "This username is already in use",
	"types.user.error.fullname_too_short" => "Fullname is too short",
	"types.user.error.fullname_too_long" => "Fullname is too long",
	"types.user.error.password_too_short" => "Password is too short",
	"types.user.error.password_mismatch" => "The passwords do not match",
	"types.user.error.password_old_false" => "Old password is false",
	"types.user.error.date_format_false" => "Date format is incorrect",

	"types.user_list.error.self_block" => "You cannot block yourself",
	"types.user_list.error.block_special" => "You cannot block protected accounts.",
	"types.user_list.delete_user.special_account" => "This account is protected from deletion.",
	"types.user_list.delete_user.success" => "The user was successfully deleted",
	"types.user_list.create_user_failed" => "The user could not be created",
	"types.user_list.delete_user.error" => "The user could not be deleted",
	"types.user_list.delete_group.success" => "The group was successfully deleted",
	"types.user_list.confirmation_failed" => "Activation failed, the code is invalid",

	"types.group.error.name_change" => "Groupname could not be changed.",
	"types.group.error.description_change" => "Group description could not be changed.",
	"types.group.error.auto_add" => "Group's auto-add status could not be changed.",

	"types.item_block.order_conflict.condition" => "Item \"[referrer_title]\" cannot be moved before item \"[target_title]\" because of its display conditions.",
	"types.container_block.order_conflict.item_condition" => "The block \"[referrer_title]\" cannot be moved before the block \"[target_title]\" because of display conditions in items within it.",

	"types.structure.auto_created" => "Automatically generated for an old unversioned test",
);

?>
