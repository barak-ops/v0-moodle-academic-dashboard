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
    
    <div id="cards-container">
   ` <div class="row mb-4">
        <!-- Replaced progress bar with pie chart -->
        <div class="col-md-3">
            <div class="card">
                <div class="icon-card-body d-flex align-items-center">
                    <!-- Smaller pie chart to match icon size (64x64) -->
                    <canvas id="progressChart" width="64" height="64" class="mr-3"></canvas>
                    <div>
                        <h3 class="mb-0"><?php echo $avgprogress; ?>%</h3>
                        <small class="text-muted"><?php echo get_string('progress', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Replaced progress bar with pie chart -->
        <div class="col-md-3">
            <div class="card">
                <div class="icon-card-body d-flex align-items-center">
                    <!-- Smaller pie chart to match icon size (64x64) -->
                    <canvas id="attendanceChart" width="64" height="64" class="mr-3"></canvas>
                    <div>
                        <h3 class="mb-0"><?php echo $avgattendance; ?>%</h3>
                        <small class="text-muted"><?php echo get_string('attendance', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="icon-card-body d-flex align-items-center">
                    <i class="fa fa-book fa-3x text-primary mr-3"></i>
                    <div>
                        <h3 class="mb-0"><?php echo $totalcourses; ?></h3>
                        <small class="text-muted"><?php echo get_string('courses'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="icon-card-body d-flex align-items-center">
                    <i class="fa fa-exclamation-triangle fa-3x text-danger mr-3"></i>
                    <div>
                        <h3 class="mb-0"><?php echo $totaloverdue; ?></h3>
                        <small class="text-muted"><?php echo get_string('overdue_tasks', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
  `</div  
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
                    <?php 
                    $courseIndex = 0;
                    foreach ($courses as $course): 
                        $progress = local_academic_dashboard_get_student_progress($userid, $course->id);
                        $attendance = local_academic_dashboard_get_student_attendance($userid, $course->id);
                        $groups = groups_get_all_groups($course->id);
                        $usergroups = groups_get_user_groups($course->id, $userid);
                        $currentgroupid = !empty($usergroups[0]) ? $usergroups[0][0] : 0;
                        $courseIndex++;
                    ?>
                    <tr>
                        <td>
                            <a href="course.php?id=<?php echo $course->id; ?>&fromuser=<?php echo $userid; ?>">
                                <?php echo format_string($course->fullname); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($progress !== null): ?>
                            <!-- Replace progress bar with small pie chart -->
                            <div class="d-flex align-items-center">
                                <canvas id="courseProgress<?php echo $courseIndex; ?>" width="40" height="40" class="mr-2"></canvas>
                                <span><?php echo round($progress); ?>%</span>
                            </div>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($attendance !== null): ?>
                            <!-- Replace progress bar with small pie chart -->
                            <div class="d-flex align-items-center">
                                <canvas id="courseAttendance<?php echo $courseIndex; ?>" width="40" height="40" class="mr-2"></canvas>
                                <span><?php echo $attendance; ?>%</span>
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

<!-- Added Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const progressCtx = document.getElementById('progressChart').getContext('2d');
new Chart(progressCtx, {
    type: 'doughnut',
    data: {
        labels: ['<?php echo get_string('completed', 'local_academic_dashboard'); ?>', '<?php echo get_string('remaining', 'local_academic_dashboard'); ?>'],
        datasets: [{
            data: [<?php echo $avgprogress; ?>, <?php echo 100 - $avgprogress; ?>],
            backgroundColor: ['#28a745', '#dee2e6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + '%';
                    }
                }
            }
        },
        cutout: '65%'
    }
});

const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
new Chart(attendanceCtx, {
    type: 'doughnut',
    data: {
        labels: ['<?php echo get_string('present', 'local_academic_dashboard'); ?>', '<?php echo get_string('absent', 'local_academic_dashboard'); ?>'],
        datasets: [{
            data: [<?php echo $avgattendance; ?>, <?php echo 100 - $avgattendance; ?>],
            backgroundColor: ['#0f6cbf', '#dee2e6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + '%';
                    }
                }
            }
        },
        cutout: '65%'
    }
});

<?php 
$courseIndex = 0;
foreach ($courses as $course):
    $progress = local_academic_dashboard_get_student_progress($userid, $course->id);
    $attendance = local_academic_dashboard_get_student_attendance($userid, $course->id);
    $courseIndex++;
    
    if ($progress !== null):
?>
const courseProgressCtx<?php echo $courseIndex; ?> = document.getElementById('courseProgress<?php echo $courseIndex; ?>').getContext('2d');
new Chart(courseProgressCtx<?php echo $courseIndex; ?>, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?php echo round($progress); ?>, <?php echo 100 - round($progress); ?>],
            backgroundColor: ['#28a745', '#dee2e6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: false,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        },
        cutout: '60%'
    }
});
<?php 
    endif;
    
    if ($attendance !== null):
?>
const courseAttendanceCtx<?php echo $courseIndex; ?> = document.getElementById('courseAttendance<?php echo $courseIndex; ?>').getContext('2d');
new Chart(courseAttendanceCtx<?php echo $courseIndex; ?>, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?php echo $attendance; ?>, <?php echo 100 - $attendance; ?>],
            backgroundColor: ['#0f6cbf', '#dee2e6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: false,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        },
        cutout: '60%'
    }
});
<?php 
    endif;
endforeach; 
?>

const groupChanges = [];

document.querySelectorAll('.group-selector').forEach(select => {
    select.addEventListener('change', function() {
        const courseid = this.dataset.courseid;
        const groupid = this.value;
        
        const index = groupChanges.findIndex(c => c.courseid === courseid);
        if (index > -1) {
            groupChanges.splice(index, 1);
        }
        
        groupChanges.push({courseid: courseid, groupid: groupid});
        
        document.getElementById('saveBtn').disabled = false;
    });
});

document.getElementById('groupForm').addEventListener('submit', function(e) {
    document.getElementById('groupChanges').value = JSON.stringify(groupChanges);
});

function openEmailModal(email) {
    const url = '<?php echo new moodle_url('/local/academic_dashboard/mail_compose.php'); ?>?to=' + encodeURIComponent(email) + '&courseid=<?php echo $fromcourse ? $fromcourse : 0; ?>';
    window.open(url, 'EmailComposer', 'width=900,height=700,scrollbars=yes,resizable=yes');
}
</script>
