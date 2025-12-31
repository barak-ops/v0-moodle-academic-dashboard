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
 * Task create/edit page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');
require_once($CFG->libdir . '/formslib.php');

$id = optional_param('id', 0, PARAM_INT);
$studentid = optional_param('studentid', 0, PARAM_INT);
$classid = optional_param('classid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

require_login();

$context = context_system::instance();

// Determine if creating or editing.
$task = null;
if ($id > 0) {
    $task = \local_academic_dashboard\task_manager::get_task($id);
    if (!$task) {
        throw new moodle_exception('error_notfound', 'local_academic_dashboard');
    }
    require_capability('local/academic_dashboard:managetasks', $context);
    $pagetitle = get_string('edittask', 'local_academic_dashboard');
} else {
    require_capability('local/academic_dashboard:managetasks', $context);
    $pagetitle = get_string('newtask', 'local_academic_dashboard');
}

// Handle delete.
if ($delete && $task) {
    if ($confirm && confirm_sesskey()) {
        \local_academic_dashboard\task_manager::delete_task($id);
        redirect(
            new moodle_url('/local/academic_dashboard/tasks.php'),
            get_string('taskdeleted', 'local_academic_dashboard'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        $PAGE->set_context($context);
        $PAGE->set_url(new moodle_url('/local/academic_dashboard/task.php', ['id' => $id, 'delete' => 1]));
        $PAGE->set_title(get_string('deletetask', 'local_academic_dashboard'));
        $PAGE->set_heading(get_string('deletetask', 'local_academic_dashboard'));

        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('deletetask', 'local_academic_dashboard') . ': ' . format_string($task->title) . '?',
            new moodle_url('/local/academic_dashboard/task.php', ['id' => $id, 'delete' => 1, 'confirm' => 1]),
            new moodle_url('/local/academic_dashboard/task.php', ['id' => $id])
        );
        echo $OUTPUT->footer();
        exit;
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/task.php', ['id' => $id]));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->set_pagelayout('standard');

/**
 * Task form class.
 */
class task_form extends moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        global $DB, $USER;

        $mform = $this->_form;
        $task = $this->_customdata['task'] ?? null;

        // Title.
        $mform->addElement('text', 'title', get_string('tasktitle', 'local_academic_dashboard'), ['size' => 50]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required');

        // Description.
        $mform->addElement('textarea', 'description', get_string('taskdescription', 'local_academic_dashboard'), ['rows' => 5, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);

        // Assignment type.
        $assigntypes = [
            'general' => get_string('taskassigntype_general', 'local_academic_dashboard'),
            'student' => get_string('taskassigntype_student', 'local_academic_dashboard'),
            'class' => get_string('taskassigntype_class', 'local_academic_dashboard'),
        ];
        $mform->addElement('select', 'assigntype', get_string('taskassigntype', 'local_academic_dashboard'), $assigntypes);

        // Student selector (shown when assigntype = student).
        $students = $DB->get_records_sql("SELECT id, firstname, lastname FROM {user} WHERE deleted = 0 AND suspended = 0 ORDER BY lastname, firstname");
        $studentoptions = [0 => ''];
        foreach ($students as $s) {
            $studentoptions[$s->id] = $s->lastname . ', ' . $s->firstname;
        }
        $mform->addElement('select', 'studentid', get_string('taskassigntype_student', 'local_academic_dashboard'), $studentoptions);
        $mform->hideIf('studentid', 'assigntype', 'neq', 'student');

        // Class selector (shown when assigntype = class).
        $classes = $DB->get_records('local_acad_classes', [], 'name ASC');
        $classoptions = [0 => ''];
        foreach ($classes as $c) {
            $classoptions[$c->id] = $c->name;
        }
        $mform->addElement('select', 'classid', get_string('taskassigntype_class', 'local_academic_dashboard'), $classoptions);
        $mform->hideIf('classid', 'assigntype', 'neq', 'class');

        // Assignee.
        $managers = get_users_by_capability(context_system::instance(), 'local/academic_dashboard:viewdashboard', 'u.id, u.firstname, u.lastname');
        $manageroptions = [];
        foreach ($managers as $m) {
            $manageroptions[$m->id] = $m->lastname . ', ' . $m->firstname;
        }
        $mform->addElement('select', 'assigneeid', get_string('taskassignee', 'local_academic_dashboard'), $manageroptions);
        $mform->setDefault('assigneeid', $USER->id);

        // Due date.
        $mform->addElement('date_time_selector', 'duedate', get_string('taskduedate', 'local_academic_dashboard'), ['optional' => true]);

        // Priority.
        $priorities = [
            1 => get_string('taskpriority_low', 'local_academic_dashboard'),
            2 => get_string('taskpriority_medium', 'local_academic_dashboard'),
            3 => get_string('taskpriority_high', 'local_academic_dashboard'),
        ];
        $mform->addElement('select', 'priority', get_string('taskpriority', 'local_academic_dashboard'), $priorities);
        $mform->setDefault('priority', 2);

        // Status (only for edit).
        if ($task) {
            $statuses = [
                'open' => get_string('taskstatus_open', 'local_academic_dashboard'),
                'inprogress' => get_string('taskstatus_inprogress', 'local_academic_dashboard'),
                'completed' => get_string('taskstatus_completed', 'local_academic_dashboard'),
                'cancelled' => get_string('taskstatus_cancelled', 'local_academic_dashboard'),
            ];
            $mform->addElement('select', 'status', get_string('taskstatus', 'local_academic_dashboard'), $statuses);
        }

        // Recurring task.
        $mform->addElement('advcheckbox', 'isrecurring', get_string('taskrecurring', 'local_academic_dashboard'));

        // Recurring frequency.
        $frequencies = [
            'daily' => get_string('taskrecurringfreq_daily', 'local_academic_dashboard'),
            'weekly' => get_string('taskrecurringfreq_weekly', 'local_academic_dashboard'),
            'monthly' => get_string('taskrecurringfreq_monthly', 'local_academic_dashboard'),
        ];
        $mform->addElement('select', 'recurringfreq', get_string('taskrecurringfreq', 'local_academic_dashboard'), $frequencies);
        $mform->hideIf('recurringfreq', 'isrecurring', 'notchecked');

        // Recurring day.
        $mform->addElement('text', 'recurringday', get_string('taskrecurringday', 'local_academic_dashboard'), ['size' => 5]);
        $mform->setType('recurringday', PARAM_INT);
        $mform->hideIf('recurringday', 'isrecurring', 'notchecked');

        // Recurring end date.
        $mform->addElement('date_selector', 'recurringend', get_string('taskrecurringend', 'local_academic_dashboard'), ['optional' => true]);
        $mform->hideIf('recurringend', 'isrecurring', 'notchecked');

        // Course link.
        $courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id, fullname');
        $courseoptions = [0 => ''];
        foreach ($courses as $c) {
            if ($c->id != SITEID) {
                $courseoptions[$c->id] = format_string($c->fullname);
            }
        }
        $mform->addElement('select', 'courseid', get_string('taskcourse', 'local_academic_dashboard'), $courseoptions);

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Buttons.
        $this->add_action_buttons(true, get_string('save', 'local_academic_dashboard'));
    }
}

// Create form.
$mform = new task_form(null, ['task' => $task]);

// Set default data.
if ($task) {
    $mform->set_data($task);
} else {
    $defaults = new stdClass();
    $defaults->id = 0;
    if ($studentid > 0) {
        $defaults->assigntype = 'student';
        $defaults->studentid = $studentid;
    } else if ($classid > 0) {
        $defaults->assigntype = 'class';
        $defaults->classid = $classid;
    }
    $mform->set_data($defaults);
}

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/academic_dashboard/tasks.php'));
} else if ($data = $mform->get_data()) {
    if ($data->id > 0) {
        \local_academic_dashboard\task_manager::update_task($data);
        $message = get_string('taskupdated', 'local_academic_dashboard');
    } else {
        $data->id = \local_academic_dashboard\task_manager::create_task($data);
        $message = get_string('taskcreated', 'local_academic_dashboard');
    }

    redirect(
        new moodle_url('/local/academic_dashboard/task.php', ['id' => $data->id]),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$mform->display();

// Show delete button for existing tasks.
if ($task) {
    echo '<div class="mt-3">';
    echo '<a href="' . new moodle_url('/local/academic_dashboard/task.php', ['id' => $id, 'delete' => 1]) . '" class="btn btn-danger">';
    echo '<i class="fa fa-trash"></i> ' . get_string('deletetask', 'local_academic_dashboard');
    echo '</a>';
    echo '</div>';
}

echo $OUTPUT->footer();
