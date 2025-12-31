<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AJAX handler for academic dashboard.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');

require_login();
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
$context = context_system::instance();

header('Content-Type: application/json');

$response = ['success' => false];

try {
    switch ($action) {
        case 'complete_task':
            require_capability('local/academic_dashboard:managetasks', $context);
            $taskid = required_param('taskid', PARAM_INT);
            $result = \local_academic_dashboard\task_manager::complete_task($taskid);
            $response['success'] = $result;
            break;

        case 'get_students':
            require_capability('local/academic_dashboard:viewstudentcard', $context);
            $classid = optional_param('classid', 0, PARAM_INT);
            $search = optional_param('search', '', PARAM_TEXT);

            $sql = "SELECT u.id, u.firstname, u.lastname, u.email
                    FROM {user} u
                    WHERE u.deleted = 0 AND u.suspended = 0";
            $params = [];

            if ($classid > 0) {
                $sql .= " AND EXISTS (SELECT 1 FROM {local_acad_class_members} cm WHERE cm.userid = u.id AND cm.classid = :classid)";
                $params['classid'] = $classid;
            }

            if (!empty($search)) {
                $sql .= " AND (u.firstname LIKE :search1 OR u.lastname LIKE :search2 OR u.email LIKE :search3)";
                $params['search1'] = "%$search%";
                $params['search2'] = "%$search%";
                $params['search3'] = "%$search%";
            }

            $sql .= " ORDER BY u.lastname, u.firstname LIMIT 50";

            $students = $DB->get_records_sql($sql, $params);
            $response['success'] = true;
            $response['data'] = array_values($students);
            break;

        case 'update_request_status':
            require_capability('local/academic_dashboard:manageservicerequests', $context);
            $requestid = required_param('requestid', PARAM_INT);
            $status = required_param('status', PARAM_ALPHA);

            $data = new stdClass();
            $data->id = $requestid;
            $data->status = $status;

            $result = \local_academic_dashboard\service_request_manager::update_request($data);
            $response['success'] = $result;
            break;

        case 'acknowledge_alert':
            require_capability('local/academic_dashboard:viewalerts', $context);
            $alertid = required_param('alertid', PARAM_INT);

            $result = $DB->set_field('local_acad_alerts', 'status', 'acknowledged', ['id' => $alertid]);
            $DB->set_field('local_acad_alerts', 'acknowledgedby', $USER->id, ['id' => $alertid]);
            $DB->set_field('local_acad_alerts', 'timemodified', time(), ['id' => $alertid]);

            $response['success'] = $result;
            break;

        default:
            $response['error'] = 'Unknown action';
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
