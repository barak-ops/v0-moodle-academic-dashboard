<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

use local_academic_dashboard\service_request_manager;

$requestid = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

require_login();
$context = context_system::instance();
require_capability('local/academic_dashboard:viewdashboard', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/academic_dashboard/request.php', ['id' => $requestid]);
$PAGE->set_pagelayout('admin');

// Handle form submission
if ($action === 'save' && confirm_sesskey()) {
    $data = new stdClass();
    $data->studentid = required_param('studentid', PARAM_INT);
    $data->requesttype = required_param('requesttype', PARAM_TEXT);
    $data->description = required_param('description', PARAM_TEXT);
    $data->assigneeid = optional_param('assigneeid', 0, PARAM_INT);
    $data->internalnotes = optional_param('internalnotes', PARAM_RAW);
    $data->createtask = optional_param('createtask', 0, PARAM_INT);

    if ($requestid > 0) {
        // Update existing request
        $data->id = $requestid;
        $data->status = required_param('status', PARAM_TEXT);
        service_request_manager::update_request($data);
        redirect(new moodle_url('/local/academic_dashboard/request.php', ['id' => $requestid]), 
                 get_string('requestupdated', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Create new request
        $newid = service_request_manager::create_request($data);
        redirect(new moodle_url('/local/academic_dashboard/request.php', ['id' => $newid]), 
                 get_string('requestcreated', 'local_academic_dashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Get request data if editing
$request = null;
$history = [];
if ($requestid > 0) {
    $request = service_request_manager::get_request($requestid);
    $history = service_request_manager::get_history($requestid);
    $PAGE->set_title(get_string('editrequest', 'local_academic_dashboard'));
    $PAGE->navbar->add(get_string('servicerequests', 'local_academic_dashboard'), new moodle_url('/local/academic_dashboard/requests.php'));
    $PAGE->navbar->add(get_string('editrequest', 'local_academic_dashboard'));
} else {
    $PAGE->set_title(get_string('newrequest', 'local_academic_dashboard'));
    $PAGE->navbar->add(get_string('servicerequests', 'local_academic_dashboard'), new moodle_url('/local/academic_dashboard/requests.php'));
    $PAGE->navbar->add(get_string('newrequest', 'local_academic_dashboard'));
}

// Get list of students
$students = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname, u.email
    FROM {user} u
    WHERE u.deleted = 0 AND u.suspended = 0
    ORDER BY u.lastname ASC, u.firstname ASC
");

// Get list of potential assignees (users with capability)
$assignees = get_users_by_capability($context, 'local/academic_dashboard:managetasks', 'id, firstname, lastname', 'lastname ASC, firstname ASC');

echo $OUTPUT->header();

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h3 class="mb-0">
                        <?php echo $requestid > 0 ? get_string('editrequest', 'local_academic_dashboard') : get_string('newrequest', 'local_academic_dashboard'); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="post" action="request.php" class="mform">
                        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                        <input type="hidden" name="action" value="save">
                        <?php if ($requestid > 0): ?>
                            <input type="hidden" name="id" value="<?php echo $requestid; ?>">
                        <?php endif; ?>

                        <div class="form-group row">
                            <label for="studentid" class="col-md-3 col-form-label">
                                <?php echo get_string('student', 'local_academic_dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <div class="col-md-9">
                                <select name="studentid" id="studentid" class="form-control" required <?php echo $requestid > 0 ? 'disabled' : ''; ?>>
                                    <option value=""><?php echo get_string('selectstudent', 'local_academic_dashboard'); ?></option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student->id; ?>" <?php echo ($request && $request->studentid == $student->id) ? 'selected' : ''; ?>>
                                            <?php echo fullname($student); ?> (<?php echo $student->email; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($requestid > 0): ?>
                                    <input type="hidden" name="studentid" value="<?php echo $request->studentid; ?>">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="requesttype" class="col-md-3 col-form-label">
                                <?php echo get_string('requesttype', 'local_academic_dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <div class="col-md-9">
                                <select name="requesttype" id="requesttype" class="form-control" required>
                                    <option value=""><?php echo get_string('selecttype', 'local_academic_dashboard'); ?></option>
                                    <option value="academic" <?php echo ($request && $request->requesttype == 'academic') ? 'selected' : ''; ?>><?php echo get_string('academic', 'local_academic_dashboard'); ?></option>
                                    <option value="administrative" <?php echo ($request && $request->requesttype == 'administrative') ? 'selected' : ''; ?>><?php echo get_string('administrative', 'local_academic_dashboard'); ?></option>
                                    <option value="technical" <?php echo ($request && $request->requesttype == 'technical') ? 'selected' : ''; ?>><?php echo get_string('technical', 'local_academic_dashboard'); ?></option>
                                    <option value="financial" <?php echo ($request && $request->requesttype == 'financial') ? 'selected' : ''; ?>><?php echo get_string('financial', 'local_academic_dashboard'); ?></option>
                                    <option value="other" <?php echo ($request && $request->requesttype == 'other') ? 'selected' : ''; ?>><?php echo get_string('other', 'local_academic_dashboard'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="description" class="col-md-3 col-form-label">
                                <?php echo get_string('description', 'local_academic_dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <div class="col-md-9">
                                <textarea name="description" id="description" class="form-control" rows="5" required><?php echo $request ? s($request->description) : ''; ?></textarea>
                            </div>
                        </div>

                        <?php if ($requestid > 0): ?>
                            <div class="form-group row">
                                <label for="status" class="col-md-3 col-form-label">
                                    <?php echo get_string('status', 'local_academic_dashboard'); ?>
                                </label>
                                <div class="col-md-9">
                                    <select name="status" id="status" class="form-control">
                                        <option value="open" <?php echo ($request && $request->status == 'open') ? 'selected' : ''; ?>><?php echo get_string('open', 'local_academic_dashboard'); ?></option>
                                        <option value="inprogress" <?php echo ($request && $request->status == 'inprogress') ? 'selected' : ''; ?>><?php echo get_string('inprogress', 'local_academic_dashboard'); ?></option>
                                        <option value="resolved" <?php echo ($request && $request->status == 'resolved') ? 'selected' : ''; ?>><?php echo get_string('resolved', 'local_academic_dashboard'); ?></option>
                                        <option value="closed" <?php echo ($request && $request->status == 'closed') ? 'selected' : ''; ?>><?php echo get_string('closed', 'local_academic_dashboard'); ?></option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group row">
                            <label for="assigneeid" class="col-md-3 col-form-label">
                                <?php echo get_string('assignee', 'local_academic_dashboard'); ?>
                            </label>
                            <div class="col-md-9">
                                <select name="assigneeid" id="assigneeid" class="form-control">
                                    <option value="0"><?php echo get_string('unassigned', 'local_academic_dashboard'); ?></option>
                                    <?php foreach ($assignees as $assignee): ?>
                                        <option value="<?php echo $assignee->id; ?>" <?php echo ($request && $request->assigneeid == $assignee->id) ? 'selected' : ''; ?>>
                                            <?php echo fullname($assignee); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="internalnotes" class="col-md-3 col-form-label">
                                <?php echo get_string('internalnotes', 'local_academic_dashboard'); ?>
                            </label>
                            <div class="col-md-9">
                                <textarea name="internalnotes" id="internalnotes" class="form-control" rows="3"><?php echo $request ? s($request->internalnotes) : ''; ?></textarea>
                                <small class="form-text text-muted"><?php echo get_string('internalnoteshelp', 'local_academic_dashboard'); ?></small>
                            </div>
                        </div>

                        <?php if (!$requestid): ?>
                            <div class="form-group row">
                                <div class="col-md-9 offset-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="createtask" name="createtask" value="1">
                                        <label class="form-check-label" for="createtask">
                                            <?php echo get_string('createfollowuptask', 'local_academic_dashboard'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group row">
                            <div class="col-md-9 offset-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> <?php echo get_string('save', 'moodle'); ?>
                                </button>
                                <a href="requests.php" class="btn btn-secondary">
                                    <i class="fa fa-times"></i> <?php echo get_string('cancel', 'moodle'); ?>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($requestid > 0 && !empty($history)): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h4 class="mb-0"><?php echo get_string('history', 'local_academic_dashboard'); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($history as $entry): ?>
                                <div class="timeline-item mb-3">
                                    <div class="timeline-badge bg-primary"></div>
                                    <div class="timeline-content">
                                        <p class="mb-1">
                                            <strong><?php echo fullname($entry); ?></strong>
                                            <small class="text-muted float-right">
                                                <?php echo userdate($entry->timecreated, get_string('strftimedatetime', 'langconfig')); ?>
                                            </small>
                                        </p>
                                        <p class="mb-0">
                                            <span class="badge badge-secondary"><?php echo s($entry->action); ?></span>
                                            <?php if ($entry->details): ?>
                                                <br><small><?php echo s($entry->details); ?></small>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
}

.timeline-badge {
    position: absolute;
    left: -28px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -23px;
    top: 17px;
    width: 2px;
    height: calc(100% + 10px);
    background-color: #dee2e6;
}

.timeline-content {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}
</style>

<?php
echo $OUTPUT->footer();
