<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->libdir . '/accesslib.php');

/**
 * Check if course has Zoom meeting activity
 */
function local_academic_dashboard_course_has_zoom($courseid) {
    global $DB;
    
    // Check if zoom module exists
    if (!$DB->record_exists('modules', ['name' => 'zoom'])) {
        return false;
    }
    
    // Check if course has any zoom activities
    $sql = "SELECT COUNT(*) as count
            FROM {zoom} z
            WHERE z.course = ?";
    
    $result = $DB->get_field_sql($sql, [$courseid]);
    
    return $result > 0;
}

/**
 * Get course statistics
 */
function local_academic_dashboard_get_course_stats($courseid) {
    global $DB;
    
    $stats = new stdClass();
    
    $sql = "SELECT COUNT(DISTINCT ue.userid) as total
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {user} u ON u.id = ue.userid
            WHERE e.courseid = ? AND u.deleted = 0 AND ue.status = 0";
    
    $result = $DB->get_record_sql($sql, [$courseid]);
    $stats->students = $result ? $result->total : 0;
    
    // Count resources
    $stats->resources = $DB->count_records('resource', ['course' => $courseid]);
    
    // Count quizzes
    $stats->quizzes = $DB->count_records('quiz', ['course' => $courseid]);
    
    // Count assignments
    $stats->assignments = $DB->count_records('assign', ['course' => $courseid]);
    
    // Count overdue (assignments + quizzes with due date passed)
    $now = time();
    $overdue = 0;
    
    $overdueassignments = $DB->count_records_select('assign', 
        'course = ? AND duedate > 0 AND duedate < ?', 
        [$courseid, $now]);
    $overdue += $overdueassignments;
    
    $overduequizzes = $DB->count_records_select('quiz', 
        'course = ? AND timeclose > 0 AND timeclose < ?', 
        [$courseid, $now]);
    $overdue += $overduequizzes;
    
    $stats->overdue = $overdue;
    
    // Count remaining (assignments + quizzes with future due date)
    $remaining = 0;
    
    $remainingassignments = $DB->count_records_select('assign', 
        'course = ? AND duedate > 0 AND duedate >= ?', 
        [$courseid, $now]);
    $remaining += $remainingassignments;
    
    $remainingquizzes = $DB->count_records_select('quiz', 
        'course = ? AND timeclose > 0 AND timeclose >= ?', 
        [$courseid, $now]);
    $remaining += $remainingquizzes;
    
    $stats->remaining = $remaining;
    
    return $stats;
}

/**
 * Get student progress in course
 */
function local_academic_dashboard_get_student_progress($userid, $courseid) {
    $course = get_course($courseid);
    $completion = new completion_info($course);
    
    if (!$completion->is_enabled()) {
        return null;
    }
    
    $percentage = \core_completion\progress::get_course_progress_percentage($course, $userid);
    
    return $percentage;
}

/**
 * Get student attendance percentage based on Zoom meeting participation
 * 
 * Logic:
 * 1. Find all Zoom meeting activities in the course
 * 2. For each Zoom meeting, find all sessions (zoom_meeting_details) by UUID
 * 3. For each session UUID, check if the student participated (zoom_meeting_participants)
 * 4. Calculate percentage: (sessions attended / total sessions) * 100
 *
 * @param int $userid The user ID
 * @param int $courseid The course ID
 * @return int|null Attendance percentage or null if no Zoom sessions
 */
