<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();
require_capability('local/academic_dashboard:view', context_system::instance());

$PAGE->set_url(new moodle_url('/local/academic_dashboard/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('dashboard_title', 'local_academic_dashboard'));
$PAGE->set_heading(get_string('dashboard_title', 'local_academic_dashboard'));

echo $OUTPUT->header();

?>

<div class="academic-dashboard">
    <h2><?php echo get_string('dashboard_title', 'local_academic_dashboard'); ?></h2>
    
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="courses-tab" data-toggle="tab" href="#courses" role="tab">
                <?php echo get_string('courses_tab', 'local_academic_dashboard'); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">
                <?php echo get_string('users_tab', 'local_academic_dashboard'); ?>
            </a>
        </li>
    </ul>
    
    <div class="tab-content">
        <div class="tab-pane fade show active" id="courses" role="tabpanel">
            <?php include('courses_list.php'); ?>
        </div>
        <div class="tab-pane fade" id="users" role="tabpanel">
            <?php include('users_search.php'); ?>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
