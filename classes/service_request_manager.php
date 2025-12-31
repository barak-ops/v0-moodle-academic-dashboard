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
 * Service request manager class.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_academic_dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Class service_request_manager
 *
 * Handles CRUD operations for service requests.
 */
class service_request_manager {

    /**
     * Create a new service request.
     *
     * @param object $data Request data.
     * @return int The new request ID.
     */
    public static function create_request($data) {
        global $DB, $USER;

        $request = new \stdClass();
        $request->studentid = $data->studentid;
        $request->requesttype = $data->requesttype;
        $request->description = $data->description;
        $request->status = 'open';
        $request->assigneeid = $data->assigneeid ?? null;
        $request->createdby = $USER->id;
        $request->internalnotes = $data->internalnotes ?? '';
        $request->timecreated = time();
        $request->timemodified = time();

        $requestid = $DB->insert_record('local_acad_service_requests', $request);

        // Create calendar event.
        $event = new \stdClass();
        $event->name = get_string('servicerequest', 'local_academic_dashboard') . ' #' . $requestid;
        $event->description = $request->description;
        $event->format = FORMAT_HTML;
        $event->eventtype = 'user';
        $event->userid = $request->assigneeid ?? $USER->id;
        $event->timestart = time();
        $event->timeduration = 0;
        $event->visible = 1;
        $event->component = 'local_academic_dashboard';

        $calendarevent = \calendar_event::create($event, false);
        $DB->set_field('local_acad_service_requests', 'calendareventid', $calendarevent->id, ['id' => $requestid]);

        // Add history entry.
        self::add_history($requestid, 'created', get_string('requestcreated', 'local_academic_dashboard'));

        // Create follow-up task if requested.
        if (!empty($data->createtask)) {
            $taskdata = new \stdClass();
            $taskdata->title = get_string('servicerequest', 'local_academic_dashboard') . ' #' . $requestid;
            $taskdata->description = $request->description;
            $taskdata->assigntype = 'student';
            $taskdata->studentid = $request->studentid;
            $taskdata->assigneeid = $request->assigneeid ?? $USER->id;
            $taskdata->servicerequestid = $requestid;
            $taskdata->priority = 2;

            task_manager::create_task($taskdata);
        }

        return $requestid;
    }

    /**
     * Update a service request.
     *
     * @param object $data Request data with ID.
     * @return bool Success status.
     */
    public static function update_request($data) {
        global $DB, $USER;

        $request = $DB->get_record('local_acad_service_requests', ['id' => $data->id], '*', MUST_EXIST);
        $oldstatus = $request->status;

        $request->requesttype = $data->requesttype ?? $request->requesttype;
        $request->description = $data->description ?? $request->description;
        $request->status = $data->status ?? $request->status;
        $request->assigneeid = $data->assigneeid ?? $request->assigneeid;
        $request->internalnotes = $data->internalnotes ?? $request->internalnotes;
        $request->timemodified = time();

        // Set resolved time if status changed to resolved.
        if ($request->status === 'resolved' && $oldstatus !== 'resolved') {
            $request->timeresolved = time();
        }

        $result = $DB->update_record('local_acad_service_requests', $request);

        // Add history entry.
        $details = '';
        if ($oldstatus !== $request->status) {
            $details = "Status changed from $oldstatus to {$request->status}";
        }
        self::add_history($request->id, 'updated', $details);

        return $result;
    }

    /**
     * Get a service request by ID.
     *
     * @param int $requestid The request ID.
     * @return object|false The request object or false.
     */
    public static function get_request($requestid) {
        global $DB;

        $sql = "SELECT sr.*, 
                       u.firstname AS student_firstname, u.lastname AS student_lastname,
                       a.firstname AS assignee_firstname, a.lastname AS assignee_lastname
                FROM {local_acad_service_requests} sr
                JOIN {user} u ON u.id = sr.studentid
                LEFT JOIN {user} a ON a.id = sr.assigneeid
                WHERE sr.id = :id";

        return $DB->get_record_sql($sql, ['id' => $requestid]);
    }

    /**
     * Get service requests with filters.
     *
     * @param array $filters Associative array of filters.
     * @param string $sort Sort field.
     * @param string $order Sort order (ASC/DESC).
     * @param int $limitfrom Offset.
     * @param int $limitnum Limit.
     * @return array Array of requests.
     */
    public static function get_requests($filters = [], $sort = 'timecreated', $order = 'DESC', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'sr.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['studentid'])) {
            $where[] = 'sr.studentid = :studentid';
            $params['studentid'] = $filters['studentid'];
        }

        if (!empty($filters['assigneeid'])) {
            $where[] = 'sr.assigneeid = :assigneeid';
            $params['assigneeid'] = $filters['assigneeid'];
        }

        if (!empty($filters['requesttype'])) {
            $where[] = 'sr.requesttype = :requesttype';
            $params['requesttype'] = $filters['requesttype'];
        }

        if (!empty($filters['classid'])) {
            $where[] = 'EXISTS (SELECT 1 FROM {local_acad_class_members} cm WHERE cm.userid = sr.studentid AND cm.classid = :classid)';
            $params['classid'] = $filters['classid'];
        }

        $wheresql = implode(' AND ', $where);
        $ordersql = "$sort $order";

        $sql = "SELECT sr.*, 
                       u.firstname AS student_firstname, u.lastname AS student_lastname,
                       a.firstname AS assignee_firstname, a.lastname AS assignee_lastname
                FROM {local_acad_service_requests} sr
                JOIN {user} u ON u.id = sr.studentid
                LEFT JOIN {user} a ON a.id = sr.assigneeid
                WHERE $wheresql
                ORDER BY $ordersql";

        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * Get open service requests count.
     *
     * @param int $assigneeid Optional assignee filter.
     * @return int Count of open requests.
     */
    public static function get_open_requests_count($assigneeid = 0) {
        global $DB;

        $params = ['status1' => 'open', 'status2' => 'inprogress'];
        $sql = "SELECT COUNT(*) FROM {local_acad_service_requests} WHERE status IN (:status1, :status2)";

        if ($assigneeid > 0) {
            $sql .= " AND assigneeid = :assigneeid";
            $params['assigneeid'] = $assigneeid;
        }

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Add history entry for a request.
     *
     * @param int $requestid The request ID.
     * @param string $action The action performed.
     * @param string $details Additional details.
     */
    protected static function add_history($requestid, $action, $details = '') {
        global $DB, $USER;

        $history = new \stdClass();
        $history->requestid = $requestid;
        $history->userid = $USER->id;
        $history->action = $action;
        $history->details = $details;
        $history->timecreated = time();

        $DB->insert_record('local_acad_request_history', $history);
    }

    /**
     * Get request history.
     *
     * @param int $requestid The request ID.
     * @return array Array of history entries.
     */
    public static function get_history($requestid) {
        global $DB;

        $sql = "SELECT h.*, u.firstname, u.lastname
                FROM {local_acad_request_history} h
                JOIN {user} u ON u.id = h.userid
                WHERE h.requestid = :requestid
                ORDER BY h.timecreated DESC";

        return $DB->get_records_sql($sql, ['requestid' => $requestid]);
    }
}