function local_academic_dashboard_get_student_attendance($userid, $courseid) {
    global $DB;
    
    // Check if zoom module exists
    if (!$DB->record_exists('modules', ['name' => 'zoom'])) {
        return null;
    }
    
    // Get user details for matching in zoom participants
    $user = $DB->get_record('user', ['id' => $userid], 'id, email, firstname, lastname');
    if (!$user) {
        return null;
    }
    
    // Step 1: Get all Zoom meetings in this course
    $zoomMeetings = $DB->get_records('zoom', ['course' => $courseid], '', 'id, name, meeting_id');
    
    if (empty($zoomMeetings)) {
        return null;
    }
    
    // Step 2: Get all unique session UUIDs for all Zoom meetings in this course
    $zoomIds = array_keys($zoomMeetings);
    list($insql, $params) = $DB->get_in_or_equal($zoomIds, SQL_PARAMS_NAMED);
    
    $sql = "SELECT DISTINCT zmd.id as detailsid, zmd.zoomid, zmd.uuid, zmd.start_time, zmd.end_time
            FROM {zoom_meeting_details} zmd
            WHERE zmd.zoomid {$insql}
            AND zmd.uuid IS NOT NULL
            AND zmd.uuid != ''
            AND zmd.end_time IS NOT NULL 
            AND zmd.end_time < :now
            ORDER BY zmd.start_time ASC";
    
    $params['now'] = time();
    $sessions = $DB->get_records_sql($sql, $params);
    
    if (empty($sessions)) {
        return null;
    }
    
    $uniqueSessions = [];
    foreach ($sessions as $session) {
        if (!empty($session->uuid) && !isset($uniqueSessions[$session->uuid])) {
            $uniqueSessions[$session->uuid] = $session;
        }
    }
    
    $totalSessions = count($uniqueSessions);
    $attendedSessions = 0;
    
    // Prepare user matching patterns
    $userEmail = strtolower(trim($user->email));
    $fullName = strtolower(trim($user->firstname . ' ' . $user->lastname));
    $reverseName = strtolower(trim($user->lastname . ' ' . $user->firstname));
    
    // Step 3: For each unique session UUID, check if the student participated
    foreach ($uniqueSessions as $uuid => $session) {
        $sql = "SELECT COUNT(*) as participated
                FROM {zoom_meeting_participants} zmp
                JOIN {zoom_meeting_details} zmd ON zmd.id = zmp.detailsid
                WHERE zmd.uuid = :uuid
                AND (
                    (zmp.userid IS NOT NULL AND zmp.userid = :userid)
                    OR LOWER(zmp.user_email) = :email
                    OR LOWER(zmp.name) LIKE :fullname
                    OR LOWER(zmp.name) LIKE :reversename
                    OR LOWER(zmp.name) LIKE :emailpattern
                )";
        
        $participationParams = [
            'uuid' => $uuid,
            'userid' => $userid,
            'email' => $userEmail,
            'fullname' => '%' . $fullName . '%',
            'reversename' => '%' . $reverseName . '%',
            'emailpattern' => '%' . $userEmail . '%'
        ];
        
        $participated = $DB->get_field_sql($sql, $participationParams);
        
        if ($participated > 0) {
            $attendedSessions++;
        }
    }
    
    // Step 4: Calculate percentage
    if ($totalSessions > 0) {
        return round(($attendedSessions / $totalSessions) * 100);
    }
    
    return null;
}

/**
 * Get overall student attendance across all courses with Zoom
 * Used for the attendance pie chart on the student page
 *
 * @param int $userid The user ID
 * @return array ['percentage' => int, 'attended' => int, 'total' => int]
 */
