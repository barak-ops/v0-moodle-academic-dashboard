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
 * Tasks list page.
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

// Filter parameters.
$status = optional_param('status', '', PARAM_ALPHA);
$assigneeid = optional_param('assigneeid', 0, PARAM_INT);
$classid = optional_param('classid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/tasks.php'));
$PAGE->set_title(get_string('tasks', 'local_academic_dashboard'));
$PAGE->set_heading(get_string('tasks', 'local_academic_dashboard'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css('/local/academic_dashboard/styles.css');

// Build filters.
$filters = [];
if (!empty($status)) {
    $filters['status'] = $status;
}
if ($assigneeid > 0) {
    $filters['assigneeid'] = $assigneeid;
}
if ($classid > 0) {
    $filters['classid'] = $classid;
}

// Get tasks.
$tasks = \local_academic_dashboard\task_manager::get_tasks($filters, 'duedate', 'ASC', $page * $perpage, $perpage);
$totalcount = count(\local_academic_dashboard\task_manager::get_tasks($filters));

// Get filter options.
$managers = get_users_by_capability($context, 'local/academic_dashboard:viewdashboard', 'u.id, u.firstname, u.lastname');
$classes = $DB->get_records('local_acad_classes', [], 'name ASC');

echo $OUTPUT->header();
?>

<div class="tasks-page">
    <!-- Header with Add button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo get_string('tasks', 'local_academic_dashboard'); ?></h2>
        <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
            <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php'); ?>" class="btn btn-primary">
                <i class="fa fa-plus"></i> <?php echo get_string('newtask', 'local_academic_dashboard'); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="" class="form-inline">
                <div class="row w-100">
                    <div class="col-md-3 mb-2">
                        <label for="status"><?php echo get_string('taskstatus', 'local_academic_dashboard'); ?></label>
                        <select name="status" id="status" class="form-control w-100">
                            <option value=""><?php echo get_string('filterall', 'local_academic_dashboard'); ?></option>
                            <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>><?php echo get_string('taskstatus_open', 'local_academic_dashboard'); ?></option>
                            <option value="inprogress" <?php echo $status === 'inprogress' ? 'selected' : ''; ?>><?php echo get_string('taskstatus_inprogress', 'local_academic_dashboard'); ?></option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>><?php echo get_string('taskstatus_completed', 'local_academic_dashboard'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="assigneeid"><?php echo get_string('taskassignee', 'local_academic_dashboard'); ?></label>
                        <select name="assigneeid" id="assigneeid" class="form-control w-100">
                            <option value="0"><?php echo get_string('filterall', 'local_academic_dashboard'); ?></option>
                            <?php foreach ($managers as $m): ?>
                                <option value="<?php echo $m->id; ?>" <?php echo $assigneeid == $m->id ? 'selected' : ''; ?>>
                                    <?php echo $m->lastname . ', ' . $m->firstname; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="classid"><?php echo get_string('filterclass', 'local_academic_dashboard'); ?></label>
                        <select name="classid" id="classid" class="form-control w-100">
                            <option value="0"><?php echo get_string('filterall', 'local_academic_dashboard'); ?></option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c->id; ?>" <?php echo $classid == $c->id ? 'selected' : ''; ?>>
                                    <?php echo format_string($c->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2"><?php echo get_string('filterapply', 'local_academic_dashboard'); ?></button>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/tasks.php'); ?>" class="btn btn-secondary">
                            <?php echo get_string('filterclear', 'local_academic_dashboard'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th><?php echo get_string('tasktitle', 'local_academic_dashboard'); ?></th>
                        <th><?php echo get_string('taskassigntype', 'local_academic_dashboard'); ?></th>
                        <th><?php echo get_string('taskassignee', 'local_academic_dashboard'); ?></th>
                        <th><?php echo get_string('taskduedate', 'local_academic_dashboard'); ?></th>
                        <th><?php echo get_string('taskpriority', 'local_academic_dashboard'); ?></th>
                        <th><?php echo get_string('taskstatus', 'local_academic_dashboard'); ?></th>
                        <th><?php echo get_string('actions', 'local_academic_dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted p-4">
                                <?php echo get_string('nodata', 'local_academic_dashboard'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr class="<?php echo ($task->duedate && $task->duedate < time() && $task->status !== 'completed') ? 'table-danger' : ''; ?>">
                                <td>
                                    <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['id' => $task->id]); ?>">
                                        <?php echo format_string($task->title); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">
                                        <?php echo get_string('taskassigntype_' . $task->assigntype, 'local_academic_dashboard'); ?>
                                    </span>
                                </td>
                                <td><?php echo $task->assignee_firstname . ' ' . $task->assignee_lastname; ?></td>
                                <td>
                                    <?php echo $task->duedate ? userdate($task->duedate, get_string('strftimedateshort')) : '-'; ?>
                                </td>
                                <td>
                                    <?php
                                    $priorityclass = ['1' => 'success', '2' => 'warning', '3' => 'danger'];
                                    ?>
                                    <span class="badge badge-<?php echo $priorityclass[$task->priority]; ?>">
                                        <?php echo get_string('taskpriority_' . ['1' => 'low', '2' => 'medium', '3' => 'high'][$task->priority], 'local_academic_dashboard'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusclass = ['open' => 'warning', 'inprogress' => 'info', 'completed' => 'success', 'cancelled' => 'secondary'];
                                    ?>
                                    <span class="badge badge-<?php echo $statusclass[$task->status]; ?>">
                                        <?php echo get_string('taskstatus_' . $task->status, 'local_academic_dashboard'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['id' => $task->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo get_string('edit', 'local_academic_dashboard'); ?>">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <?php if ($task->status !== 'completed' && has_capability('local/academic_dashboard:managetasks', $context)): ?>
                                        <button class="btn btn-sm btn-outline-success complete-task" data-taskid="<?php echo $task->id; ?>" title="<?php echo get_string('taskmarkcomplete', 'local_academic_dashboard'); ?>">
                                            <i class="fa fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php
    $baseurl = new moodle_url('/local/academic_dashboard/tasks.php', [
        'status' => $status,
        'assigneeid' => $assigneeid,
        'classid' => $classid,
    ]);
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
    ?>
</div>

<?php
// Add JavaScript for task completion.
$PAGE->requires->js_amd_inline("
    require(['jquery'], function($) {
        $('.complete-task').on('click', function() {
            var taskid = $(this).data('taskid');
            var row = $(this).closest('tr');
            
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
                        row.fadeOut(function() {
                            location.reload();
                        });
                    }
                }
            });
        });
    });
");

echo $OUTPUT->footer();
