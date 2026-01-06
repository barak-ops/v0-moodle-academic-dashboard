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
        <h2 class="d-flex align-items-center">
            <?php echo fullname($user); ?>
            <!-- Pass userid instead of email address -->
            <a href="#" class="ml-2" onclick="openUserEmail(<?php echo $userid; ?>, <?php echo $fromcourse ? $fromcourse : 0; ?>); return false;" style="font-size: 0.6em; text-decoration: none;">
                <i class="fa fa-envelope"></i>
            </a>
        </h2>
        <a href="<?php echo $fromcourse ? 'course.php?id=' . $fromcourse : 'index.php'; ?>" style="font-size: 1.5em; text-decoration: none; color: #333;">
            <i class="fa fa-arrow-left"></i>
        </a>
    </div>
    
    <div id="cards-container">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #007bff; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-book fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $totalcourses; ?></h4>
                        <small class="text-muted"><?php echo get_string('total_courses', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #28a745; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-tasks fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $totalquizzes + $totalassignments; ?></h4>
                        <small class="text-muted"><?php echo get_string('quizzes', 'local_academic_dashboard') . ' + ' . get_string('assignments', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #dc3545; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-exclamation-triangle fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $totaloverdue; ?></h4>
                        <small class="text-muted"><?php echo get_string('overdue_tasks', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-3" style="border-radius: 7px;">
        <div class="card-header" style="padding: 15px">
            <h4 class="mb-0"><?php echo get_string('courses'); ?></h4>
        </div>
        <hr class="m-0">
        <?php 
        $courseCount = count($courses);
        $courseIndex = 0;
        foreach ($courses as $course): 
            $courseIndex++;
            $isLast = ($courseIndex === $courseCount);
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
        <div class="card-body d-flex justify-content-between align-items-center" style="border-radius: 0; <?php echo $isLast ? '' : 'border-bottom: 1px solid #dee2e6;'; ?>">
            <a href="course.php?id=<?php echo $course->id; ?>&fromuser=<?php echo $userid; ?>">
                <?php echo format_string($course->fullname); ?>
            </a>
            <div>
                <span class="mr-3"><?php echo get_string('students', 'local_academic_dashboard'); ?>: <?php echo count($students); ?></span>
                <?php if ($overduestudents > 0): ?>
                <span class="badge badge-danger"><?php echo $overduestudents; ?> <?php echo get_string('overdue_tasks', 'local_academic_dashboard'); ?></span>
                <?php else: ?>
                <span class="text-muted">0 <?php echo get_string('overdue_tasks', 'local_academic_dashboard'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function openUserEmail(userid, courseid) {
    const url = '<?php echo new moodle_url('/local/academic_dashboard/mail_compose.php'); ?>?userid=' + userid + '&courseid=' + courseid;
    window.open(url, 'EmailComposer', 'width=900,height=700,scrollbars=yes,resizable=yes');
}
</script>