function local_academic_dashboard_get_overall_attendance($userid) {
    global $DB;
    
    // Check if zoom module exists
    if (!$DB->record_exists('modules', ['name' => 'zoom'])) {
        return ['percentage' => 0, 'attended' => 0, 'total' => 0];
    }
    
    // Get user details
    $user = $DB->get_record('user', ['id' => $userid], 'id, email, firstname, lastname');
    if (!$user) {
        return ['percentage' => 0, 'attended' => 0, 'total' => 0];
    }
    
    // Get all courses the user is enrolled in
    $courses = enrol_get_users_courses($userid, true);
    
    if (empty($courses)) {
        return ['percentage' => 0, 'attended' => 0, 'total' => 0];
    }
    
    $totalSessions = 0;
    $attendedSessions = 0;
    
    // Prepare user matching patterns
    $userEmail = strtolower(trim($user->email));
    $fullName = strtolower(trim($user->firstname . ' ' . $user->lastname));
    $reverseName = strtolower(trim($user->lastname . ' ' . $user->firstname));
    
    $allUniqueUuids = [];
    
    foreach ($courses as $course) {
        // Get all Zoom meetings in this course
        $zoomMeetings = $DB->get_records('zoom', ['course' => $course->id], '', 'id');
        
        if (empty($zoomMeetings)) {
            continue;
        }
        
        // Get all completed sessions for these Zoom meetings
        $zoomIds = array_keys($zoomMeetings);
        list($insql, $params) = $DB->get_in_or_equal($zoomIds, SQL_PARAMS_NAMED);
        
        $sql = "SELECT DISTINCT zmd.id as detailsid, zmd.uuid
                FROM {zoom_meeting_details} zmd
                WHERE zmd.zoomid {$insql}
                AND zmd.uuid IS NOT NULL
                AND zmd.uuid != ''
                AND zmd.end_time IS NOT NULL 
                AND zmd.end_time < :now";
        
        $params['now'] = time();
        $sessions = $DB->get_records_sql($sql, $params);
        
        foreach ($sessions as $session) {
            if (!empty($session->uuid) && !isset($allUniqueUuids[$session->uuid])) {
                $allUniqueUuids[$session->uuid] = $session->detailsid;
            }
        }
    }
    
    $totalSessions = count($allUniqueUuids);
    
    foreach ($allUniqueUuids as $uuid => $detailsid) {
        // Check if user participated in this session by UUID
        $sql = "SELECT COUNT(*) as participated
                FROM {zoom_meeting_participants} zmp
                JOIN {zoom_meeting_details} zmd ON zmd.id = zmp.detailsid
                WHERE zmd.uuid = :uuid
                AND (
                    (zmp.userid IS NOT NULL AND zmp.userid = :userid)
                    OR LOWER(zmp.user_email) = :email
                    OR LOWER(zmp.name) LIKE :fullname
                    OR LOWER(zmp.name) LIKE :reversename
                    OR LOWER(zmp.name) LIKE :emailpattern
                )";
        
        $participationParams = [
            'uuid' => $uuid,
            'userid' => $userid,
            'email' => $userEmail,
            'fullname' => '%' . $fullName . '%',
            'reversename' => '%' . $reverseName . '%',
            'emailpattern' => '%' . $userEmail . '%'
        ];
        
        $participated = $DB->get_field_sql($sql, $participationParams);
        
        if ($participated > 0) {
            $attendedSessions++;
        }
    }
    
    $percentage = $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100) : 0;
    
    return [
        'percentage' => $percentage,
        'attended' => $attendedSessions,
        'total' => $totalSessions
    ];
}

/**
 * Check if user is a teacher
 */
function local_academic_dashboard_is_teacher($userid) {
    global $DB;
    
    // Get first course where user is enrolled
    $sql = "SELECT DISTINCT c.id
            FROM {course} c
            JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
            JOIN {role_assignments} ra ON ra.contextid = ctx.id
            WHERE ra.userid = ? AND c.id > 1
            LIMIT 1";
    
    $course = $DB->get_record_sql($sql, [$userid]);
    
    if (!$course) {
        return false;
    }
    
    $context = context_course::instance($course->id);
    return has_capability('moodle/course:update', $context, $userid);
}

/**
 * Render email modal
 */
function local_academic_dashboard_render_email_modal($recipients = [], $courseid = null) {
    global $OUTPUT;
    
    $html = '<div class="modal fade" id="emailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">' . get_string('send_email', 'local_academic_dashboard') . '</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="emailForm" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>' . get_string('email_to', 'local_academic_dashboard') . '</label>
                            <input type="text" class="form-control" name="to" value="' . implode(', ', $recipients) . '" readonly>
                        </div>
                        <div class="form-group">
                            <label>' . get_string('email_cc', 'local_academic_dashboard') . '</label>
                            <select class="form-control" name="cc[]" multiple>';
    
    if ($courseid) {
        $context = context_course::instance($courseid);
        $users = get_enrolled_users($context);
        
        foreach ($users as $user) {
            $html .= '<option value="' . $user->id . '">' . fullname($user) . ' (' . $user->email . ')</option>';
        }
    }
    
    $html .= '          </select>
                        </div>
                        <div class="form-group">
                            <label>' . get_string('email_subject', 'local_academic_dashboard') . '</label>
                            <input type="text" class="form-control" name="subject" value="' . get_string('email_default_subject', 'local_academic_dashboard') . '">
                        </div>
                        <div class="form-group">
                            <label>' . get_string('email_content', 'local_academic_dashboard') . '</label>
                            <textarea class="form-control" name="content" rows="8"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">' . get_string('cancel') . '</button>
                        <button type="submit" class="btn btn-primary">' . get_string('email_send', 'local_academic_dashboard') . '</button>
                    </div>
                </form>
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Open email composer window
 */
function local_academic_dashboard_open_email_composer($to = '', $courseid = 0) {
    $url = new moodle_url('/local/academic_dashboard/mail_compose.php', [
        'to' => $to,
        'courseid' => $courseid
    ]);
    return $url->out();
}
