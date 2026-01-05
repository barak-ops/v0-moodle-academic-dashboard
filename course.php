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
        <h2>
            <?php echo format_string($course->fullname); ?>
            <a href="#" class="btn btn-sm btn-primary ml-2" onclick="openCourseEmail(<?php echo $courseid; ?>); return false;">
                <i class="fa fa-envelope"></i>
            </a>
        </h2>
        <a href="<?php echo $fromuser ? 'user.php?id=' . $fromuser : 'index.php'; ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?php echo get_string('back', 'local_academic_dashboard'); ?>
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-file fa-2x"></i>
                    <h4><?php echo $stats->resources; ?></h4>
                    <small><?php echo get_string('resources', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-question-circle fa-2x"></i>
                    <h4><?php echo $stats->quizzes; ?></h4>
                    <small><?php echo get_string('quizzes', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-tasks fa-2x"></i>
                    <h4><?php echo $stats->assignments; ?></h4>
                    <small><?php echo get_string('assignments', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                    <h4><?php echo $stats->overdue; ?></h4>
                    <small><?php echo get_string('overdue', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-clock-o fa-2x text-primary"></i>
                    <h4><?php echo $stats->remaining; ?></h4>
                    <small><?php echo get_string('remaining', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fa fa-users fa-2x"></i>
                    <h4><?php echo $stats->students; ?></h4>
                    <small><?php echo get_string('students', 'local_academic_dashboard'); ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Teachers -->
    <h4><?php echo get_string('teacher', 'local_academic_dashboard'); ?></h4>
    <ul class="list-group mb-4">
        <?php
        $teachers = get_enrolled_users($context, 'moodle/course:update');
        foreach ($teachers as $teacher):
        ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <a href="user.php?id=<?php echo $teacher->id; ?>&fromcourse=<?php echo $courseid; ?>">
                <?php echo fullname($teacher); ?>
            </a>
            <a href="#" onclick="openEmailModal('<?php echo $teacher->email; ?>'); return false;">
                <?php echo $teacher->email; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    
    <!-- Students by Groups -->
    <h4><?php echo get_string('students', 'local_academic_dashboard'); ?></h4>
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
            // Group students by group
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
            
            // Display grouped students
            foreach ($groups as $group) {
                echo '<h5 class="mt-3">' . format_string($group->name) . '</h5>';
                echo '<div class="student-group" data-groupid="' . $group->id . '">';
                if (isset($groupedstudents[$group->id])) {
                    foreach ($groupedstudents[$group->id] as $student) {
                        display_student_row($student, $courseid);
                    }
                }
                echo '</div>';
            }
            
            // Display students without group
            if (count($nogroupstudents) > 0) {
                echo '<h5 class="mt-3">' . get_string('no_group', 'local_academic_dashboard') . '</h5>';
                echo '<div class="student-group" data-groupid="0">';
                foreach ($nogroupstudents as $student) {
                    display_student_row($student, $courseid);
                }
                echo '</div>';
            }
        } else {
            // No groups, display all students
            echo '<div class="student-group" data-groupid="0">';
            foreach ($students as $student) {
                display_student_row($student, $courseid);
            }
            echo '</div>';
        }
        
        function display_student_row($student, $courseid) {
            $progress = local_academic_dashboard_get_student_progress($student->id, $courseid);
            $attendance = local_academic_dashboard_get_student_attendance($student->id, $courseid);
            
            echo '<div class="card mb-2 student-card" data-userid="' . $student->id . '" draggable="true">';
            echo '<div class="card-body d-flex align-items-center">';
            echo '<i class="fa fa-bars mr-3" style="cursor: move;"></i>';
            echo '<div class="flex-grow-1">';
            echo '<a href="user.php?id=' . $student->id . '&fromcourse=' . $courseid . '">' . fullname($student) . '</a>';
            echo '</div>';
            echo '<a href="#" class="mr-3" onclick="openEmailModal(\'' . $student->email . '\'); return false;">' . $student->email . '</a>';
            
            if ($progress !== null) {
                echo '<div class="mr-3">';
                echo '<small>' . get_string('progress', 'local_academic_dashboard') . ': ' . round($progress) . '%</small>';
                echo '<div class="progress" style="width: 100px;">';
                echo '<div class="progress-bar" role="progressbar" style="width: ' . $progress . '%"></div>';
                echo '</div>';
                echo '</div>';
            }
            
            if ($attendance !== null) {
                echo '<div>';
                echo '<small>' . get_string('attendance', 'local_academic_dashboard') . ': ' . $attendance . '%</small>';
                echo '<div class="progress" style="width: 100px;">';
                echo '<div class="progress-bar bg-success" role="progressbar" style="width: ' . $attendance . '%"></div>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <button type="submit" class="btn btn-primary mt-3" id="saveBtn" disabled>
            <?php echo get_string('save', 'local_academic_dashboard'); ?>
        </button>
    </form>
</div>

<script>
// Drag and drop functionality
let draggedElement = null;
const changes = [];

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
            
            // Track change
            const userid = draggedElement.dataset.userid;
            const groupid = targetGroup.dataset.groupid;
            changes.push({userid: userid, groupid: groupid});
            
            document.getElementById('saveBtn').disabled = false;
        }
        return false;
    });
});

// Save changes
document.getElementById('groupForm').addEventListener('submit', function(e) {
    document.getElementById('groupData').value = JSON.stringify(changes);
});

function openEmailModal(email) {
    alert('Send email to: ' + email);
}

function openCourseEmail(courseid) {
    alert('Send email to all course participants');
}
</script>

<?php
echo $OUTPUT->footer();
