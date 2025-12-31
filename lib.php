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
 * Library functions for local_academic_dashboard.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add navigation node to the navigation tree.
 *
 * @param global_navigation $navigation
 */
function local_academic_dashboard_extend_navigation(global_navigation $navigation) {
    global $PAGE;

    $context = context_system::instance();
    if (!has_capability('local/academic_dashboard:viewdashboard', $context)) {
        return;
    }

    $node = $navigation->add(
        get_string('academic_dashboard', 'local_academic_dashboard'),
        new moodle_url('/local/academic_dashboard/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'academic_dashboard',
        new pix_icon('i/dashboard', '')
    );

    $node->showinflatnavigation = true;
}

/**
 * Add settings link in the admin tree.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_academic_dashboard_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $PAGE;

    if ($PAGE->context->contextlevel != CONTEXT_SYSTEM) {
        return;
    }

    if (!has_capability('local/academic_dashboard:viewdashboard', $context)) {
        return;
    }

    $settingsnav->add(
        get_string('academic_dashboard', 'local_academic_dashboard'),
        new moodle_url('/local/academic_dashboard/index.php'),
        navigation_node::TYPE_SETTING,
        null,
        'academic_dashboard',
        new pix_icon('i/dashboard', '')
    );
}

/**
 * Create a calendar event for a task.
 *
 * @param object $task The task object.
 * @return int The calendar event ID.
 */
function local_academic_dashboard_create_calendar_event($task) {
    global $DB, $USER;

    $event = new stdClass();
    $event->name = $task->title;
    $event->description = $task->description ?? '';
    $event->format = FORMAT_HTML;
    $event->eventtype = 'user';
    $event->userid = $task->assigneeid;
    $event->timestart = $task->duedate;
    $event->timeduration = 0;
    $event->visible = 1;
    $event->component = 'local_academic_dashboard';
    $event->modulename = '';
    $event->instance = 0;

    $calendarevent = \calendar_event::create($event, false);

    return $calendarevent->id;
}

/**
 * Update a calendar event for a task.
 *
 * @param object $task The task object.
 */
function local_academic_dashboard_update_calendar_event($task) {
    global $DB;

    if (empty($task->calendareventid)) {
        return;
    }

    $event = calendar_event::load($task->calendareventid);
    if ($event) {
        $data = new stdClass();
        $data->name = $task->title;
        $data->description = $task->description ?? '';
        $data->timestart = $task->duedate;
        $event->update($data, false);
    }
}

/**
 * Delete a calendar event for a task.
 *
 * @param int $eventid The calendar event ID.
 */
function local_academic_dashboard_delete_calendar_event($eventid) {
    if (empty($eventid)) {
        return;
    }

    $event = calendar_event::load($eventid);
    if ($event) {
        $event->delete(true);
    }
}

/**
 * Send a message using Moodle messaging API.
 *
 * @param int $userfrom The sender user ID.
 * @param int $userto The recipient user ID.
 * @param string $subject The message subject.
 * @param string $message The message content.
 * @return bool Success status.
 */
function local_academic_dashboard_send_message($userfrom, $userto, $subject, $message) {
    $eventdata = new \core\message\message();
    $eventdata->component = 'local_academic_dashboard';
    $eventdata->name = 'notification';
    $eventdata->userfrom = $userfrom;
    $eventdata->userto = $userto;
    $eventdata->subject = $subject;
    $eventdata->fullmessage = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml = format_text($message, FORMAT_MARKDOWN);
    $eventdata->smallmessage = $subject;
    $eventdata->notification = 1;

    return message_send($eventdata);
}

/**
 * Get student completion data for courses.
 *
 * @param int $userid The user ID.
 * @param array $courseids Optional array of course IDs to filter.
 * @return array Array of course completion data.
 */
function local_academic_dashboard_get_student_completion($userid, $courseids = []) {
    global $DB;

    $completiondata = [];
    
    $sql = "SELECT c.id, c.fullname, c.shortname
            FROM {course} c
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE ue.userid = :userid";
    $params = ['userid' => $userid];

    if (!empty($courseids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
        $sql .= " AND c.id $insql";
        $params = array_merge($params, $inparams);
    }

    $courses = $DB->get_records_sql($sql, $params);

    foreach ($courses as $course) {
        $completion = new completion_info($course);
        if ($completion->is_enabled()) {
            $completiondata[$course->id] = [
                'courseid' => $course->id,
                'coursename' => $course->fullname,
                'completed' => $completion->is_course_complete($userid),
                'progress' => \core_completion\progress::get_course_progress_percentage($course, $userid),
            ];
        }
    }

    return $completiondata;
}

/**
 * Get students at risk based on inactivity or low completion.
 *
 * @param int $classid Optional class ID to filter.
 * @param int $inactivitydays Number of days without activity.
 * @param float $completionthreshold Minimum completion percentage.
 * @return array Array of at-risk students.
 */
function local_academic_dashboard_get_atrisk_students($classid = 0, $inactivitydays = 7, $completionthreshold = 50) {
    global $DB;

    $atrisk = [];
    $threshold = time() - ($inactivitydays * 24 * 60 * 60);

    // Get students based on class filter.
    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.lastaccess
            FROM {user} u";

    $params = [];

    if ($classid > 0) {
        $sql .= " JOIN {local_acad_class_members} cm ON cm.userid = u.id
                  WHERE cm.classid = :classid";
        $params['classid'] = $classid;
    } else {
        $sql .= " WHERE u.deleted = 0 AND u.suspended = 0";
    }

    $students = $DB->get_records_sql($sql, $params);

    foreach ($students as $student) {
        $reasons = [];

        // Check inactivity.
        if ($student->lastaccess < $threshold) {
            $reasons[] = 'no_activity';
        }

        // Check completion.
        $completiondata = local_academic_dashboard_get_student_completion($student->id);
        $lowcompletion = false;
        foreach ($completiondata as $data) {
            if ($data['progress'] !== null && $data['progress'] < $completionthreshold) {
                $lowcompletion = true;
                break;
            }
        }
        if ($lowcompletion) {
            $reasons[] = 'no_completion';
        }

        if (!empty($reasons)) {
            $atrisk[] = [
                'userid' => $student->id,
                'firstname' => $student->firstname,
                'lastname' => $student->lastname,
                'email' => $student->email,
                'lastaccess' => $student->lastaccess,
                'reasons' => $reasons,
            ];
        }
    }

    return $atrisk;
}
