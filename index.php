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
 * Main dashboard page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/academic_dashboard:viewdashboard', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/index.php'));
$PAGE->set_title(get_string('academic_dashboard', 'local_academic_dashboard'));
$PAGE->set_heading(get_string('academic_dashboard', 'local_academic_dashboard'));
$PAGE->set_pagelayout('standard');

// Add CSS.
$PAGE->requires->css('/local/academic_dashboard/styles.css');

// Get filter parameters.
$classid = optional_param('classid', 0, PARAM_INT);
$studentid = optional_param('studentid', 0, PARAM_INT);
$datefrom = optional_param('datefrom', '', PARAM_TEXT);
$dateto = optional_param('dateto', '', PARAM_TEXT);

// Get dashboard data.
$taskmanager = new \local_academic_dashboard\task_manager();
$requestmanager = new \local_academic_dashboard\service_request_manager();

$taskstoday = $taskmanager::get_tasks_today($USER->id);
$tasksweek = $taskmanager::get_tasks_week($USER->id);
$tasksoverdue = $taskmanager::get_overdue_tasks($USER->id);
$openrequests = $requestmanager::get_requests(['status' => 'open'], 'timecreated', 'DESC', 0, 10);
$atriskstudents = local_academic_dashboard_get_atrisk_students($classid);

// Get classes for filter.
$classes = $DB->get_records('local_acad_classes', [], 'name ASC');

echo $OUTPUT->header();

