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
 * Alerts management page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/academic_dashboard:viewalerts', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/alerts.php'));
$PAGE->set_title(get_string('alerts', 'local_academic_dashboard'));
$PAGE->set_heading(get_string('alerts', 'local_academic_dashboard'));
$PAGE->set_pagelayout('standard');

// Add CSS.
$PAGE->requires->css('/local/academic_dashboard/styles.css');

// Get filter parameters.
$alerttype = optional_param('alerttype', '', PARAM_TEXT);
$status = optional_param('status', 'active', PARAM_TEXT);
$classid = optional_param('classid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

// Handle alert actions.
$action = optional_param('action', '', PARAM_TEXT);
$alertid = optional_param('id', 0, PARAM_INT);

if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'acknowledge':
            if ($alertid && has_capability('local/academic_dashboard:managealerts', $context)) {
                $DB->update_record('local_acad_alerts', [
                    'id' => $alertid,
                    'status' => 'acknowledged',
                    'acknowledgedby' => $USER->id,
                    'timemodified' => time()
                ]);
                redirect($PAGE->url, get_string('alertacknowledged', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
            }
            break;
        case 'resolve':
            if ($alertid && has_capability('local/academic_dashboard:managealerts', $context)) {
                $DB->update_record('local_acad_alerts', [
                    'id' => $alertid,
                    'status' => 'resolved',
                    'timemodified' => time()
                ]);
                redirect($PAGE->url, get_string('alertresolved', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
            }
            break;
        case 'delete':
            if ($alertid && has_capability('local/academic_dashboard:managealerts', $context)) {
                $DB->delete_records('local_acad_alerts', ['id' => $alertid]);
                redirect($PAGE->url, get_string('alertdeleted', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
            }
            break;
    }
}

// Build SQL query for alerts.
$sql = "SELECT a.*, 
               u.firstname as student_firstname, 
               u.lastname as student_lastname, 
               u.email as student_email,
               c.fullname as course_name,
               ack.firstname as ack_firstname,
               ack.lastname as ack_lastname
        FROM {local_acad_alerts} a
        JOIN {user} u ON u.id = a.studentid
        LEFT JOIN {course} c ON c.id = a.courseid
        LEFT JOIN {user} ack ON ack.id = a.acknowledgedby
        WHERE 1=1";

$params = [];

if ($alerttype) {
    $sql .= " AND a.alerttype = :alerttype";
    $params['alerttype'] = $alerttype;
}

if ($status) {
    $sql .= " AND a.status = :status";
    $params['status'] = $status;
}

if ($classid) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM {local_acad_class_members} cm 
        WHERE cm.userid = a.studentid AND cm.classid = :classid
    )";
    $params['classid'] = $classid;
}

$sql .= " ORDER BY a.timecreated DESC";

// Get total count.
$totalcount = $DB->count_records_sql("SELECT COUNT(*) FROM (" . $sql . ") AS subquery", $params);

// Get alerts for current page.
$alerts = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Get classes for filter.
$classes = $DB->get_records('local_acad_classes', [], 'name ASC');

// Get alert statistics.
$stats = [
    'active' => $DB->count_records('local_acad_alerts', ['status' => 'active']),
    'acknowledged' => $DB->count_records('local_acad_alerts', ['status' => 'acknowledged']),
    'resolved' => $DB->count_records('local_acad_alerts', ['status' => 'resolved']),
    'no_activity' => $DB->count_records('local_acad_alerts', ['alerttype' => 'no_activity', 'status' => 'active']),
    'no_completion' => $DB->count_records('local_acad_alerts', ['alerttype' => 'no_completion', 'status' => 'active']),
    'low_grade' => $DB->count_records('local_acad_alerts', ['alerttype' => 'low_grade', 'status' => 'active']),
];

echo $OUTPUT->header();

?>
<div class="academic-dashboard alerts-page">
    <!-- Alert Statistics -->
    <div class="alert-stats mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card card bg-danger text-white">
                    <div class="card-body">
                        <h3><?php echo $stats['active']; ?></h3>
                        <p><?php echo get_string('alertstatus_active', 'local_academic_dashboard'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-warning">
                    <div class="card-body">
                        <h3><?php echo $stats['acknowledged']; ?></h3>
                        <p><?php echo get_string('alertstatus_acknowledged', 'local_academic_dashboard'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-success text-white">
                    <div class="card-body">
                        <h3><?php echo $stats['resolved']; ?></h3>
                        <p><?php echo get_string('alertstatus_resolved', 'local_academic_dashboard'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-info text-white">
                    <div class="card-body">
                        <h3><?php echo $totalcount; ?></h3>
                        <p><?php echo get_string('totalalerts', 'local_academic_dashboard'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="alerts-filters card mb-4">
        <div class="card-body">
            <form method="get" action="" class="form-inline">
                <div class="row w-100">
                    <div class="col-md-3">
                        <label for="status"><?php echo get_string('status', 'local_academic_dashboard'); ?></label>
                        <select name="status" id="status" class="form-control">
                            <option value=""><?php echo get_string('filterall', 'local_academic_dashboard'); ?></option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                                <?php echo get_string('alertstatus_active', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="acknowledged" <?php echo $status === 'acknowledged' ? 'selected' : ''; ?>>
                                <?php echo get_string('alertstatus_acknowledged', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>
                                <?php echo get_string('alertstatus_resolved', 'local_academic_dashboard'); ?>
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="alerttype"><?php echo get_string('alerttype', 'local_academic_dashboard'); ?></label>
                        <select name="alerttype" id="alerttype" class="form-control">
                            <option value=""><?php echo get_string('filterall', 'local_academic_dashboard'); ?></option>
                            <option value="no_activity" <?php echo $alerttype === 'no_activity' ? 'selected' : ''; ?>>
                                <?php echo get_string('alerttype_no_activity', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="no_completion" <?php echo $alerttype === 'no_completion' ? 'selected' : ''; ?>>
                                <?php echo get_string('alerttype_no_completion', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="low_grade" <?php echo $alerttype === 'low_grade' ? 'selected' : ''; ?>>
                                <?php echo get_string('alerttype_low_grade', 'local_academic_dashboard'); ?>
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="classid"><?php echo get_string('filterclass', 'local_academic_dashboard'); ?></label>
                        <select name="classid" id="classid" class="form-control">
                            <option value="0"><?php echo get_string('filterall', 'local_academic_dashboard'); ?></option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class->id; ?>" <?php echo ($classid == $class->id) ? 'selected' : ''; ?>>
                                    <?php echo format_string($class->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2"><?php echo get_string('filterapply', 'local_academic_dashboard'); ?></button>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/alerts.php'); ?>" class="btn btn-secondary">
                            <?php echo get_string('filterclear', 'local_academic_dashboard'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Alerts Table -->
    <div class="alerts-list card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fa fa-exclamation-triangle"></i>
                <?php echo get_string('alerts', 'local_academic_dashboard'); ?>
                (<?php echo $totalcount; ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($alerts)): ?>
                <p class="text-muted text-center py-4"><?php echo get_string('noalerts', 'local_academic_dashboard'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php echo get_string('student', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('alerttype', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('course', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('details', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('status', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('created', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('actions', 'local_academic_dashboard'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alerts as $alert): ?>
                                <tr class="alert-row alert-<?php echo $alert->status; ?>">
                                    <td>
                                        <a href="<?php echo new moodle_url('/local/academic_dashboard/student.php', ['id' => $alert->studentid]); ?>">
                                            <?php echo $alert->student_firstname . ' ' . $alert->student_lastname; ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?php echo $alert->student_email; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $alert->alerttype === 'no_activity' ? 'danger' : 
                                                ($alert->alerttype === 'no_completion' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo get_string('alerttype_' . $alert->alerttype, 'local_academic_dashboard'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($alert->course_name): ?>
                                            <a href="<?php echo new moodle_url('/course/view.php', ['id' => $alert->courseid]); ?>">
                                                <?php echo format_string($alert->course_name); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo format_text($alert->details, FORMAT_HTML); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $alert->status === 'active' ? 'danger' : 
                                                ($alert->status === 'acknowledged' ? 'warning' : 'success'); 
                                        ?>">
                                            <?php echo get_string('alertstatus_' . $alert->status, 'local_academic_dashboard'); ?>
                                        </span>
                                        <?php if ($alert->status === 'acknowledged' && $alert->ack_firstname): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo get_string('by', 'local_academic_dashboard') . ' ' . 
                                                    $alert->ack_firstname . ' ' . $alert->ack_lastname; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo userdate($alert->timecreated, get_string('strftimedatetimeshort')); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($alert->status === 'active' && has_capability('local/academic_dashboard:managealerts', $context)): ?>
                                                <a href="<?php echo new moodle_url('/local/academic_dashboard/alerts.php', 
                                                    ['action' => 'acknowledge', 'id' => $alert->id, 'sesskey' => sesskey()]); ?>" 
                                                    class="btn btn-sm btn-warning" title="<?php echo get_string('acknowledge', 'local_academic_dashboard'); ?>">
                                                    <i class="fa fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($alert->status !== 'resolved' && has_capability('local/academic_dashboard:managealerts', $context)): ?>
                                                <a href="<?php echo new moodle_url('/local/academic_dashboard/alerts.php', 
                                                    ['action' => 'resolve', 'id' => $alert->id, 'sesskey' => sesskey()]); ?>" 
                                                    class="btn btn-sm btn-success" title="<?php echo get_string('resolve', 'local_academic_dashboard'); ?>">
                                                    <i class="fa fa-check-double"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo new moodle_url('/local/academic_dashboard/student.php', ['id' => $alert->studentid]); ?>" 
                                                class="btn btn-sm btn-info" title="<?php echo get_string('viewstudent', 'local_academic_dashboard'); ?>">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <?php if (has_capability('local/academic_dashboard:sendmessages', $context)): ?>
                                                <a href="<?php echo new moodle_url('/message/index.php', ['id' => $alert->studentid]); ?>" 
                                                    class="btn btn-sm btn-primary" title="<?php echo get_string('sendmessage', 'local_academic_dashboard'); ?>">
                                                    <i class="fa fa-envelope"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
                                                <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', 
                                                    ['studentid' => $alert->studentid]); ?>" 
                                                    class="btn btn-sm btn-secondary" title="<?php echo get_string('createtask', 'local_academic_dashboard'); ?>">
                                                    <i class="fa fa-tasks"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_capability('local/academic_dashboard:managealerts', $context)): ?>
                                                <a href="<?php echo new moodle_url('/local/academic_dashboard/alerts.php', 
                                                    ['action' => 'delete', 'id' => $alert->id, 'sesskey' => sesskey()]); ?>" 
                                                    class="btn btn-sm btn-danger" title="<?php echo get_string('delete', 'local_academic_dashboard'); ?>"
                                                    onclick="return confirm('<?php echo get_string('confirmdeletealert', 'local_academic_dashboard'); ?>');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php
                $baseurl = new moodle_url('/local/academic_dashboard/alerts.php', [
                    'alerttype' => $alerttype,
                    'status' => $status,
                    'classid' => $classid
                ]);
                echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
