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
 * Classes management page.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/academic_dashboard:viewclasscard', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/academic_dashboard/classes.php'));
$PAGE->set_title(get_string('nav_classes', 'local_academic_dashboard'));
$PAGE->set_heading(get_string('nav_classes', 'local_academic_dashboard'));
$PAGE->set_pagelayout('standard');

// Add CSS.
$PAGE->requires->css('/local/academic_dashboard/styles.css');

// Get filter parameters.
$search = optional_param('search', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

// Get classes from database.
$sql = "SELECT c.*, 
               COUNT(DISTINCT cm.userid) as studentcount,
               COUNT(DISTINCT t.id) as opentasks,
               COUNT(DISTINCT sr.id) as openrequests
        FROM {local_acad_classes} c
        LEFT JOIN {local_acad_class_members} cm ON cm.classid = c.id
        LEFT JOIN {local_acad_tasks} t ON (
            (t.entitytype = 'class' AND t.entityid = c.id AND t.status != 'completed')
            OR (t.entitytype = 'student' AND t.entityid = cm.userid AND t.status != 'completed')
        )
        LEFT JOIN {local_acad_service_requests} sr ON sr.studentid = cm.userid AND sr.status = 'open'
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND " . $DB->sql_like('c.name', ':search', false);
    $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
}

$sql .= " GROUP BY c.id, c.name, c.description, c.timecreated, c.timemodified
          ORDER BY c.name ASC";

$totalcount = $DB->count_records_sql("SELECT COUNT(DISTINCT c.id) FROM {local_acad_classes} c WHERE 1=1" . 
    (!empty($search) ? " AND " . $DB->sql_like('c.name', ':search', false) : ""), 
    $params
);

$classes = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

echo $OUTPUT->header();
?>

<div class="academic-classes-page">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2><?php echo get_string('nav_classes', 'local_academic_dashboard'); ?></h2>
            <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
                <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php'); ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> <?php echo get_string('newtask', 'local_academic_dashboard'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="classes-filters card mb-4">
        <div class="card-body">
            <form method="get" action="" class="form-inline">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="<?php echo get_string('searchclasses', 'local_academic_dashboard'); ?>" 
                           value="<?php echo s($search); ?>">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i> <?php echo get_string('search'); ?>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="<?php echo new moodle_url('/local/academic_dashboard/classes.php'); ?>" class="btn btn-secondary">
                                <i class="fa fa-times"></i> <?php echo get_string('clear'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Classes Grid -->
    <div class="classes-grid">
        <?php if (empty($classes)): ?>
            <div class="alert alert-info">
                <?php echo get_string('noclasses', 'local_academic_dashboard'); ?>
            </div>
        <?php else: ?>
            <?php foreach ($classes as $class): ?>
                <div class="class-card card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fa fa-chalkboard-teacher"></i>
                            <?php echo format_string($class->name); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($class->description)): ?>
                            <p class="class-description">
                                <?php echo format_text($class->description, FORMAT_HTML); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Class Statistics -->
                        <div class="class-stats row mt-3">
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <i class="fa fa-users text-primary"></i>
                                    <span class="stat-value"><?php echo $class->studentcount; ?></span>
                                    <span class="stat-label"><?php echo get_string('students', 'local_academic_dashboard'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <i class="fa fa-tasks text-warning"></i>
                                    <span class="stat-value"><?php echo $class->opentasks; ?></span>
                                    <span class="stat-label"><?php echo get_string('opentasks', 'local_academic_dashboard'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <i class="fa fa-exclamation-circle text-danger"></i>
                                    <span class="stat-value"><?php echo $class->openrequests; ?></span>
                                    <span class="stat-label"><?php echo get_string('openrequests', 'local_academic_dashboard'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="class-actions mt-3">
                            <a href="<?php echo new moodle_url('/local/academic_dashboard/class.php', ['id' => $class->id]); ?>" 
                               class="btn btn-primary">
                                <i class="fa fa-eye"></i> <?php echo get_string('viewcard', 'local_academic_dashboard'); ?>
                            </a>
                            <?php if (has_capability('local/academic_dashboard:sendmessages', $context)): ?>
                                <a href="<?php echo new moodle_url('/local/academic_dashboard/message.php', ['classid' => $class->id]); ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fa fa-envelope"></i> <?php echo get_string('sendmessage', 'local_academic_dashboard'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (has_capability('local/academic_dashboard:managetasks', $context)): ?>
                                <a href="<?php echo new moodle_url('/local/academic_dashboard/task.php', ['classid' => $class->id]); ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fa fa-plus"></i> <?php echo get_string('newtask', 'local_academic_dashboard'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small>
                            <?php echo get_string('created', 'local_academic_dashboard'); ?>: 
                            <?php echo userdate($class->timecreated, get_string('strftimedatetime')); ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($totalcount > $perpage): ?>
                <div class="pagination-wrapper">
                    <?php echo $OUTPUT->paging_bar($totalcount, $page, $perpage, 
                        new moodle_url('/local/academic_dashboard/classes.php', ['search' => $search])); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
echo $OUTPUT->footer();
