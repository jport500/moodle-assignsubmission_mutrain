<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * CE Credit submission plugin site-wide settings.
 *
 * @package    assignsubmission_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Note: This is off by default — must be explicitly enabled per assignment.
$settings->add(new admin_setting_configcheckbox(
    'assignsubmission_mutrain/default',
    new lang_string('default', 'assignsubmission_mutrain'),
    new lang_string('default_help', 'assignsubmission_mutrain'),
    0
));

$settings->add(new admin_setting_configtext(
    'assignsubmission_mutrain/maxhours',
    new lang_string('maxhours', 'assignsubmission_mutrain'),
    new lang_string('maxhours_help', 'assignsubmission_mutrain'),
    20,
    PARAM_INT
));
