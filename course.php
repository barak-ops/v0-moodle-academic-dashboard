<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();
require_capability('local/academic_dashboard:view', context_system::instance());

$courseid = required_param('id', PARAM_INT);
$fromuser = optional_param('fromuser', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

$PAGE->set_url(new moodle_url('/local/academic_dashboard/course.php', ['id' => $courseid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(get_string('dashboard_title', 'local_academic_dashboard'));

// Handle group changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $groupdata = optional_param('groupdata', '', PARAM_RAW);
    if ($groupdata) {
        $changes = json_decode($groupdata, true);
        foreach ($changes as $change) {
            $userid = $change['userid'];
            $newgroupid = $change['groupid'];
            
            // Remove from all groups in course
            $currentgroups = groups_get_user_groups($courseid, $userid);
            foreach ($currentgroups[0] as $gid) {
                groups_remove_member($gid, $userid);
            }
            
            // Add to new group
            if ($newgroupid > 0) {
                groups_add_member($newgroupid, $userid);
            }
        }
        redirect($PAGE->url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$stats = local_academic_dashboard_get_course_stats($courseid);

echo $OUTPUT->header();

?>

<div class="course-details">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="d-flex align-items-center">
            <?php echo format_string($course->fullname); ?>
            <a href="#" class="ml-2" onclick="openCourseEmail(<?php echo $courseid; ?>); return false;" style="font-size: 0.6em; text-decoration: none;">
                <i class="fa fa-envelope"></i>
            </a>
        </h2>
        <!-- Reordered navigation icons - home first, then back arrow with RTL/LTR support -->
        <div>
            <a href="index.php" style="font-size: 1.5em; text-decoration: none; color: #333; margin-<?php echo right_to_left() ? 'left' : 'right'; ?>: 10px;">
                <i class="fa fa-home"></i>
            </a>
            <a href="<?php echo $fromuser ? 'user.php?id=' . $fromuser : 'index.php'; ?>" style="font-size: 1.5em; text-decoration: none; color: #333;">
                <i class="fa fa-arrow-<?php echo right_to_left() ? 'left' : 'right'; ?>"></i>
            </a>
        </div>
    </div>
    
    <div id="cards-container">
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #6c757d; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-file fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $stats->resources; ?></h4>
                        <small class="text-muted"><?php echo get_string('resources', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #17a2b8; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-question-circle fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $stats->quizzes; ?></h4>
                        <small class="text-muted"><?php echo get_string('quizzes', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #28a745; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-tasks fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $stats->assignments; ?></h4>
                        <small class="text-muted"><?php echo get_string('assignments', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #dc3545; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-exclamation-triangle fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $stats->overdue; ?></h4>
                        <small class="text-muted"><?php echo get_string('overdue', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #007bff; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-clock-o fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $stats->remaining; ?></h4>
                        <small class="text-muted"><?php echo get_string('remaining', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card" style="border-radius: 7px;">
                    <div class="icon-card-body text-center">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #6c757d; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fa fa-users fa-2x" style="color: white;"></i>
                        </div>
                        <h4 class="mb-0"><?php echo $stats->students; ?></h4>
                        <small class="text-muted"><?php echo get_string('students', 'local_academic_dashboard'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header card-header-color" style="padding: 15px">
            <h4 class="mb-0"><?php echo get_string('teacher', 'local_academic_dashboard'); ?></h4>
        </div>
        <hr class="m-0">
        <?php
        $teachers = get_enrolled_users($context, 'moodle/course:update');
        $teacherCount = count($teachers);
        $teacherIndex = 0;
        foreach ($teachers as $teacher):
            $teacherIndex++;
            $isLast = ($teacherIndex === $teacherCount);
        ?>
        <div class="card-body teacher-card d-flex justify-content-between align-items-center" style="border-radius: 0; <?php echo $isLast ? '' : 'border-bottom: 1px solid #dee2e6;'; ?>">
            <a href="user.php?id=<?php echo $teacher->id; ?>&fromcourse=<?php echo $courseid; ?>">
                <?php echo fullname($teacher); ?>
            </a>
            <a href="#" onclick="openEmailModal('<?php echo $teacher->email; ?>'); return false;">
                <?php echo $teacher->email; ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="card">
        <div class="card-header card-header-color">
            <h4 class="mb-0"><?php echo get_string('students', 'local_academic_dashboard'); ?></h4>
        </div>
        <hr class="m-0">
        <form method="post" id="groupForm">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <input type="hidden" name="groupdata" id="groupData">
            
            <?php
            $groups = groups_get_all_groups($courseid);
            
            $sql = "SELECT DISTINCT u.*
                    FROM {user} u
                    JOIN {user_enrolments} ue ON ue.userid = u.id
                    JOIN {enrol} e ON e.id = ue.enrolid
                    WHERE e.courseid = ? AND u.deleted = 0 AND ue.status = 0
                    ORDER BY u.lastname, u.firstname";
            
            $students = $DB->get_records_sql($sql, [$courseid]);
            
            if (count($groups) > 0) {
                $groupedstudents = [];
                $nogroupstudents = [];
                
                foreach ($students as $student) {
                    $usergroups = groups_get_user_groups($courseid, $student->id);
                    if (empty($usergroups[0])) {
                        $nogroupstudents[] = $student;
                    } else {
                        foreach ($usergroups[0] as $gid) {
                            if (!isset($groupedstudents[$gid])) {
                                $groupedstudents[$gid] = [];
                            }
                            $groupedstudents[$gid][] = $student;
                        }
                    }
                }
                
                foreach ($groups as $group) {
                    echo '<div class="card-header intent bg-light"><h5 class="mb-0">' . format_string($group->name) . '</h5></div>';
                    echo '<hr class="m-0">';
                    echo '<div class="student-group" data-groupid="' . $group->id . '">';
                    if (isset($groupedstudents[$group->id])) {
                        $groupStudentCount = count($groupedstudents[$group->id]);
                        $groupStudentIndex = 0;
                        foreach ($groupedstudents[$group->id] as $student) {
                            $groupStudentIndex++;
                            $isLastInGroup = ($groupStudentIndex === $groupStudentCount);
                            display_student_row($student, $courseid, $isLastInGroup);
                        }
                    }
                    echo '</div>';
                }
                
                if (count($nogroupstudents) > 0) {
                    echo '<div class="card-header bg-light"><h5 class="mb-0">' . get_string('no_group', 'local_academic_dashboard') . '</h5></div>';
                    echo '<hr class="m-0">';
                    echo '<div class="student-group" data-groupid="0">';
                    $noGroupCount = count($nogroupstudents);
                    $noGroupIndex = 0;
                    foreach ($nogroupstudents as $student) {
                        $noGroupIndex++;
                        $isLastInGroup = ($noGroupIndex === $noGroupCount);
                        display_student_row($student, $courseid, $isLastInGroup);
                    }
                    echo '</div>';
                }
            } else {
                echo '<div class="student-group" data-groupid="0">';
                $allStudentCount = count($students);
                $allStudentIndex = 0;
                foreach ($students as $student) {
                    $allStudentIndex++;
                    $isLastInGroup = ($allStudentIndex === $allStudentCount);
                    display_student_row($student, $courseid, $isLastInGroup);
                }
                echo '</div>';
            }
            
            function display_student_row($student, $courseid, $isLast = false) {
                $progress = local_academic_dashboard_get_student_progress($student->id, $courseid);
                $attendance = local_academic_dashboard_get_student_attendance($student->id, $courseid);
                
                echo '<div class="card-body d-flex align-items-center student-card" data-userid="' . $student->id . '" draggable="true" style="border-radius: 0; ' . ($isLast ? '' : 'border-bottom: 1px solid #dee2e6;') . '">';
                echo '<i class="fa fa-bars mr-3" style="cursor: move;"></i>';
                echo '<div class="flex-grow-1">';
                echo '<a href="user.php?id=' . $student->id . '&fromcourse=' . $courseid . '">' . fullname($student) . '</a>';
                echo '</div>';
                echo '<a href="#" class="mr-3" onclick="openUserEmail(' . $student->id . ', ' . $courseid . '); return false;" title="' . $student->email . '">';
                echo '<i class="fa fa-envelope"></i>';
                echo '</a>';
                
                if ($progress !== null) {
                    echo '<div class="mr-3 d-flex align-items-center">';
                    echo '<div class="progress mr-2" style="width: 100px; height: 20px;">';
                    echo '<div class="progress-bar bg-info" role="progressbar" style="width: ' . $progress . '%"></div>';
                    echo '</div>';
                    echo '<small style="white-space: nowrap;">' . get_string('progress', 'local_academic_dashboard') . ': ' . round($progress) . '%</small>';
                    echo '</div>';
                }
                
                if ($attendance !== null) {
                    echo '<div class="d-flex align-items-center">';
                    echo '<div class="progress mr-2" style="width: 100px; height: 20px;">';
                    echo '<div class="progress-bar bg-success" role="progressbar" style="width: ' . $attendance . '%"></div>';
                    echo '</div>';
                    echo '<small style="white-space: nowrap;">' . get_string('attendance', 'local_academic_dashboard') . ': ' . $attendance . '%</small>';
                    echo '</div>';
                } else {
                    echo '<div class="d-flex align-items-center">';
                    echo '<div class="progress mr-2" style="width: 100px; height: 20px;">';
                    echo '<div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>';
                    echo '</div>';
                    echo '<small style="white-space: nowrap;">' . get_string('attendance', 'local_academic_dashboard') . ': 0%</small>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
            ?>
    
            <div class="card-body" style="background: #f7f7f7; padding-top:15px;">
                <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
                    <?php echo get_string('save', 'local_academic_dashboard'); ?>
                </button>
            </div>
        </form>
    </div>
</div>


<script>
let draggedElement = null;
const changes = [];

document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.style.opacity = '0.5';
    saveBtn.style.cursor = 'not-allowed';
});

document.querySelectorAll('.student-card').forEach(card => {
    card.addEventListener('dragstart', function(e) {
        draggedElement = this;
        this.style.opacity = '0.4';
    });
    
    card.addEventListener('dragend', function(e) {
        this.style.opacity = '1';
    });
    
    card.addEventListener('dragover', function(e) {
        e.preventDefault();
        return false;
    });
    
    card.addEventListener('drop', function(e) {
        e.preventDefault();
        if (draggedElement !== this) {
            const targetGroup = this.closest('.student-group');
            targetGroup.insertBefore(draggedElement, this);
            
            const userid = draggedElement.dataset.userid;
            const groupid = targetGroup.dataset.groupid;
            
            const existingIndex = changes.findIndex(c => c.userid === userid);
            if (existingIndex > -1) {
                changes.splice(existingIndex, 1);
            }
            
            changes.push({userid: userid, groupid: groupid});
            
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = false;
            saveBtn.style.opacity = '1';
            saveBtn.style.cursor = 'pointer';
        }
        return false;
    });
});

document.getElementById('groupForm').addEventListener('submit', function(e) {
    document.getElementById('groupData').value = JSON.stringify(changes);
});

function openUserEmail(userid, courseid) {
    const url = '<?php echo new moodle_url('/local/academic_dashboard/mail_compose.php'); ?>?userid=' + userid + '&courseid=' + courseid;
    window.open(url, 'EmailComposer', 'width=900,height=700,scrollbars=yes,resizable=yes');
}

function openCourseEmail(courseid) {
    const url = '<?php echo new moodle_url('/local/academic_dashboard/mail_compose.php'); ?>?courseid=' + courseid;
    window.open(url, 'EmailComposer', 'width=900,height=700,scrollbars=yes,resizable=yes');
}
</script>

<?php
echo $OUTPUT->footer();
