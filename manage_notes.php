<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();
require_capability('local/academic_dashboard:view', context_system::instance());

$action = required_param('action', PARAM_ALPHA);
$courseid = required_param('courseid', PARAM_INT);

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'list':
            $notes = $DB->get_records('local_acad_course_notes', ['courseid' => $courseid], 'timecreated DESC');
            $result = [];
            foreach ($notes as $note) {
                $result[] = [
                    'id' => $note->id,
                    'title' => $note->title,
                    'content' => $note->content,
                    'timecreated' => userdate($note->timecreated, get_string('strftimedatetime', 'langconfig'))
                ];
            }
            echo json_encode(['success' => true, 'notes' => $result]);
            break;
            
        case 'add':
            $title = required_param('title', PARAM_TEXT);
            $content = required_param('content', PARAM_RAW);
            
            $note = new stdClass();
            $note->courseid = $courseid;
            $note->title = $title;
            $note->content = $content;
            $note->timecreated = time();
            $note->timemodified = time();
            
            $noteid = $DB->insert_record('local_acad_course_notes', $note);
            
            echo json_encode([
                'success' => true,
                'note' => [
                    'id' => $noteid,
                    'title' => $title,
                    'content' => $content,
                    'timecreated' => userdate($note->timecreated, get_string('strftimedatetime', 'langconfig'))
                ]
            ]);
            break;
            
        case 'update':
            $noteid = required_param('noteid', PARAM_INT);
            $title = required_param('title', PARAM_TEXT);
            $content = required_param('content', PARAM_RAW);
            
            $note = $DB->get_record('local_acad_course_notes', ['id' => $noteid], '*', MUST_EXIST);
            $note->title = $title;
            $note->content = $content;
            $note->timemodified = time();
            
            $DB->update_record('local_acad_course_notes', $note);
            
            echo json_encode([
                'success' => true,
                'note' => [
                    'id' => $noteid,
                    'title' => $title,
                    'content' => $content,
                    'timecreated' => userdate($note->timecreated, get_string('strftimedatetime', 'langconfig'))
                ]
            ]);
            break;
            
        case 'delete':
            $noteid = required_param('noteid', PARAM_INT);
            $DB->delete_records('local_acad_course_notes', ['id' => $noteid]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get':
            $noteid = required_param('noteid', PARAM_INT);
            $note = $DB->get_record('local_acad_course_notes', ['id' => $noteid], '*', MUST_EXIST);
            
            echo json_encode([
                'success' => true,
                'note' => [
                    'id' => $note->id,
                    'title' => $note->title,
                    'content' => $note->content
                ]
            ]);
            break;
            
        default:
            throw new moodle_exception('invalidaction');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
