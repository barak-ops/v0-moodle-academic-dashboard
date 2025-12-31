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
 * Students listing page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/academic_dashboard:viewstudentcard', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/students.php'));
$PAGE->set_title(get_string('nav_students', 'local_academic_dashboard'));
$PAGE->set_heading(get_string('nav_students', 'local_academic_dashboard'));
$PAGE->set_pagelayout('standard');

// Add CSS.
$PAGE->requires->css('/local/academic_dashboard/styles.css');

// Get filter parameters.
$search = optional_param('search', '', PARAM_TEXT);
$classid = optional_param('classid', 0, PARAM_INT);
$atrisk = optional_param('atrisk', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

// Build query to get students.
$sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.lastaccess,
               COUNT(DISTINCT t.id) as opentasks,
               COUNT(DISTINCT sr.id) as openrequests
        FROM {user} u
        WHERE u.deleted = 0 AND u.suspended = 0";

$params = [];

if (!empty($search)) {
    $sql .= " AND (" . $DB->sql_like('u.firstname', ':search1', false) . 
            " OR " . $DB->sql_like('u.lastname', ':search2', false) . 
            " OR " . $DB->sql_like('u.email', ':search3', false) . ")";
    $searchparam = '%' . $DB->sql_like_escape($search) . '%';
    $params['search1'] = $searchparam;
    $params['search2'] = $searchparam;
    $params['search3'] = $searchparam;
}

if ($classid > 0) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM {local_acad_class_members} cm 
        WHERE cm.userid = u.id AND cm.classid = :classid
    )";
    $params['classid'] = $classid;
}

$sql .= " LEFT JOIN {local_acad_tasks} t ON t.entitytype = 'student' AND t.entityid = u.id AND t.status != 'completed'
          LEFT JOIN {local_acad_service_requests} sr ON sr.studentid = u.id AND sr.status = 'open'
          GROUP BY u.id, u.firstname, u.lastname, u.email, u.lastaccess
          ORDER BY u.lastname ASC, u.firstname ASC";

$students = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Get classes for filter.
$classes = $DB->get_records('local_acad_classes', [], 'name ASC');

echo $OUTPUT->header();
?>

<div class="academic-students-page">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2><?php echo get_string('nav_students', 'local_academic_dashboard'); ?></h2>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="students-filters card mb-4">
        <div class="card-body">
            <form method="get" action="" class="form-inline">
                <div class="row w-100">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control w-100" 
                               placeholder="<?php echo get_string('searchstudents', 'local_academic_dashboard'); ?>" 
                               value="<?php echo s($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="classid" class="form-control w-100">
                            <option value="0"><?php echo get_string('allclasses', 'local_academic_dashboard'); ?></option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class->id; ?>" <?php echo ($classid == $class->id) ? 'selected' : ''; ?>>
                                    <?php echo format_string($class->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-search"></i> <?php echo get_string('search'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="students-list card">
        <div class="card-body">
            <?php if (empty($students)): ?>
                <div class="alert alert-info">
                    <?php echo get_string('nostudents', 'local_academic_dashboard'); ?>
                </div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo get_string('name'); ?></th>
                            <th><?php echo get_string('email'); ?></th>
                            <th><?php echo get_string('lastaccess'); ?></th>
                            <th><?php echo get_string('opentasks', 'local_academic_dashboard'); ?></th>
                            <th><?php echo get_string('openrequests', 'local_academic_dashboard'); ?></th>
                            <th><?php echo get_string('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <strong><?php echo fullname($student); ?></strong>
                                </td>
                                <td><?php echo $student->email; ?></td>
                                <td>
                                    <?php if ($student->lastaccess): ?>
                                        <?php echo userdate($student->lastaccess, get_string('strftimedatetime')); ?>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo get_string('never'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($student->opentasks > 0): ?>
                                        <span class="badge badge-warning"><?php echo $student->opentasks; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($student->openrequests > 0): ?>
                                        <span class="badge badge-danger"><?php echo $student->openrequests; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo new moodle_url('/local/academic_dashboard/student.php', ['id' => $student->id]); ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fa fa-eye"></i> <?php echo get_string('view'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
