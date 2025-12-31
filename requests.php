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
 * Service requests list page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/academic_dashboard:viewservicerequests', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/requests.php'));
$PAGE->set_title(get_string('servicerequests', 'local_academic_dashboard'));
$PAGE->set_heading(get_string('servicerequests', 'local_academic_dashboard'));
$PAGE->set_pagelayout('standard');

// Add CSS.
$PAGE->requires->css('/local/academic_dashboard/styles.css');

// Get filter parameters.
$status = optional_param('status', '', PARAM_ALPHA);
$requesttype = optional_param('requesttype', '', PARAM_TEXT);
$studentid = optional_param('studentid', 0, PARAM_INT);
$assigneeid = optional_param('assigneeid', 0, PARAM_INT);
$classid = optional_param('classid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

// Build filters array.
$filters = [];
if (!empty($status)) {
    $filters['status'] = $status;
}
if (!empty($requesttype)) {
    $filters['requesttype'] = $requesttype;
}
if ($studentid > 0) {
    $filters['studentid'] = $studentid;
}
if ($assigneeid > 0) {
    $filters['assigneeid'] = $assigneeid;
}
if ($classid > 0) {
    $filters['classid'] = $classid;
}

// Get service request manager.
use local_academic_dashboard\service_request_manager;

// Get requests.
$requests = service_request_manager::get_requests($filters, 'timecreated', 'DESC', $page * $perpage, $perpage);

// Get summary statistics.
$stats = [
    'open' => service_request_manager::get_requests(['status' => 'open']),
    'inprogress' => service_request_manager::get_requests(['status' => 'inprogress']),
    'resolved' => service_request_manager::get_requests(['status' => 'resolved']),
];

// Get classes for filter.
$classes = $DB->get_records('local_acad_classes', [], 'name ASC');

// Get request types.
$requesttypes = $DB->get_fieldset_select('local_acad_service_requests', 'DISTINCT requesttype', '', [], '');

// Get staff members for assignee filter.
$staffrole = $DB->get_record('role', ['shortname' => 'academicmanager']);
$staff = [];
if ($staffrole) {
    $staffusers = get_role_users($staffrole->id, $context);
    foreach ($staffusers as $user) {
        $staff[$user->id] = fullname($user);
    }
}

echo $OUTPUT->header();
?>

<div class="service-requests-page">
    <!-- Summary Statistics -->
    <div class="requests-stats mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card card bg-info text-white">
                    <div class="card-body">
                        <h3><?php echo count($stats['open']); ?></h3>
                        <p><?php echo get_string('statusopen', 'local_academic_dashboard'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-warning text-white">
                    <div class="card-body">
                        <h3><?php echo count($stats['inprogress']); ?></h3>
                        <p><?php echo get_string('statusinprogress', 'local_academic_dashboard'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-success text-white">
                    <div class="card-body">
                        <h3><?php echo count($stats['resolved']); ?></h3>
                        <p><?php echo get_string('statusresolved', 'local_academic_dashboard'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-secondary text-white">
                    <div class="card-body">
                        <h3><?php echo count($stats['open']) + count($stats['inprogress']) + count($stats['resolved']); ?></h3>
                        <p><?php echo get_string('total', 'local_academic_dashboard'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="requests-filters card mb-4">
        <div class="card-body">
            <form method="get" action="" class="form">
                <div class="row">
                    <div class="col-md-2">
                        <label for="status"><?php echo get_string('status', 'local_academic_dashboard'); ?></label>
                        <select name="status" id="status" class="form-control">
                            <option value=""><?php echo get_string('all', 'local_academic_dashboard'); ?></option>
                            <option value="open" <?php echo ($status === 'open') ? 'selected' : ''; ?>>
                                <?php echo get_string('statusopen', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="inprogress" <?php echo ($status === 'inprogress') ? 'selected' : ''; ?>>
                                <?php echo get_string('statusinprogress', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="resolved" <?php echo ($status === 'resolved') ? 'selected' : ''; ?>>
                                <?php echo get_string('statusresolved', 'local_academic_dashboard'); ?>
                            </option>
                            <option value="closed" <?php echo ($status === 'closed') ? 'selected' : ''; ?>>
                                <?php echo get_string('statusclosed', 'local_academic_dashboard'); ?>
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="requesttype"><?php echo get_string('requesttype', 'local_academic_dashboard'); ?></label>
                        <select name="requesttype" id="requesttype" class="form-control">
                            <option value=""><?php echo get_string('all', 'local_academic_dashboard'); ?></option>
                            <?php foreach ($requesttypes as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo ($requesttype === $type) ? 'selected' : ''; ?>>
                                    <?php echo get_string('requesttype_' . $type, 'local_academic_dashboard'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="classid"><?php echo get_string('class', 'local_academic_dashboard'); ?></label>
                        <select name="classid" id="classid" class="form-control">
                            <option value="0"><?php echo get_string('all', 'local_academic_dashboard'); ?></option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class->id; ?>" <?php echo ($classid == $class->id) ? 'selected' : ''; ?>>
                                    <?php echo format_string($class->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="assigneeid"><?php echo get_string('assignee', 'local_academic_dashboard'); ?></label>
                        <select name="assigneeid" id="assigneeid" class="form-control">
                            <option value="0"><?php echo get_string('all', 'local_academic_dashboard'); ?></option>
                            <?php foreach ($staff as $userid => $username): ?>
                                <option value="<?php echo $userid; ?>" <?php echo ($assigneeid == $userid) ? 'selected' : ''; ?>>
                                    <?php echo $username; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <?php echo get_string('filterapply', 'local_academic_dashboard'); ?>
                        </button>
                        <a href="<?php echo new moodle_url('/local/academic_dashboard/requests.php'); ?>" class="btn btn-secondary mr-2">
                            <?php echo get_string('filterclear', 'local_academic_dashboard'); ?>
                        </a>
                        <?php if (has_capability('local/academic_dashboard:manageservicerequests', $context)): ?>
                            <a href="<?php echo new moodle_url('/local/academic_dashboard/request.php'); ?>" class="btn btn-success">
                                <i class="fa fa-plus"></i> <?php echo get_string('newrequest', 'local_academic_dashboard'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="requests-table card">
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <p class="text-center text-muted"><?php echo get_string('norequestsfound', 'local_academic_dashboard'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php echo get_string('requestid', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('student', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('requesttype', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('description', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('status', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('assignee', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('created', 'local_academic_dashboard'); ?></th>
                                <th><?php echo get_string('actions', 'local_academic_dashboard'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request->id; ?></td>
                                    <td>
                                        <a href="<?php echo new moodle_url('/local/academic_dashboard/student.php', ['id' => $request->studentid]); ?>">
                                            <?php echo $request->student_firstname . ' ' . $request->student_lastname; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?php echo get_string('requesttype_' . $request->requesttype, 'local_academic_dashboard'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="request-description" title="<?php echo format_text($request->description, FORMAT_HTML); ?>">
                                            <?php echo shorten_text(format_text($request->description, FORMAT_HTML), 50); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusclass = 'secondary';
                                        switch ($request->status) {
                                            case 'open':
                                                $statusclass = 'info';
                                                break;
                                            case 'inprogress':
                                                $statusclass = 'warning';
                                                break;
                                            case 'resolved':
                                                $statusclass = 'success';
                                                break;
                                            case 'closed':
                                                $statusclass = 'dark';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $statusclass; ?>">
                                            <?php echo get_string('status' . $request->status, 'local_academic_dashboard'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($request->assigneeid): ?>
                                            <?php echo $request->assignee_firstname . ' ' . $request->assignee_lastname; ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo get_string('unassigned', 'local_academic_dashboard'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo userdate($request->timecreated, get_string('strftimedateshort')); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo new moodle_url('/local/academic_dashboard/request.php', ['id' => $request->id]); ?>" 
                                               class="btn btn-sm btn-outline-primary" title="<?php echo get_string('view', 'local_academic_dashboard'); ?>">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <?php if (has_capability('local/academic_dashboard:sendmessages', $context)): ?>
                                                <a href="<?php echo new moodle_url('/message/index.php', ['id' => $request->studentid]); ?>" 
                                                   class="btn btn-sm btn-outline-secondary" title="<?php echo get_string('sendmessage', 'local_academic_dashboard'); ?>">
                                                    <i class="fa fa-envelope"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
                                                <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['studentid' => $request->studentid, 'requestid' => $request->id]); ?>" 
                                                   class="btn btn-sm btn-outline-success" title="<?php echo get_string('createtask', 'local_academic_dashboard'); ?>">
                                                    <i class="fa fa-tasks"></i>
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
                <?php if (count($requests) >= $perpage): ?>
                    <div class="pagination-wrapper mt-3">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 0): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo new moodle_url('/local/academic_dashboard/requests.php', 
                                            array_merge($filters, ['page' => $page - 1])); ?>">
                                            <?php echo get_string('previous', 'local_academic_dashboard'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $page + 1; ?></span>
                                </li>
                                <?php if (count($requests) >= $perpage): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo new moodle_url('/local/academic_dashboard/requests.php', 
                                            array_merge($filters, ['page' => $page + 1])); ?>">
                                            <?php echo get_string('next', 'local_academic_dashboard'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
