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
 * Student card page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

$id = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/academic_dashboard:viewstudentcard', $context);

// Get student data.
$student = $DB->get_record('user', ['id' => $id, 'deleted' => 0], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/student.php', ['id' => $id]));
$PAGE->set_title(get_string('studentcard', 'local_academic_dashboard') . ' - ' . fullname($student));
$PAGE->set_heading(get_string('studentcard', 'local_academic_dashboard'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css('/local/academic_dashboard/styles.css');

// Get student's classes.
$classes = $DB->get_records_sql("
    SELECT c.* FROM {local_acad_classes} c
    JOIN {local_acad_class_members} cm ON cm.classid = c.id
    WHERE cm.userid = :userid
", ['userid' => $id]);

// Get completion data.
$completiondata = local_academic_dashboard_get_student_completion($id);

// Get open tasks for student.
$opentasks = \local_academic_dashboard\task_manager::get_tasks([
    'studentid' => $id,
    'status' => 'open',
]);

// Get service requests.
$requests = \local_academic_dashboard\service_request_manager::get_requests([
    'studentid' => $id,
], 'timecreated', 'DESC', 0, 5);

echo $OUTPUT->header();
?>

<div class="student-card">
    <div class="student-card-header">
        <img src="<?php echo $OUTPUT->user_picture($student, ['size' => 100]); ?>" alt="" class="student-avatar">
        <div class="student-info">
            <h2><?php echo fullname($student); ?></h2>
            <p><?php echo $student->email; ?></p>
            <p class="text-muted">
                <?php echo get_string('studentlastactivity', 'local_academic_dashboard'); ?>:
                <?php echo $student->lastaccess ? userdate($student->lastaccess) : get_string('never'); ?>
            </p>
        </div>
    </div>

    <div class="student-card-body">
        <!-- Classes Section -->
        <div class="card-section">
            <h4><i class="fa fa-users"></i> <?php echo get_string('studentclasses', 'local_academic_dashboard'); ?></h4>
            <?php if (empty($classes)): ?>
                <p class="text-muted"><?php echo get_string('nodata', 'local_academic_dashboard'); ?></p>
            <?php else: ?>
                <ul class="list-unstyled">
                    <?php foreach ($classes as $class): ?>
                        <li>
                            <a href="<?php echo new moodle_url('/local/academic_dashboard/class.php', ['id' => $class->id]); ?>">
                                <?php echo format_string($class->name); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Progress Section -->
        <div class="card-section">
            <h4><i class="fa fa-chart-line"></i> <?php echo get_string('studentprogress', 'local_academic_dashboard'); ?></h4>
            <?php if (empty($completiondata)): ?>
                <p class="text-muted"><?php echo get_string('nodata', 'local_academic_dashboard'); ?></p>
            <?php else: ?>
                <?php foreach ($completiondata as $course): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><?php echo format_string($course['coursename']); ?></span>
                            <span><?php echo round($course['progress'] ?? 0); ?>%</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-bar-fill" style="width: <?php echo $course['progress'] ?? 0; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Open Tasks Section -->
        <div class="card-section">
            <h4><i class="fa fa-tasks"></i> <?php echo get_string('studentopentasks', 'local_academic_dashboard'); ?></h4>
            <?php if (empty($opentasks)): ?>
                <p class="text-muted"><?php echo get_string('nodata', 'local_academic_dashboard'); ?></p>
            <?php else: ?>
                <ul class="list-unstyled">
                    <?php foreach ($opentasks as $task): ?>
                        <li class="mb-2">
                            <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['id' => $task->id]); ?>">
                                <?php echo format_string($task->title); ?>
                            </a>
                            <?php if ($task->duedate): ?>
                                <small class="text-muted d-block">
                                    <?php echo userdate($task->duedate, get_string('strftimedateshort')); ?>
                                </small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Service Requests Section -->
        <div class="card-section">
            <h4><i class="fa fa-headset"></i> <?php echo get_string('studentrequests', 'local_academic_dashboard'); ?></h4>
            <?php if (empty($requests)): ?>
                <p class="text-muted"><?php echo get_string('nodata', 'local_academic_dashboard'); ?></p>
            <?php else: ?>
                <ul class="list-unstyled">
                    <?php foreach ($requests as $request): ?>
                        <li class="mb-2">
                            <a href="<?php echo new moodle_url('/local/academic_dashboard/request.php', ['id' => $request->id]); ?>">
                                #<?php echo $request->id; ?> - 
                                <?php echo get_string('requesttype_' . $request->requesttype, 'local_academic_dashboard'); ?>
                            </a>
                            <span class="badge badge-<?php echo $request->status === 'open' ? 'warning' : 'secondary'; ?>">
                                <?php echo get_string('requeststatus_' . $request->status, 'local_academic_dashboard'); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="card-actions">
        <?php if (has_capability('local/academic_dashboard:sendmessages', $context)): ?>
            <a href="<?php echo new moodle_url('/message/index.php', ['id' => $student->id]); ?>" class="btn btn-primary">
                <i class="fa fa-envelope"></i>
                <?php echo get_string('studentmessage', 'local_academic_dashboard'); ?>
            </a>
        <?php endif; ?>

        <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
            <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['studentid' => $student->id]); ?>" class="btn btn-secondary">
                <i class="fa fa-plus"></i>
                <?php echo get_string('studentcreatetask', 'local_academic_dashboard'); ?>
            </a>
        <?php endif; ?>

        <?php if (has_capability('local/academic_dashboard:manageservicerequests', $context)): ?>
            <a href="<?php echo new moodle_url('/local/academic_dashboard/request.php', ['studentid' => $student->id]); ?>" class="btn btn-info">
                <i class="fa fa-ticket-alt"></i>
                <?php echo get_string('studentcreaterequest', 'local_academic_dashboard'); ?>
            </a>
        <?php endif; ?>

        <a href="<?php echo new moodle_url('/user/profile.php', ['id' => $student->id]); ?>" class="btn btn-outline-secondary">
            <i class="fa fa-user"></i>
            <?php echo get_string('view', 'local_academic_dashboard'); ?> Profile
        </a>
    </div>
</div>

<?php
echo $OUTPUT->footer();