// Render dashboard.
?>
<div class="academic-dashboard">
    <!-- Filters Section -->
    <div class="dashboard-filters card mb-4">
        <div class="card-body">
            <form method="get" action="" class="form-inline">
                <div class="row w-100">
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
                    <div class="col-md-3">
                        <label for="datefrom"><?php echo get_string('filterfrom', 'local_academic_dashboard'); ?></label>
                        <input type="date" name="datefrom" id="datefrom" class="form-control" value="<?php echo $datefrom; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="dateto"><?php echo get_string('filterto', 'local_academic_dashboard'); ?></label>
                        <input type="date" name="dateto" id="dateto" class="form-control" value="<?php echo $dateto; ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2"><?php echo get_string('filterapply', 'local_academic_dashboard'); ?></button>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/index.php'); ?>" class="btn btn-secondary">
                            <?php echo get_string('filterclear', 'local_academic_dashboard'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Tasks Today Widget -->
        <div class="dashboard-widget card">
            <!-- Changed bg-primary to bg-light for better readability -->
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fa fa-calendar-day"></i>
                    <?php echo get_string('widget_tasks_today', 'local_academic_dashboard'); ?>
                    <span class="badge badge-primary"><?php echo count($taskstoday); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($taskstoday)): ?>
                    <p class="text-muted"><?php echo get_string('nodata', 'local_academic_dashboard'); ?></p>
                <?php else: ?>
                    <ul class="task-list">
                        <?php foreach ($taskstoday as $task): ?>
                            <li class="task-item priority-<?php echo $task->priority; ?>">
                                <div class="task-title">
                                    <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['id' => $task->id]); ?>">
                                        <?php echo format_string($task->title); ?>
                                    </a>
                                </div>
                                <div class="task-meta">
                                    <span class="task-assignee"><?php echo $task->assignee_firstname . ' ' . $task->assignee_lastname; ?></span>
                                    <span class="task-status badge badge-<?php echo $task->status === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo get_string('taskstatus_' . $task->status, 'local_academic_dashboard'); ?>
                                    </span>
                                </div>
                                <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
                                    <button class="btn btn-sm btn-outline-success complete-task" data-taskid="<?php echo $task->id; ?>">
                                        <i class="fa fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo new moodle_url('/local/academic_dashboard/tasks.php'); ?>" class="btn btn-link">
                    <?php echo get_string('view', 'local_academic_dashboard'); ?> <?php echo get_string('tasks', 'local_academic_dashboard'); ?>
                </a>
            </div>
        </div>

        <!-- Overdue Tasks Widget -->
        <div class="dashboard-widget card">
            <!-- Changed bg-danger to border-danger for better readability -->
            <div class="card-header bg-light border-danger">
                <h5 class="mb-0 text-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <?php echo get_string('widget_tasks_overdue', 'local_academic_dashboard'); ?>
                    <span class="badge badge-danger"><?php echo count($tasksoverdue); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($tasksoverdue)): ?>
                    <p class="text-muted"><?php echo get_string('nodata', 'local_academic_dashboard'); ?></p>
                <?php else: ?>
                    <ul class="task-list">
                        <?php foreach (array_slice($tasksoverdue, 0, 5) as $task): ?>
                            <li class="task-item overdue">
                                <div class="task-title">
                                    <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['id' => $task->id]); ?>">
                                        <?php echo format_string($task->title); ?>
                                    </a>
                                </div>
                                <div class="task-meta">
                                    <span class="task-due text-danger">
                                        <?php echo userdate($task->duedate, get_string('strftimedateshort')); ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- At-Risk Students Widget -->
        <?php if (has_capability('local/academic_dashboard:viewalerts', $context)): ?>
        <div class="dashboard-widget card">
            <!-- Changed bg-warning to border-warning for better readability -->
            <div class="card-header bg-light border-warning">
                <h5 class="mb-0 text-warning">
                    <i class="fa fa-user-clock"></i>
                    <?php echo get_string('widget_atrisk_students', 'local_academic_dashboard'); ?>
                    <span class="badge badge-warning"><?php echo count($atriskstudents); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($atriskstudents)): ?>
                    <p class="text-muted"><?php echo get_string('nodata', 'local_academic_dashboard'); ?></p>
                <?php else: ?>
                    <ul class="student-list">
                        <?php foreach (array_slice($atriskstudents, 0, 5) as $student): ?>
                            <li class="student-item">
                                <div class="student-name">
                                    <a href="<?php echo new moodle_url('/local/academic_dashboard/student.php', ['id' => $student['userid']]); ?>">
                                        <?php echo $student['firstname'] . ' ' . $student['lastname']; ?>
                                    </a>
                                </div>
                                <div class="student-reasons">
                                    <?php foreach ($student['reasons'] as $reason): ?>
                                        <span class="badge badge-warning">
                                            <?php echo get_string('alerttype_' . $reason, 'local_academic_dashboard'); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="student-actions">
                                    <?php if (has_capability('local/academic_dashboard:sendmessages', $context)): ?>
                                        <a href="<?php echo new moodle_url('/message/index.php', ['id' => $student['userid']]); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-envelope"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
                                        <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['studentid' => $student['userid']]); ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa fa-tasks"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo new moodle_url('/local/academic_dashboard/alerts.php'); ?>" class="btn btn-link">
                    <?php echo get_string('view', 'local_academic_dashboard'); ?> <?php echo get_string('alerts', 'local_academic_dashboard'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Open Service Requests Widget -->
        <?php if (has_capability('local/academic_dashboard:viewservicerequests', $context)): ?>
        <div class="dashboard-widget card">
            <!-- Changed bg-info text-white to border-info for better readability -->
            <div class="card-header bg-light border-info">
                <h5 class="mb-0 text-info">
                    <i class="fa fa-headset"></i>
                    <?php echo get_string('widget_open_requests', 'local_academic_dashboard'); ?>
                    <span class="badge badge-info"><?php echo count($openrequests); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($openrequests)): ?>
                    <p class="text-muted"><?php echo get_string('nodata', 'local_academic_dashboard'); ?></p>
                <?php else: ?>
                    <ul class="request-list">
                        <?php foreach ($openrequests as $request): ?>
                            <li class="request-item">
                                <div class="request-type badge badge-secondary">
                                    <?php echo get_string('requesttype_' . $request->requesttype, 'local_academic_dashboard'); ?>
                                </div>
                                <div class="request-student">
                                    <a href="<?php echo new moodle_url('/local/academic_dashboard/student.php', ['id' => $request->studentid]); ?>">
                                        <?php echo $request->student_firstname . ' ' . $request->student_lastname; ?>
                                    </a>
                                </div>
                                <div class="request-date text-muted">
                                    <?php echo userdate($request->timecreated, get_string('strftimedateshort')); ?>
                                </div>
                                <a href="<?php echo new moodle_url('/local/academic_dashboard/request.php', ['id' => $request->id]); ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo new moodle_url('/local/academic_dashboard/requests.php'); ?>" class="btn btn-link">
                    <?php echo get_string('view', 'local_academic_dashboard'); ?> <?php echo get_string('servicerequests', 'local_academic_dashboard'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Access Widget -->
        <div class="dashboard-widget card">
            <!-- Changed bg-secondary text-white to bg-light for better readability -->
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fa fa-bolt"></i>
                    <?php echo get_string('widget_quick_access', 'local_academic_dashboard'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="quick-access-grid">
                    <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php'); ?>" class="quick-access-item">
                            <i class="fa fa-plus-circle"></i>
                            <span><?php echo get_string('newtask', 'local_academic_dashboard'); ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (has_capability('local/academic_dashboard:viewstudentcard', $context)): ?>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/students.php'); ?>" class="quick-access-item">
                            <i class="fa fa-users"></i>
                            <span><?php echo get_string('nav_students', 'local_academic_dashboard'); ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (has_capability('local/academic_dashboard:viewclasscard', $context)): ?>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/classes.php'); ?>" class="quick-access-item">
                            <i class="fa fa-chalkboard-teacher"></i>
                            <span><?php echo get_string('nav_classes', 'local_academic_dashboard'); ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (has_capability('local/academic_dashboard:manageservicerequests', $context)): ?>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/request.php'); ?>" class="quick-access-item">
                            <i class="fa fa-ticket-alt"></i>
                            <span><?php echo get_string('newrequest', 'local_academic_dashboard'); ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (has_capability('local/academic_dashboard:viewcalendar', $context)): ?>
                        <a href="<?php echo new moodle_url('/calendar/view.php'); ?>" class="quick-access-item">
                            <i class="fa fa-calendar-alt"></i>
                            <span><?php echo get_string('calendar', 'local_academic_dashboard'); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add JavaScript for task completion.
$PAGE->requires->js_amd_inline("
    require(['jquery'], function($) {
        $('.complete-task').on('click', function() {
            var taskid = $(this).data('taskid');
            var btn = $(this);
            
            $.ajax({
                url: M.cfg.wwwroot + '/local/academic_dashboard/ajax.php',
                method: 'POST',
                data: {
                    action: 'complete_task',
                    taskid: taskid,
                    sesskey: M.cfg.sesskey
                },
                success: function(response) {
                    if (response.success) {
                        btn.closest('.task-item').fadeOut();
                    }
                }
            });
        });
    });
");

echo $OUTPUT->footer();
