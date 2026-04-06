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
// phpcs:disable moodle.Files.LineLength.TooLong

/**
 * CE Credit submission plugin.
 *
 * @package    assignsubmission_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('ASSIGNSUBMISSION_MUTRAIN_FILEAREA', 'certificate');

/**
 * CE Credit submission plugin for the assign module.
 *
 * Allows members to submit CE credit claims (activity name, provider,
 * hours, credit type, certificate) as assignment submissions.
 *
 * @package    assignsubmission_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_mutrain extends assign_submission_plugin {

    /**
     * Get the name of the plugin.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_mutrain');
    }

    /**
     * Get the submission record for the given submission id.
     *
     * @param int $submissionid
     * @return stdClass|false
     */
    private function get_mutrain_submission($submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_mutrain', ['submission' => $submissionid]);
    }

    /**
     * Get the file options for the certificate upload.
     *
     * @return array
     */
    private function get_file_options() {
        return [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.pdf', '.jpg', '.jpeg', '.png', '.doc', '.docx'],
            'return_types' => FILE_INTERNAL,
        ];
    }

    /**
     * Add settings to the assignment edit form.
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $DB;

        // Framework selector.
        $frameworks = $DB->get_records('tool_mutrain_framework', ['archived' => 0], 'name ASC', 'id, name');
        $options = [0 => get_string('none')];
        foreach ($frameworks as $fw) {
            $options[$fw->id] = $fw->name;
        }

        $defaultframeworkid = $this->get_config('frameworkid');
        $mform->addElement(
            'select',
            'assignsubmission_mutrain_frameworkid',
            get_string('frameworkid', 'assignsubmission_mutrain'),
            $options
        );
        $mform->addHelpButton('assignsubmission_mutrain_frameworkid', 'frameworkid', 'assignsubmission_mutrain');
        $mform->setDefault('assignsubmission_mutrain_frameworkid', $defaultframeworkid ?: 0);
        $mform->hideIf('assignsubmission_mutrain_frameworkid', 'assignsubmission_mutrain_enabled', 'notchecked');

        // Credit types.
        $defaultcredittypes = $this->get_config('credittypes');
        $mform->addElement(
            'text',
            'assignsubmission_mutrain_credittypes',
            get_string('credittypes', 'assignsubmission_mutrain'),
            ['size' => '60']
        );
        $mform->setType('assignsubmission_mutrain_credittypes', PARAM_TEXT);
        $mform->addHelpButton('assignsubmission_mutrain_credittypes', 'credittypes', 'assignsubmission_mutrain');
        $mform->setDefault('assignsubmission_mutrain_credittypes', $defaultcredittypes ?: '');
        $mform->hideIf('assignsubmission_mutrain_credittypes', 'assignsubmission_mutrain_enabled', 'notchecked');

        // Max hours.
        $defaultmaxhours = $this->get_config('maxhours');
        if ($defaultmaxhours === false) {
            $defaultmaxhours = get_config('assignsubmission_mutrain', 'maxhours');
        }
        $mform->addElement(
            'text',
            'assignsubmission_mutrain_maxhours',
            get_string('maxhours', 'assignsubmission_mutrain'),
            ['size' => '5']
        );
        $mform->setType('assignsubmission_mutrain_maxhours', PARAM_INT);
        $mform->addHelpButton('assignsubmission_mutrain_maxhours', 'maxhours', 'assignsubmission_mutrain');
        $mform->setDefault('assignsubmission_mutrain_maxhours', $defaultmaxhours ?: 20);
        $mform->hideIf('assignsubmission_mutrain_maxhours', 'assignsubmission_mutrain_enabled', 'notchecked');
    }

    /**
     * Save settings from the assignment edit form.
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('frameworkid', $data->assignsubmission_mutrain_frameworkid ?? 0);
        $this->set_config('credittypes', $data->assignsubmission_mutrain_credittypes ?? '');
        $this->set_config('maxhours', $data->assignsubmission_mutrain_maxhours ?? 20);
        return true;
    }

    /**
     * Build credit type options from config.
     *
     * @return array
     */
    private function get_credittype_options(): array {
        $raw = $this->get_config('credittypes');
        if (empty($raw)) {
            return [];
        }
        $types = array_map('trim', explode(',', $raw));
        $options = [];
        foreach ($types as $type) {
            if ($type !== '') {
                $options[$type] = $type;
            }
        }
        return $options;
    }

    /**
     * Add elements to the student submission form.
     *
     * @param mixed $submission
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        $submissionid = $submission ? $submission->id : 0;

        // Load existing record for pre-population.
        $existing = null;
        if ($submissionid) {
            $existing = $this->get_mutrain_submission($submissionid);
        }

        // Activity name.
        $mform->addElement('text', 'activityname', get_string('activityname', 'assignsubmission_mutrain'), ['size' => '60']);
        $mform->setType('activityname', PARAM_TEXT);
        $mform->addHelpButton('activityname', 'activityname', 'assignsubmission_mutrain');
        if ($existing) {
            $mform->setDefault('activityname', $existing->activityname);
        }

        // Provider.
        $mform->addElement('text', 'provider', get_string('provider', 'assignsubmission_mutrain'), ['size' => '60']);
        $mform->setType('provider', PARAM_TEXT);
        $mform->addHelpButton('provider', 'provider', 'assignsubmission_mutrain');
        if ($existing) {
            $mform->setDefault('provider', $existing->provider);
        }

        // Date of activity.
        $mform->addElement('date_selector', 'dateofactivity', get_string('dateofactivity', 'assignsubmission_mutrain'));
        $mform->addHelpButton('dateofactivity', 'dateofactivity', 'assignsubmission_mutrain');
        if ($existing) {
            $mform->setDefault('dateofactivity', $existing->dateofactivity);
        }

        // Credit type.
        $credittypes = $this->get_credittype_options();
        if ($credittypes) {
            $mform->addElement('select', 'credittype', get_string('credittype', 'assignsubmission_mutrain'), $credittypes);
            $mform->addHelpButton('credittype', 'credittype', 'assignsubmission_mutrain');
            if ($existing && isset($credittypes[$existing->credittype])) {
                $mform->setDefault('credittype', $existing->credittype);
            }
        } else {
            $mform->addElement('text', 'credittype', get_string('credittype', 'assignsubmission_mutrain'), ['size' => '30']);
            $mform->setType('credittype', PARAM_TEXT);
            $mform->addHelpButton('credittype', 'credittype', 'assignsubmission_mutrain');
            if ($existing) {
                $mform->setDefault('credittype', $existing->credittype);
            }
        }

        // Hours claimed.
        $mform->addElement('text', 'hoursclaimed', get_string('hoursclaimed', 'assignsubmission_mutrain'), ['size' => '5']);
        $mform->setType('hoursclaimed', PARAM_RAW);
        $mform->addHelpButton('hoursclaimed', 'hoursclaimed', 'assignsubmission_mutrain');
        if ($existing) {
            $mform->setDefault('hoursclaimed', format_float($existing->hoursclaimed, 2));
        }

        // Certificate file upload.
        $fileoptions = $this->get_file_options();
        $data = file_prepare_standard_filemanager(
            $data,
            'certificate',
            $fileoptions,
            $this->assignment->get_context(),
            'assignsubmission_mutrain',
            ASSIGNSUBMISSION_MUTRAIN_FILEAREA,
            $submissionid
        );
        $mform->addElement('filemanager', 'certificate_filemanager',
            get_string('certificate', 'assignsubmission_mutrain'), null, $fileoptions);
        $mform->addHelpButton('certificate_filemanager', 'certificate', 'assignsubmission_mutrain');

        return true;
    }

    /**
     * Save submission data.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        // Handle certificate files.
        $fileoptions = $this->get_file_options();
        $data = file_postupdate_standard_filemanager(
            $data,
            'certificate',
            $fileoptions,
            $this->assignment->get_context(),
            'assignsubmission_mutrain',
            ASSIGNSUBMISSION_MUTRAIN_FILEAREA,
            $submission->id
        );

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $this->assignment->get_context()->id,
            'assignsubmission_mutrain',
            ASSIGNSUBMISSION_MUTRAIN_FILEAREA,
            $submission->id,
            'id',
            false
        );
        $numfiles = count($files);

        $frameworkid = $this->get_config('frameworkid');

        $existing = $this->get_mutrain_submission($submission->id);

        $hoursclaimed = str_replace(',', '.', $data->hoursclaimed ?? '0');
        if (!is_numeric($hoursclaimed)) {
            $hoursclaimed = 0;
        }

        $record = new stdClass();
        $record->activityname = trim($data->activityname ?? '');
        $record->provider = trim($data->provider ?? '');
        $record->dateofactivity = $data->dateofactivity ?? time();
        $record->credittype = trim($data->credittype ?? '');
        $record->hoursclaimed = $hoursclaimed;
        $record->frameworkid = $frameworkid ? (int)$frameworkid : null;
        $record->numfiles = $numfiles;

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('assignsubmission_mutrain', $record);
        } else {
            $record->assignment = $this->assignment->get_instance()->id;
            $record->submission = $submission->id;
            $DB->insert_record('assignsubmission_mutrain', $record);
        }

        // Fire assessable uploaded event if files attached.
        if ($numfiles > 0) {
            $params = [
                'context' => context_module::instance($this->assignment->get_course_module()->id),
                'courseid' => $this->assignment->get_course()->id,
                'objectid' => $submission->id,
                'other' => [
                    'content' => '',
                    'pathnamehashes' => array_keys($files),
                ],
            ];
            if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
                $params['relateduserid'] = $submission->userid;
            }
            $event = \assignsubmission_mutrain\event\assessable_uploaded::create($params);
            $event->set_legacy_files($files);
            $event->trigger();
        }

        return true;
    }

    /**
     * Render one-line summary for the grading table.
     *
     * @param stdClass $submission
     * @param bool $showviewlink
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        $record = $this->get_mutrain_submission($submission->id);
        if (!$record) {
            return get_string('nosubmission', 'assignsubmission_mutrain');
        }

        $showviewlink = true;

        $a = new stdClass();
        $a->activityname = s($record->activityname);
        $a->provider = s($record->provider);
        $a->hoursclaimed = format_float($record->hoursclaimed, 2);
        $a->credittype = s($record->credittype);

        $summary = get_string('submissionsummary', 'assignsubmission_mutrain', $a);

        if ($record->numfiles > 0) {
            $summary .= ' 📎';
        }

        return $summary;
    }

    /**
     * Render full submission view.
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        $record = $this->get_mutrain_submission($submission->id);
        if (!$record) {
            return '';
        }

        $o = '';
        $o .= html_writer::tag('h4', get_string('pluginname', 'assignsubmission_mutrain'));

        $table = new html_table();
        $table->attributes['class'] = 'generaltable';

        $table->data[] = [
            get_string('activityname', 'assignsubmission_mutrain'),
            s($record->activityname),
        ];
        $table->data[] = [
            get_string('provider', 'assignsubmission_mutrain'),
            s($record->provider),
        ];
        $table->data[] = [
            get_string('dateofactivity', 'assignsubmission_mutrain'),
            userdate($record->dateofactivity, get_string('strftimedatefull', 'langconfig')),
        ];
        $table->data[] = [
            get_string('credittype', 'assignsubmission_mutrain'),
            s($record->credittype),
        ];
        $table->data[] = [
            get_string('hoursclaimed', 'assignsubmission_mutrain'),
            format_float($record->hoursclaimed, 2),
        ];

        $o .= html_writer::table($table);

        // Render certificate files.
        if ($record->numfiles > 0) {
            $o .= $this->assignment->render_area_files(
                'assignsubmission_mutrain',
                ASSIGNSUBMISSION_MUTRAIN_FILEAREA,
                $submission->id
            );
        }

        return $o;
    }

    /**
     * Check if the submission is empty.
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        $record = $this->get_mutrain_submission($submission->id);
        if (!$record) {
            return true;
        }
        return trim($record->activityname) === '';
    }

    /**
     * Check if a pre-save submission is empty.
     *
     * @param stdClass $data
     * @return bool
     */
    public function submission_is_empty(stdClass $data) {
        return empty($data->activityname) || trim($data->activityname) === '';
    }

    /**
     * Delete all data for this assignment instance.
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;

        $DB->delete_records('assignsubmission_mutrain', [
            'assignment' => $this->assignment->get_instance()->id,
        ]);

        // Delete all certificate files for this assignment context.
        $fs = get_file_storage();
        $fs->delete_area_files(
            $this->assignment->get_context()->id,
            'assignsubmission_mutrain',
            ASSIGNSUBMISSION_MUTRAIN_FILEAREA
        );

        return true;
    }

    /**
     * Copy submission data to a new attempt.
     *
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     * @return bool
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        $existing = $this->get_mutrain_submission($sourcesubmission->id);
        if (!$existing) {
            return true;
        }

        // Copy the record.
        $newrecord = clone $existing;
        unset($newrecord->id);
        $newrecord->submission = $destsubmission->id;
        $DB->insert_record('assignsubmission_mutrain', $newrecord);

        // Copy certificate files.
        $contextid = $this->assignment->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $contextid,
            'assignsubmission_mutrain',
            ASSIGNSUBMISSION_MUTRAIN_FILEAREA,
            $sourcesubmission->id,
            'id',
            false
        );
        foreach ($files as $file) {
            $fs->create_file_from_storedfile(['itemid' => $destsubmission->id], $file);
        }

        return true;
    }

    /**
     * Return the file areas used by this plugin.
     *
     * @return array
     */
    public function get_file_areas() {
        return [ASSIGNSUBMISSION_MUTRAIN_FILEAREA => get_string('certificate', 'assignsubmission_mutrain')];
    }
}
