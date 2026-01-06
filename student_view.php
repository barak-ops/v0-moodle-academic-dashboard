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
            <div class="col-md-3">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #28a745; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-check-circle fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $avgprogress; ?>%</h4>
                        <small class="text-muted"><?php echo get_string('progress', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #0f6cbf; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-calendar-check-o fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $avgattendance; ?>%</h4>
                        <small class="text-muted"><?php echo get_string('attendance', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #007bff; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-book fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $totalcourses; ?></h4>
                        <small class="text-muted"><?php echo get_string('courses'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
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
        <form method="post" id="groupForm">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <input type="hidden" name="groupchanges" id="groupChanges">
            
            <?php 
            $courseIndex = 0;
            $courseCount = count($courses);
            foreach ($courses as $course): 
                $courseIndex++;
                $isLast = ($courseIndex === $courseCount);
                $progress = local_academic_dashboard_get_student_progress($userid, $course->id);
                $attendance = local_academic_dashboard_get_student_attendance($userid, $course->id);
                $groups = groups_get_all_groups($course->id);
                $usergroups = groups_get_user_groups($course->id, $userid);
                $currentgroupid = !empty($usergroups[0]) ? $usergroups[0][0] : 0;
            ?>
            <div class="card-body d-flex align-items-center" style="border-radius: 0; <?php echo $isLast ? '' : 'border-bottom: 1px solid #dee2e6;'; ?>">
                <div class="flex-grow-1">
                    <a href="course.php?id=<?php echo $course->id; ?>&fromuser=<?php echo $userid; ?>">
                        <?php echo format_string($course->fullname); ?>
                    </a>
                </div>
                
                <?php if ($progress !== null): ?>
                <div class="mr-3 d-flex align-items-center">
                    <div class="progress mr-2" style="width: 100px; height: 20px;">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <small style="white-space: nowrap;"><?php echo get_string('progress', 'local_academic_dashboard'); ?>: <?php echo round($progress); ?>%</small>
                </div>
                <?php endif; ?>
                
                <?php if ($attendance !== null): ?>
                <div class="mr-3 d-flex align-items-center">
                    <div class="progress mr-2" style="width: 100px; height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $attendance; ?>%"></div>
                    </div>
                    <small style="white-space: nowrap;"><?php echo get_string('attendance', 'local_academic_dashboard'); ?>: <?php echo $attendance; ?>%</small>
                </div>
                <?php endif; ?>
                
                <?php if (count($groups) > 0): ?>
                <select class="form-control group-selector" data-courseid="<?php echo $course->id; ?>" style="width: 150px;">
                    <option value="0"><?php echo get_string('no_group', 'local_academic_dashboard'); ?></option>
                    <?php foreach ($groups as $group): ?>
                    <option value="<?php echo $group->id; ?>" <?php echo $group->id == $currentgroupid ? 'selected' : ''; ?>>
                        <?php echo format_string($group->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <div class="card-body" style="background: #f7f7f7; padding-top:15px; border-radius: 0;">
                <button type="submit" class="btn btn-primary" id="saveBtn">
                    <?php echo get_string('save', 'local_academic_dashboard'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const groupChanges = [];

const originalGroups = {};
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.style.opacity = '0.5';
    saveBtn.style.cursor = 'not-allowed';
    
    document.querySelectorAll('.group-selector').forEach(select => {
        const courseid = select.dataset.courseid;
        originalGroups[courseid] = select.value;
    });
});

document.querySelectorAll('.group-selector').forEach(select => {
    select.addEventListener('change', function() {
        const courseid = this.dataset.courseid;
        const groupid = this.value;
        
        const index = groupChanges.findIndex(c => c.courseid === courseid);
        if (index > -1) {
            groupChanges.splice(index, 1);
        }
        
        // Only add to changes if different from original
        if (groupid !== originalGroups[courseid]) {
            groupChanges.push({courseid: courseid, groupid: groupid});
        }
        
        const saveBtn = document.getElementById('saveBtn');
        if (groupChanges.length === 0) {
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.5';
            saveBtn.style.cursor = 'not-allowed';
        } else {
            saveBtn.disabled = false;
            saveBtn.style.opacity = '1';
            saveBtn.style.cursor = 'pointer';
        }
    });
});

document.getElementById('groupForm').addEventListener('submit', function(e) {
    document.getElementById('groupChanges').value = JSON.stringify(groupChanges);
});

function openUserEmail(userid, courseid) {
    const url = '<?php echo new moodle_url('/local/academic_dashboard/mail_compose.php'); ?>?userid=' + userid + '&courseid=' + courseid;
    window.open(url, 'EmailComposer', 'width=900,height=700,scrollbars=yes,resizable=yes');
}
</script>
