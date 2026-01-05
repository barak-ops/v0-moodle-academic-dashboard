<?php
defined('MOODLE_INTERNAL') || die();

// Get teacher's courses
$sql = "SELECT DISTINCT c.*
        FROM {course} c
        JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
        JOIN {role_assignments} ra ON ra.contextid = ctx.id
        JOIN {role} r ON r.id = ra.roleid
        WHERE ra.userid = ? AND c.id > 1 AND r.shortname IN ('editingteacher', 'teacher')
        ORDER BY c.fullname";

$courses = $DB->get_records_sql($sql, [$userid]);

// Calculate statistics
$totalcourses = count($courses);
$totalquizzes = 0;
$totalassignments = 0;
$totaloverdue = 0;

foreach ($courses as $course) {
    $stats = local_academic_dashboard_get_course_stats($course->id);
    $totalquizzes += $stats->quizzes;
    $totalassignments += $stats->assignments;
    
    // Count students with overdue tasks
    $context = context_course::instance($course->id);
    $students = get_enrolled_users($context, 'mod/assignment:submit');
    
    $now = time();
    foreach ($students as $student) {
        $sql = "SELECT COUNT(*) as count
                FROM {assign} a
                LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = ?
                WHERE a.course = ? AND a.duedate > 0 AND a.duedate < ? AND (s.id IS NULL OR s.status != 'submitted')";
        $overdue = $DB->get_field_sql($sql, [$student->id, $course->id, $now]);
        if ($overdue > 0) {
            $totaloverdue++;
            break; // Count each student only once
        }
    }
}

?>

<div class="teacher-details">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>
            <?php echo fullname($user); ?>
            <a href="#" class="btn btn-sm btn-primary ml-2" onclick="openEmailModal('<?php echo $user->email; ?>'); return false;">
                <i class="fa fa-envelope"></i>
            </a>
        </h2>
        <a href="<?php echo $fromcourse ? 'course.php?id=' . $fromcourse : 'index.php'; ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?php echo get_string('back', 'local_academic_dashboard'); ?>
        </a>
    </div>
    
    <p><strong><?php echo get_string('email'); ?>:</strong> 
        <a href="#" onclick="openEmailModal('<?php echo $user->email; ?>'); return false;">
            <?php echo $user->email; ?>
        </a>
    </p>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-book fa-2x"></i>
                    <h4><?php echo $totalcourses; ?></h4>
                    <small><?php echo get_string('total_courses', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-tasks fa-2x"></i>
                    <h4><?php echo $totalquizzes + $totalassignments; ?></h4>
                    <small><?php echo get_string('quizzes', 'local_academic_dashboard') . ' + ' . get_string('assignments', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                    <h4><?php echo $totaloverdue; ?></h4>
                    <small><?php echo get_string('overdue_tasks', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Courses -->
    <h4><?php echo get_string('courses'); ?></h4>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo get_string('course'); ?></th>
                    <th><?php echo get_string('students', 'local_academic_dashboard'); ?></th>
                    <th><?php echo get_string('overdue_tasks', 'local_academic_dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): 
                    $stats = local_academic_dashboard_get_course_stats($course->id);
                    
                    // Count students with overdue
                    $context = context_course::instance($course->id);
                    $students = get_enrolled_users($context, 'mod/assignment:submit');
                    $overduestudents = 0;
                    $now = time();
                    
                    foreach ($students as $student) {
                        $sql = "SELECT COUNT(*) as count
                                FROM {assign} a
                                LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = ?
                                WHERE a.course = ? AND a.duedate > 0 AND a.duedate < ? AND (s.id IS NULL OR s.status != 'submitted')";
                        $overdue = $DB->get_field_sql($sql, [$student->id, $course->id, $now]);
                        if ($overdue > 0) {
                            $overduestudents++;
                        }
                    }
                ?>
                <tr>
                    <td>
                        <a href="course.php?id=<?php echo $course->id; ?>&fromuser=<?php echo $userid; ?>">
                            <?php echo format_string($course->fullname); ?>
                        </a>
                    </td>
                    <td><?php echo count($students); ?></td>
                    <td>
                        <?php if ($overduestudents > 0): ?>
                        <span class="badge badge-danger"><?php echo $overduestudents; ?></span>
                        <?php else: ?>
                        0
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function openEmailModal(email) {
    alert('Send email to: ' + email);
}
</script>
