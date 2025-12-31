<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Individual student view page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/lib.php');

$studentid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

require_login();

$context = context_system::instance();
require_capability('local/academic_dashboard:viewdashboard', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/student.php', ['id' => $studentid]));
$PAGE->set_pagelayout('standard');

// Get student details.
$student = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);

$PAGE->set_title(get_string('student_details', 'local_academic_dashboard') . ': ' . fullname($student));
$PAGE->set_heading(get_string('student_details', 'local_academic_dashboard'));

// Handle actions.
if ($action === 'createtask' && confirm_sesskey()) {
    redirect(new moodle_url('/local/academic_dashboard/task.php', ['assigntype' => 'student', 'studentid' => $studentid]));
}

echo $OUTPUT->header();

// Student profile card.
echo html_writer::start_div('student-profile-card', ['style' => 'border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #f9f9f9;']);

echo html_writer::tag('h3', fullname($student));
echo html_writer::tag('p', html_writer::tag('strong', get_string('email')) . ': ' . $student->email);
echo html_writer::tag('p', html_writer::tag('strong', get_string('lastaccess')) . ': ' . 
    ($student->lastaccess ? userdate($student->lastaccess) : get_string('never')));

// Get student's classes.
$classes = $DB->get_records_sql(
    "SELECT c.id, c.name
     FROM {local_acad_classes} c
     JOIN {local_acad_class_members} cm ON cm.classid = c.id
     WHERE cm.userid = :userid",
    ['userid' => $studentid]
);

if (!empty($classes)) {
    $classnames = array_map(function($class) { return $class->name; }, $classes);
    echo html_writer::tag('p', html_writer::tag('strong', get_string('classes', 'local_academic_dashboard')) . ': ' . 
        implode(', ', $classnames));
}

// Action buttons.
echo html_writer::start_div('student-actions', ['style' => 'margin-top: 15px;']);
$createtaskurl = new moodle_url('/local/academic_dashboard/task.php', [
    'assigntype' => 'student',
    'studentid' => $studentid,
    'sesskey' => sesskey()
]);
echo html_writer::link($createtaskurl, get_string('create_task', 'local_academic_dashboard'), 
    ['class' => 'btn btn-primary']);

$sendmessageurl = new moodle_url('/message/index.php', ['id' => $studentid]);
echo ' ' . html_writer::link($sendmessageurl, get_string('sendmessage', 'message'), 
    ['class' => 'btn btn-secondary']);
echo html_writer::end_div();

echo html_writer::end_div();

// Course completion progress.
echo html_writer::tag('h4', get_string('course_completion', 'local_academic_dashboard'), 
    ['style' => 'margin-top: 30px;']);

$completiondata = local_academic_dashboard_get_student_completion($studentid);

if (!empty($completiondata)) {
    echo html_writer::start_div('completion-cards', ['style' => 'display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-bottom: 30px;']);
    
    foreach ($completiondata as $data) {
        $progress = $data['progress'] ?? 0;
        $progresscolor = $progress >= 75 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
        
        echo html_writer::start_div('card', ['style' => 'border: 1px solid #ddd; padding: 15px; border-radius: 5px;']);
        echo html_writer::tag('h5', $data['coursename'], ['style' => 'margin-top: 0;']);
        
        echo html_writer::start_div('progress', ['style' => 'height: 20px; margin: 10px 0; background: #f0f0f0; border-radius: 10px; overflow: hidden;']);
        echo html_writer::div('', '', [
            'style' => "width: {$progress}%; height: 100%; background: " . 
                ($progress >= 75 ? '#28a745' : ($progress >= 50 ? '#ffc107' : '#dc3545')) . ';'
        ]);
        echo html_writer::end_div();
        
        echo html_writer::tag('p', round($progress) . '% ' . get_string('complete'), ['style' => 'margin: 0;']);
        echo html_writer::end_div();
    }
    
    echo html_writer::end_div();
} else {
    echo html_writer::div(get_string('no_completion_data', 'local_academic_dashboard'), 'alert alert-info');
}

// Student's tasks.
echo html_writer::tag('h4', get_string('tasks', 'local_academic_dashboard'));

