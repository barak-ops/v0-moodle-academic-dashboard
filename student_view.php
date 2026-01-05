<?php
defined('MOODLE_INTERNAL') || die();

// Get student's courses
$courses = enrol_get_users_courses($userid, true);

// Calculate overall statistics
$totalcourses = count($courses);
$totalprogress = 0;
$totalattendance = 0;
$totaloverdue = 0;
$coursesWithProgress = 0;
$coursesWithAttendance = 0;

foreach ($courses as $course) {
    $progress = local_academic_dashboard_get_student_progress($userid, $course->id);
    if ($progress !== null) {
        $totalprogress += $progress;
        $coursesWithProgress++;
    }
    
    $attendance = local_academic_dashboard_get_student_attendance($userid, $course->id);
    if ($attendance !== null) {
        $totalattendance += $attendance;
        $coursesWithAttendance++;
    }
    
    // Count overdue assignments
    $now = time();
    $sql = "SELECT COUNT(*) as count
            FROM {assign} a
            LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = ?
            WHERE a.course = ? AND a.duedate > 0 AND a.duedate < ? AND (s.id IS NULL OR s.status != 'submitted')";
    $overdueassigns = $DB->get_field_sql($sql, [$userid, $course->id, $now]);
    $totaloverdue += $overdueassigns;
}

$avgprogress = $coursesWithProgress > 0 ? round($totalprogress / $coursesWithProgress) : 0;
$avgattendance = $coursesWithAttendance > 0 ? round($totalattendance / $coursesWithAttendance) : 0;

?>

<div class="student-details">
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
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5><?php echo get_string('progress', 'local_academic_dashboard'); ?></h5>
                    <div class="progress" style="height: 50px;">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $avgprogress; ?>%; font-size: 1.2em; line-height: 50px;">
                            <?php echo $avgprogress; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5><?php echo get_string('attendance', 'local_academic_dashboard'); ?></h5>
                    <div class="progress" style="height: 50px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $avgattendance; ?>%; font-size: 1.2em; line-height: 50px;">
                            <?php echo $avgattendance; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-book fa-2x"></i>
                    <h4><?php echo $totalcourses; ?></h4>
                    <small><?php echo get_string('courses'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
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
    <form method="post" id="groupForm">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        <input type="hidden" name="groupchanges" id="groupChanges">
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo get_string('course'); ?></th>
                        <th><?php echo get_string('progress', 'local_academic_dashboard'); ?></th>
                        <th><?php echo get_string('attendance', 'local_academic_dashboard'); ?></th>
                        <th><?php echo get_string('group', 'local_academic_dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): 
                        $progress = local_academic_dashboard_get_student_progress($userid, $course->id);
                        $attendance = local_academic_dashboard_get_student_attendance($userid, $course->id);
                        $groups = groups_get_all_groups($course->id);
                        $usergroups = groups_get_user_groups($course->id, $userid);
                        $currentgroupid = !empty($usergroups[0]) ? $usergroups[0][0] : 0;
                    ?>
                    <tr>
                        <td>
                            <a href="course.php?id=<?php echo $course->id; ?>&fromuser=<?php echo $userid; ?>">
                                <?php echo format_string($course->fullname); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($progress !== null): ?>
                            <div class="progress" style="width: 150px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%">
                                    <?php echo round($progress); ?>%
                                </div>
                            </div>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($attendance !== null): ?>
                            <div class="progress" style="width: 150px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $attendance; ?>%">
                                    <?php echo $attendance; ?>%
                                </div>
                            </div>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (count($groups) > 0): ?>
                            <select class="form-control group-selector" data-courseid="<?php echo $course->id; ?>">
                                <option value="0"><?php echo get_string('no_group', 'local_academic_dashboard'); ?></option>
                                <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group->id; ?>" <?php echo $group->id == $currentgroupid ? 'selected' : ''; ?>>
                                    <?php echo format_string($group->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
            <?php echo get_string('save', 'local_academic_dashboard'); ?>
        </button>
    </form>
</div>

<script>
const groupChanges = [];

document.querySelectorAll('.group-selector').forEach(select => {
    select.addEventListener('change', function() {
        const courseid = this.dataset.courseid;
        const groupid = this.value;
        
        // Remove existing change for this course
        const index = groupChanges.findIndex(c => c.courseid === courseid);
        if (index > -1) {
            groupChanges.splice(index, 1);
        }
        
        // Add new change
        groupChanges.push({courseid: courseid, groupid: groupid});
        
        document.getElementById('saveBtn').disabled = false;
    });
});

document.getElementById('groupForm').addEventListener('submit', function(e) {
    document.getElementById('groupChanges').value = JSON.stringify(groupChanges);
});

function openEmailModal(email) {
    alert('Send email to: ' + email);
}
</script>
