<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage(
        'local_academic_dashboard',
        get_string('pluginname', 'local_academic_dashboard'),
        new moodle_url('/local/academic_dashboard/index.php')
    ));
}
