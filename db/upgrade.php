<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_academic_dashboard_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025010700) {
        $table = new xmldb_table('local_acad_course_notes');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        
        $table->add_index('courseid_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2025010700, 'local', 'academic_dashboard');
    }

    return true;
}
