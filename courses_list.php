<?php
defined('MOODLE_INTERNAL') || die();

global $DB, $OUTPUT;

// Get all courses except site course
$courses = $DB->get_records_select('course', 'id > 1', null, 'fullname ASC');

?>

<div class="courses-list">
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo get_string('course'); ?></th>
                    <th><?php echo get_string('students', 'local_academic_dashboard'); ?></th>
                    <th><?php echo get_string('resources', 'local_academic_dashboard'); ?></th>
                    <th><?php echo get_string('quizzes', 'local_academic_dashboard'); ?></th>
                    <th><?php echo get_string('assignments', 'local_academic_dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): 
                    $stats = local_academic_dashboard_get_course_stats($course->id);
                ?>
                <tr>
                    <td>
                        <a href="course.php?id=<?php echo $course->id; ?>">
                            <?php echo format_string($course->fullname); ?>
                        </a>
                    </td>
                    <td><?php echo $stats->students; ?></td>
                    <td><?php echo $stats->resources; ?></td>
                    <td><?php echo $stats->quizzes; ?></td>
                    <td><?php echo $stats->assignments; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
