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
 * Create/Edit task page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/academic_dashboard:managetasks', $context);

$id = optional_param('id', 0, PARAM_INT);
$studentid = optional_param('studentid', 0, PARAM_INT);
$classid = optional_param('classid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/task.php', ['id' => $id]));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css('/local/academic_dashboard/styles.css');

// Handle form submission.
if ($action === 'save' && confirm_sesskey()) {
    $data = new stdClass();
    $data->title = required_param('title', PARAM_TEXT);
    $data->description = optional_param('description', '', PARAM_RAW);
    $data->assigntype = required_param('assigntype', PARAM_ALPHA);
    $data->assigneeid = required_param('assigneeid', PARAM_INT);
    $data->priority = required_param('priority', PARAM_INT);
    $data->status = optional_param('status', 'open', PARAM_ALPHA);
    
    // Handle due date.
    $duedatestr = optional_param('duedate', '', PARAM_TEXT);
    if (!empty($duedatestr)) {
        $data->duedate = strtotime($duedatestr);
    }
    
    // Handle assignment type specific fields.
    if ($data->assigntype === 'student') {
        $data->studentid = required_param('studentid', PARAM_INT);
        $data->classid = null;
    } else if ($data->assigntype === 'class') {
        $data->classid = required_param('classid', PARAM_INT);
        $data->studentid = null;
    } else {
        $data->studentid = null;
        $data->classid = null;
    }
    
    // Handle recurring fields.
    $data->isrecurring = optional_param('isrecurring', 0, PARAM_INT);
    if ($data->isrecurring) {
        $data->recurringfreq = optional_param('recurringfreq', '', PARAM_ALPHA);
        $data->recurringday = optional_param('recurringday', 0, PARAM_INT);
        $recurringendstr = optional_param('recurringend', '', PARAM_TEXT);
        if (!empty($recurringendstr)) {
            $data->recurringend = strtotime($recurringendstr);
        }
    }
    
    try {
        if ($id > 0) {
            // Update existing task.
            $data->id = $id;
            \local_academic_dashboard\task_manager::update_task($data);
            redirect(new moodle_url('/local/academic_dashboard/task.php', ['id' => $id]),
                get_string('taskupdated', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Create new task.
            $newid = \local_academic_dashboard\task_manager::create_task($data);
            redirect(new moodle_url('/local/academic_dashboard/task.php', ['id' => $newid]),
                get_string('taskcreated', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    } catch (Exception $e) {
        redirect($PAGE->url, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Handle delete action.
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    try {
        \local_academic_dashboard\task_manager::delete_task($id);
        redirect(new moodle_url('/local/academic_dashboard/tasks.php'),
            get_string('taskdeleted', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (Exception $e) {
        redirect($PAGE->url, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Load existing task data.
$task = null;
if ($id > 0) {
    $task = \local_academic_dashboard\task_manager::get_task($id);
    if (!$task) {
        redirect(new moodle_url('/local/academic_dashboard/tasks.php'),
            get_string('tasknotfound', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_ERROR);
    }
    $PAGE->set_title(get_string('edittask', 'local_academic_dashboard'));
    $PAGE->set_heading(get_string('edittask', 'local_academic_dashboard'));
} else {
    $PAGE->set_title(get_string('newtask', 'local_academic_dashboard'));
    $PAGE->set_heading(get_string('newtask', 'local_academic_dashboard'));
}

// Get data for select fields.
$managers = get_users_by_capability($context, 'local/academic_dashboard:viewdashboard', 'u.id, u.firstname, u.lastname', 'u.lastname, u.firstname');
$classes = $DB->get_records('local_acad_classes', [], 'name ASC');
$students = $DB->get_records_sql(
    "SELECT u.id, u.firstname, u.lastname 
     FROM {user} u
     WHERE u.deleted = 0 AND u.suspended = 0
     ORDER BY u.lastname, u.firstname"
);

echo $OUTPUT->header();
?>

<div class="task-form-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $id > 0 ? get_string('edittask', 'local_academic_dashboard') : get_string('newtask', 'local_academic_dashboard'); ?></h2>
        <a href="<?php echo new moodle_url('/local/academic_dashboard/tasks.php'); ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?php echo get_string('back', 'local_academic_dashboard'); ?>
        </a>
    </div>

    <form method="post" action="" class="mform">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        <input type="hidden" name="action" value="save">
        <?php if ($id > 0): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <!-- Title -->
                <div class="form-group">
                    <label for="title"><?php echo get_string('tasktitle', 'local_academic_dashboard'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo $task ? s($task->title) : ''; ?>" required>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description"><?php echo get_string('taskdescription', 'local_academic_dashboard'); ?></label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo $task ? s($task->description) : ''; ?></textarea>
                </div>

                <!-- Assignment Type -->
                <div class="form-group">
                    <label for="assigntype"><?php echo get_string('taskassigntype', 'local_academic_dashboard'); ?> <span class="text-danger">*</span></label>
                    <select class="form-control" id="assigntype" name="assigntype" required>
                        <option value="general" <?php echo ($task && $task->assigntype === 'general') || (!$task && !$studentid && !$classid) ? 'selected' : ''; ?>>
                            <?php echo get_string('taskassigntype_general', 'local_academic_dashboard'); ?>
                        </option>
                        <option value="student" <?php echo ($task && $task->assigntype === 'student') || $studentid ? 'selected' : ''; ?>>
                            <?php echo get_string('taskassigntype_student', 'local_academic_dashboard'); ?>
                        </option>
                        <option value="class" <?php echo ($task && $task->assigntype === 'class') || $classid ? 'selected' : ''; ?>>
                            <?php echo get_string('taskassigntype_class', 'local_academic_dashboard'); ?>
                        </option>
                    </select>
                </div>

                <!-- Student Selection (conditional) -->
                <div class="form-group" id="student-field" style="display: none;">
                    <label for="studentid"><?php echo get_string('student', 'local_academic_dashboard'); ?> <span class="text-danger">*</span></label>
                    <select class="form-control" id="studentid" name="studentid">
                        <option value="0"><?php echo get_string('selectstudent', 'local_academic_dashboard'); ?></option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student->id; ?>" 
                                <?php echo ($task && $task->studentid == $student->id) || $studentid == $student->id ? 'selected' : ''; ?>>
                                <?php echo fullname($student); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Class Selection (conditional) -->
                <div class="form-group" id="class-field" style="display: none;">
                    <label for="classid"><?php echo get_string('class', 'local_academic_dashboard'); ?> <span class="text-danger">*</span></label>
                    <select class="form-control" id="classid" name="classid">
                        <option value="0"><?php echo get_string('selectclass', 'local_academic_dashboard'); ?></option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class->id; ?>" 
                                <?php echo ($task && $task->classid == $class->id) || $classid == $class->id ? 'selected' : ''; ?>>
                                <?php echo format_string($class->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Assignee -->
                <div class="form-group">
                    <label for="assigneeid"><?php echo get_string('taskassignee', 'local_academic_dashboard'); ?> <span class="text-danger">*</span></label>
                    <select class="form-control" id="assigneeid" name="assigneeid" required>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?php echo $manager->id; ?>" 
                                <?php echo ($task && $task->assigneeid == $manager->id) || (!$task && $manager->id == $USER->id) ? 'selected' : ''; ?>>
                                <?php echo fullname($manager); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Due Date -->
                <div class="form-group">
                    <label for="duedate"><?php echo get_string('taskduedate', 'local_academic_dashboard'); ?></label>
                    <input type="date" class="form-control" id="duedate" name="duedate" 
                           value="<?php echo $task && $task->duedate ? date('Y-m-d', $task->duedate) : ''; ?>">
                </div>

                <!-- Priority -->
                <div class="form-group">
                    <label for="priority"><?php echo get_string('taskpriority', 'local_academic_dashboard'); ?> <span class="text-danger">*</span></label>
                    <select class="form-control" id="priority" name="priority" required>
                        <option value="1" <?php echo $task && $task->priority == 1 ? 'selected' : ''; ?>>
                            <?php echo get_string('taskpriority_low', 'local_academic_dashboard'); ?>
                        </option>
                        <option value="2" <?php echo (!$task || $task->priority == 2) ? 'selected' : ''; ?>>
                            <?php echo get_string('taskpriority_medium', 'local_academic_dashboard'); ?>
                        </option>
                        <option value="3" <?php echo $task && $task->priority == 3 ? 'selected' : ''; ?>>
                            <?php echo get_string('taskpriority_high', 'local_academic_dashboard'); ?>
                        </option>
                    </select>
                </div>

                <!-- Status (only for edit) -->
                <?php if ($id > 0): ?>
                <div class="form-group">
                    <label for="status"><?php echo get_string('taskstatus', 'local_academic_dashboard'); ?></label>
                    <select class="form-control" id="status" name="status">
                        <option value="open" <?php echo $task->status === 'open' ? 'selected' : ''; ?>>
                            <?php echo get_string('taskstatus_open', 'local_academic_dashboard'); ?>
                        </option>
                        <option value="inprogress" <?php echo $task->status === 'inprogress' ? 'selected' : ''; ?>>
                            <?php echo get_string('taskstatus_inprogress', 'local_academic_dashboard'); ?>
                        </option>
                        <option value="completed" <?php echo $task->status === 'completed' ? 'selected' : ''; ?>>
                            <?php echo get_string('taskstatus_completed', 'local_academic_dashboard'); ?>
                        </option>
                        <option value="cancelled" <?php echo $task->status === 'cancelled' ? 'selected' : ''; ?>>
                            <?php echo get_string('taskstatus_cancelled', 'local_academic_dashboard'); ?>
                        </option>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Recurring Task -->
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="isrecurring" name="isrecurring" value="1"
                               <?php echo $task && $task->isrecurring ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="isrecurring">
                            <?php echo get_string('taskrecurring', 'local_academic_dashboard'); ?>
                        </label>
                    </div>
                </div>

                <!-- Recurring Options (conditional) -->
                <div id="recurring-options" style="display: none;">
                    <div class="form-group">
                        <label for="recurringfreq"><?php echo get_string('taskrecurringfreq', 'local_academic_dashboard'); ?></label>
                        <select class="form-control" id="recurringfreq" name="recurringfreq">
                            <option value="daily" <?php echo $task && $task->recurringfreq === 'daily' ? 'selected' : ''; ?>>
                                <?php echo get_string('daily', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="weekly" <?php echo $task && $task->recurringfreq === 'weekly' ? 'selected' : ''; ?>>
                                <?php echo get_string('weekly', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="monthly" <?php echo $task && $task->recurringfreq === 'monthly' ? 'selected' : ''; ?>>
                                <?php echo get_string('monthly', 'local_academic_dashboard'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="recurringend"><?php echo get_string('taskrecurringend', 'local_academic_dashboard'); ?></label>
                        <input type="date" class="form-control" id="recurringend" name="recurringend"
                               value="<?php echo $task && $task->recurringend ? date('Y-m-d', $task->recurringend) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> <?php echo get_string('save', 'local_academic_dashboard'); ?>
                        </button>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/tasks.php'); ?>" class="btn btn-secondary">
                            <?php echo get_string('cancel', 'local_academic_dashboard'); ?>
                        </a>
                    </div>
                    <?php if ($id > 0): ?>
                    <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['id' => $id, 'action' => 'delete', 'sesskey' => sesskey()]); ?>" 
                       class="btn btn-danger" onclick="return confirm('<?php echo get_string('confirmdeletetask', 'local_academic_dashboard'); ?>');">
                        <i class="fa fa-trash"></i> <?php echo get_string('delete', 'local_academic_dashboard'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Show/hide conditional fields based on assignment type
document.getElementById('assigntype').addEventListener('change', function() {
    var studentField = document.getElementById('student-field');
    var classField = document.getElementById('class-field');
    var type = this.value;
    
    studentField.style.display = type === 'student' ? 'block' : 'none';
    classField.style.display = type === 'class' ? 'block' : 'none';
    
    // Update required attribute
    document.getElementById('studentid').required = type === 'student';
    document.getElementById('classid').required = type === 'class';
});

// Show/hide recurring options
document.getElementById('isrecurring').addEventListener('change', function() {
    document.getElementById('recurring-options').style.display = this.checked ? 'block' : 'none';
});

// Trigger on page load
document.getElementById('assigntype').dispatchEvent(new Event('change'));
if (document.getElementById('isrecurring').checked) {
    document.getElementById('recurring-options').style.display = 'block';
}
</script>

<?php
echo $OUTPUT->footer();
