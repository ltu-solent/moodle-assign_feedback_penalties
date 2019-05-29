<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   assignfeedback_penalty
 * @copyright 2019 Solent University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class assign_feedback_penalty extends assign_feedback_plugin {

    public function get_name() {
        return get_string('penalty', 'assignfeedback_penalty');
    }

    public function get_penalty($gradeid) {
        global $DB;
        return $DB->get_record('assignfeedback_penalty', array('grade' => $gradeid));
    }

    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {

        if ($grade) {
            $penalty = $this->get_penalty($grade->id);
        }

        if ($penalty) {
            if ($penalty->penalty != '0') {
                $check = $mform->addElement('checkbox', 'penalty_check', get_string('check_label', 'assignfeedback_penalty'));
                $mform->setDefault('penalty_check', true);
            } else {
                $mform->addElement('checkbox', 'penalty_check', get_string('check_label', 'assignfeedback_penalty'));
            }

        } else {
            $mform->addElement('checkbox', 'penalty_check', get_string('check_label', 'assignfeedback_penalty'));

        }

        return true;
    }

  public function supports_quickgrading() {
      return true;
  }

  public function get_quickgrading_html($userid, $grade) {
      if ($grade) {
        $penalty = $this->get_penalty($grade->id);
      }

      $pluginname = get_string('pluginname', 'assignfeedback_penalty');
      $labeloptions = array('for'=>'quickgrade_penalty_' . $userid,
                            'class'=>'accesshide');
      $selectoptions = array('name'=>'quickgrade_penalty_' . $userid,
                               'id'=>'quickgrade_penalty_' . $userid,
                               'class'=>'quickgrade');

      $out = html_writer::tag('label', $pluginname, $labeloptions);
// var_dump($penalty);
      if(isset($penalty)){
        if ($penalty->penalty == 1) {
          $out .= html_writer::start_tag('input type="hidden" name="' . $selectoptions['name'] .'" value="0"'); //this hidden input sets the value to 0 if the checkbox is unchecked
          $out .= html_writer::start_tag('input type="checkbox" name="' . $selectoptions['name'] .'" value="1" checked');
        } else {
          $out .= html_writer::start_tag('input type="hidden" name="' . $selectoptions['name'] .'" value="0"');
          $out .= html_writer::start_tag('input type="checkbox" name="' . $selectoptions['name'] .'" value="1"');
        }
      }else{
          $out .= html_writer::start_tag('input type="hidden" name="' . $selectoptions['name'] .'" value="0"');
          $out .= html_writer::start_tag('input type="checkbox" name="' . $selectoptions['name'] .'" value="1"');
      }
      return $out;
}

/**
 * Has the plugin quickgrading form element been modified in the current form submission?
 *
 * @param int $userid The user id in the table this quickgrading element relates to
 * @param stdClass $grade The grade
 * @return boolean - true if the quickgrading form element has been modified
 */

 public function is_quickgrading_modified($userid, $grade) {
     $penalty_text = '';
     if ($grade) {
         $penalty = $this->get_penalty($grade->id);
         if ($penalty) {
             $penalty_text = $penalty->penalty;
         }
     }
     // Note that this handles the difference between empty and not in the quickgrading
     // form at all (hidden column).
     $newvalue = optional_param('quickgrade_penalty_' . $userid, false, PARAM_RAW);
     return ($newvalue !== false) && ($newvalue != $penalty_text);
 }

public function save_quickgrading_changes($userid, $grade) {
    global $DB;
    $penalty = $this->get_penalty($grade->id);
    $quickgradepenalty = optional_param('quickgrade_penalty_' . $userid, 0, PARAM_RAW);
    if ($penalty) {
        $penalty->penalty = $quickgradepenalty;
        return $DB->update_record('assignfeedback_penalty', $penalty);
    } else {
        $penalty = new stdClass();
        $penalty->penalty = $quickgradepenalty;
        $penalty->penaltyformat = FORMAT_HTML;
        $penalty->grade = $grade->id;
        $penalty->assignment = $this->assignment->get_instance()->id;
        return $DB->insert_record('assignfeedback_penalty', $penalty) > 0;
    }
}

    public function save(stdClass $grade, stdClass $data) {
        global $DB, $USER;
        $penalty = $this->get_penalty($grade->id);
        if ($penalty) {
            if ($data->penalty_check !== $penalty->penalty) {
                $penalty->penalty = ($data->penalty_check != null ? 1 : 0);
            }

            // if ($data->penalty_check !== $penalty->penalty) {
                // $penalty->penalty = $data->penalty_check;
            // }

			return $DB->update_record('assignfeedback_penalty', $penalty);

        } else {
            $penalty = new stdClass();
            $penalty->assignment = $this->assignment->get_instance()->id;
            $penalty->grade = $grade->id;
            $penalty->penalty = ($data->penalty_check != null ? 1 : 0);
            $penalty->userid = $USER->id;
            return $DB->insert_record('assignfeedback_penalty', $penalty) > 0;
        }
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
		global $DB;
        $penalty = $this->get_penalty($grade->id);
		if($penalty){
			if ($penalty->penalty == 1) {
				$penalty_text = 'Yes';
				return format_text($penalty_text, FORMAT_HTML);
			} else {
        $penalty_text = 'No';
        return format_text('', FORMAT_HTML);
      }

		}
        return '';
    }
}
