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
 * CE Credit submission plugin language strings.
 *
 * @package    assignsubmission_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['eventassessableuploaded'] = 'A CE credit certificate has been uploaded.';
$string['pluginname'] = 'CE Credit Submission';
$string['enabled'] = 'CE Credit Submission';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['activityname'] = 'Activity name';
$string['activityname_help'] = 'The name of the CE activity completed';
$string['provider'] = 'Provider / sponsor';
$string['provider_help'] = 'Organisation that provided or sponsored the activity';
$string['dateofactivity'] = 'Date of activity';
$string['dateofactivity_help'] = 'Date when the activity was completed';
$string['credittype'] = 'Credit type';
$string['credittype_help'] = 'Category of credit being claimed';
$string['hoursclaimed'] = 'Hours claimed';
$string['hoursclaimed_help'] = 'Number of CE hours claimed for this activity';
$string['certificate'] = 'Certificate of completion';
$string['certificate_help'] = 'Upload your certificate of completion';
$string['frameworkid'] = 'Credit framework';
$string['frameworkid_help'] = 'The training credit framework credits will be posted to on approval';
$string['credittypes'] = 'Allowed credit types';
$string['credittypes_help'] = 'Comma-separated list of credit type options shown to members';
$string['maxhours'] = 'Maximum hours per submission';
$string['maxhours_help'] = 'Maximum CE hours a member may claim in a single submission';
$string['nosubmission'] = 'Nothing has been submitted';
$string['errornegativehours'] = 'Hours claimed must be greater than zero';
$string['errormaxhours'] = 'Hours claimed cannot exceed {$a}';
$string['errornoframework'] = 'No credit framework is configured for this assignment';
$string['submissionsummary'] = '{$a->activityname} — {$a->provider} ({$a->hoursclaimed} hrs, {$a->credittype})';
