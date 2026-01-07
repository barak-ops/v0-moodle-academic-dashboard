<?php
require_once('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

global $DB;

require_login();
require_capability('local/academic_dashboard:view', context_system::instance());

$userid = required_param('id', PARAM_INT);
$fromcourse = optional_param('fromcourse', 0, PARAM_INT);

$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
$isteacher = local_academic_dashboard_is_teacher($userid);

$PAGE->set_url(new moodle_url('/local/academic_dashboard/user.php', ['id' => $userid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(fullname($user));
$PAGE->set_heading(get_string('dashboard_title', 'local_academic_dashboard'));

// Handle group changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $groupchanges = optional_param('groupchanges', '', PARAM_RAW);
    if ($groupchanges) {
        $changes = json_decode($groupchanges, true);
        foreach ($changes as $change) {
            $courseid = $change['courseid'];
            $newgroupid = $change['groupid'];
            
            // Remove from all groups in course
            $currentgroups = groups_get_user_groups($courseid, $userid);
            foreach ($currentgroups[0] as $gid) {
                groups_remove_member($gid, $userid);
            }
            
            // Add to new group
            if ($newgroupid > 0) {
                groups_add_member($newgroupid, $userid);
            }
        }
        redirect($PAGE->url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();

if ($isteacher) {
    include('teacher_view.php');
} else {
    include('student_view.php');
}

echo $OUTPUT->footer();