$tasks = $DB->get_records_sql(
    "SELECT t.*, u.firstname, u.lastname
     FROM {local_acad_tasks} t
     JOIN {user} u ON u.id = t.assigneeid
     WHERE t.assigntype = 'student' AND t.studentid = :studentid
     ORDER BY t.duedate ASC, t.priority DESC",
    ['studentid' => $studentid]
);

if (!empty($tasks)) {
    $table = new html_table();
    $table->head = [
        get_string('title', 'local_academic_dashboard'),
        get_string('assignee', 'local_academic_dashboard'),
        get_string('due_date', 'local_academic_dashboard'),
        get_string('priority', 'local_academic_dashboard'),
        get_string('status', 'local_academic_dashboard'),
        get_string('actions', 'local_academic_dashboard')
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($tasks as $task) {
        $prioritylabels = [1 => get_string('low'), 2 => get_string('medium'), 3 => get_string('high')];
        $prioritycolors = [1 => 'success', 2 => 'warning', 3 => 'danger'];
        
        $statuslabels = [
            'open' => get_string('open', 'local_academic_dashboard'),
            'inprogress' => get_string('inprogress', 'local_academic_dashboard'),
            'completed' => get_string('completed', 'local_academic_dashboard'),
            'cancelled' => get_string('cancelled', 'local_academic_dashboard')
        ];

        $editurl = new moodle_url('/local/academic_dashboard/task.php', ['id' => $task->id]);
        $actions = html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-secondary']);

        $row = [
            $task->title,
            fullname($task),
            $task->duedate ? userdate($task->duedate, get_string('strftimedatetime')) : '-',
            html_writer::span($prioritylabels[$task->priority], 'badge badge-' . $prioritycolors[$task->priority]),
            html_writer::span($statuslabels[$task->status], 'badge badge-' . 
                ($task->status === 'completed' ? 'success' : ($task->status === 'cancelled' ? 'secondary' : 'primary'))),
            $actions
        ];

        $table->data[] = $row;
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div(get_string('no_tasks_found', 'local_academic_dashboard'), 'alert alert-info');
}

// Service requests.
echo html_writer::tag('h4', get_string('service_requests', 'local_academic_dashboard'), ['style' => 'margin-top: 30px;']);

$requests = $DB->get_records_sql(
    "SELECT sr.*, u.firstname, u.lastname
     FROM {local_acad_service_requests} sr
     LEFT JOIN {user} u ON u.id = sr.assigneeid
     WHERE sr.studentid = :studentid
     ORDER BY sr.timecreated DESC",
    ['studentid' => $studentid]
);

if (!empty($requests)) {
    $table = new html_table();
    $table->head = [
        get_string('request_type', 'local_academic_dashboard'),
        get_string('description'),
        get_string('status', 'local_academic_dashboard'),
        get_string('assignee', 'local_academic_dashboard'),
        get_string('created', 'local_academic_dashboard')
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($requests as $request) {
        $statuscolors = [
            'open' => 'primary',
            'inprogress' => 'warning',
            'resolved' => 'success',
            'closed' => 'secondary'
        ];

        $row = [
            $request->requesttype,
            shorten_text(strip_tags($request->description), 100),
            html_writer::span(get_string($request->status, 'local_academic_dashboard'), 
                'badge badge-' . ($statuscolors[$request->status] ?? 'secondary')),
            $request->assigneeid ? fullname($request) : '-',
            userdate($request->timecreated)
        ];

        $table->data[] = $row;
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div(get_string('no_service_requests', 'local_academic_dashboard'), 'alert alert-info');
}

// Alerts for this student.
$alerts = $DB->get_records('local_acad_alerts', ['studentid' => $studentid, 'status' => 'active']);
if (!empty($alerts)) {
    echo html_writer::tag('h4', get_string('alerts', 'local_academic_dashboard'), ['style' => 'margin-top: 30px;']);
    
    foreach ($alerts as $alert) {
        $alerttype = get_string('alert_' . $alert->alerttype, 'local_academic_dashboard');
        echo html_writer::div(
            html_writer::tag('strong', $alerttype) . ': ' . ($alert->details ?? ''),
            'alert alert-warning'
        );
    }
}

echo $OUTPUT->footer();
