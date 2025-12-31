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
 * Task manager class for handling academic tasks.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_academic_dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Class task_manager
 *
 * Handles CRUD operations for academic tasks.
 */
class task_manager {

    /**
     * Create a new task.
     *
     * @param object $data Task data.
     * @return int The new task ID.
     */
    public static function create_task($data) {
        global $DB, $USER;

        $task = new \stdClass();
        $task->title = $data->title;
        $task->description = $data->description ?? '';
        $task->assigntype = $data->assigntype ?? 'general';
        $task->studentid = $data->studentid ?? null;
        $task->classid = $data->classid ?? null;
        $task->assigneeid = $data->assigneeid ?? $USER->id;
        $task->createdby = $USER->id;
        $task->duedate = $data->duedate ?? null;
        $task->priority = $data->priority ?? 2;
        $task->status = 'open';
        $task->isrecurring = $data->isrecurring ?? 0;
        $task->recurringfreq = $data->recurringfreq ?? null;
        $task->recurringday = $data->recurringday ?? null;
        $task->recurringend = $data->recurringend ?? null;
        $task->courseid = $data->courseid ?? null;
        $task->cmid = $data->cmid ?? null;
        $task->servicerequestid = $data->servicerequestid ?? null;
        $task->timecreated = time();
        $task->timemodified = time();

        $taskid = $DB->insert_record('local_acad_tasks', $task);

        // Create calendar event if due date is set.
        if (!empty($task->duedate)) {
            $task->id = $taskid;
            $eventid = local_academic_dashboard_create_calendar_event($task);
            $DB->set_field('local_acad_tasks', 'calendareventid', $eventid, ['id' => $taskid]);
        }

        // Handle tags.
        if (!empty($data->tags)) {
            self::save_task_tags($taskid, $data->tags);
        }

        // Trigger event.
        $event = \local_academic_dashboard\event\task_created::create([
            'objectid' => $taskid,
            'context' => \context_system::instance(),
            'other' => ['title' => $task->title],
        ]);
        $event->trigger();

        return $taskid;
    }

    /**
     * Update an existing task.
     *
     * @param object $data Task data with ID.
     * @return bool Success status.
     */
    public static function update_task($data) {
        global $DB;

        $task = $DB->get_record('local_acad_tasks', ['id' => $data->id], '*', MUST_EXIST);

        $task->title = $data->title ?? $task->title;
        $task->description = $data->description ?? $task->description;
        $task->assigntype = $data->assigntype ?? $task->assigntype;
        $task->studentid = $data->studentid ?? $task->studentid;
        $task->classid = $data->classid ?? $task->classid;
        $task->assigneeid = $data->assigneeid ?? $task->assigneeid;
        $task->duedate = $data->duedate ?? $task->duedate;
        $task->priority = $data->priority ?? $task->priority;
        $task->status = $data->status ?? $task->status;
        $task->isrecurring = $data->isrecurring ?? $task->isrecurring;
        $task->recurringfreq = $data->recurringfreq ?? $task->recurringfreq;
        $task->recurringday = $data->recurringday ?? $task->recurringday;
        $task->recurringend = $data->recurringend ?? $task->recurringend;
        $task->courseid = $data->courseid ?? $task->courseid;
        $task->cmid = $data->cmid ?? $task->cmid;
        $task->timemodified = time();

        $result = $DB->update_record('local_acad_tasks', $task);

        // Update calendar event.
        if (!empty($task->duedate)) {
            if (!empty($task->calendareventid)) {
                local_academic_dashboard_update_calendar_event($task);
            } else {
                $eventid = local_academic_dashboard_create_calendar_event($task);
                $DB->set_field('local_acad_tasks', 'calendareventid', $eventid, ['id' => $task->id]);
            }
        }

        // Handle tags.
        if (isset($data->tags)) {
            self::save_task_tags($task->id, $data->tags);
        }

        // Trigger event.
        $event = \local_academic_dashboard\event\task_updated::create([
            'objectid' => $task->id,
            'context' => \context_system::instance(),
            'other' => ['title' => $task->title],
        ]);
        $event->trigger();

        return $result;
    }

    /**
     * Delete a task.
     *
     * @param int $taskid The task ID.
     * @return bool Success status.
     */
    public static function delete_task($taskid) {
        global $DB;

        $task = $DB->get_record('local_acad_tasks', ['id' => $taskid], '*', MUST_EXIST);

        // Delete calendar event.
        if (!empty($task->calendareventid)) {
            local_academic_dashboard_delete_calendar_event($task->calendareventid);
        }

        // Delete tags.
        $DB->delete_records('local_acad_task_tags', ['taskid' => $taskid]);

        // Delete shares.
        $DB->delete_records('local_acad_task_shares', ['taskid' => $taskid]);

        // Delete task.
        $result = $DB->delete_records('local_acad_tasks', ['id' => $taskid]);

        // Trigger event.
        $event = \local_academic_dashboard\event\task_deleted::create([
            'objectid' => $taskid,
            'context' => \context_system::instance(),
            'other' => ['title' => $task->title],
        ]);
        $event->trigger();

        return $result;
    }

    /**
     * Get a task by ID.
     *
     * @param int $taskid The task ID.
     * @return object|false The task object or false.
     */
    public static function get_task($taskid) {
        global $DB;
        return $DB->get_record('local_acad_tasks', ['id' => $taskid]);
    }

    /**
     * Get tasks with filters.
     *
     * @param array $filters Associative array of filters.
     * @param string $sort Sort field.
     * @param string $order Sort order (ASC/DESC).
     * @param int $limitfrom Offset.
     * @param int $limitnum Limit.
     * @return array Array of tasks.
     */
    public static function get_tasks($filters = [], $sort = 'duedate', $order = 'ASC', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['assigneeid'])) {
            $where[] = 't.assigneeid = :assigneeid';
            $params['assigneeid'] = $filters['assigneeid'];
        }

        if (!empty($filters['assigntype'])) {
            $where[] = 't.assigntype = :assigntype';
            $params['assigntype'] = $filters['assigntype'];
        }

        if (!empty($filters['studentid'])) {
            $where[] = 't.studentid = :studentid';
            $params['studentid'] = $filters['studentid'];
        }

        if (!empty($filters['classid'])) {
            $where[] = 't.classid = :classid';
            $params['classid'] = $filters['classid'];
        }

        if (!empty($filters['duedate_from'])) {
            $where[] = 't.duedate >= :duedate_from';
            $params['duedate_from'] = $filters['duedate_from'];
        }

        if (!empty($filters['duedate_to'])) {
            $where[] = 't.duedate <= :duedate_to';
            $params['duedate_to'] = $filters['duedate_to'];
        }

        if (!empty($filters['overdue'])) {
            $where[] = 't.duedate < :now AND t.status != :completed';
            $params['now'] = time();
            $params['completed'] = 'completed';
        }

        $wheresql = implode(' AND ', $where);
        $ordersql = "$sort $order";

        $sql = "SELECT t.*, u.firstname AS assignee_firstname, u.lastname AS assignee_lastname
                FROM {local_acad_tasks} t
                LEFT JOIN {user} u ON u.id = t.assigneeid
                WHERE $wheresql
                ORDER BY $ordersql";

        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * Get tasks for today.
     *
     * @param int $userid Optional user ID filter.
     * @return array Array of tasks.
     */
    public static function get_tasks_today($userid = 0) {
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');

        $filters = [
            'duedate_from' => $today,
            'duedate_to' => $tomorrow - 1,
        ];

        if ($userid > 0) {
            $filters['assigneeid'] = $userid;
        }

        return self::get_tasks($filters);
    }

    /**
     * Get tasks for this week.
     *
     * @param int $userid Optional user ID filter.
     * @return array Array of tasks.
     */
    public static function get_tasks_week($userid = 0) {
        $startofweek = strtotime('monday this week');
        $endofweek = strtotime('sunday this week 23:59:59');

        $filters = [
            'duedate_from' => $startofweek,
            'duedate_to' => $endofweek,
        ];

        if ($userid > 0) {
            $filters['assigneeid'] = $userid;
        }

        return self::get_tasks($filters);
    }

    /**
     * Get overdue tasks.
     *
     * @param int $userid Optional user ID filter.
     * @return array Array of tasks.
     */
    public static function get_overdue_tasks($userid = 0) {
        $filters = ['overdue' => true];

        if ($userid > 0) {
            $filters['assigneeid'] = $userid;
        }

        return self::get_tasks($filters);
    }

    /**
     * Mark a task as complete.
     *
     * @param int $taskid The task ID.
     * @return bool Success status.
     */
    public static function complete_task($taskid) {
        global $DB;

        return $DB->set_field('local_acad_tasks', 'status', 'completed', ['id' => $taskid]);
    }

    /**
     * Save task tags.
     *
     * @param int $taskid The task ID.
     * @param array $tags Array of tag data.
     */
    protected static function save_task_tags($taskid, $tags) {
        global $DB;

        // Delete existing tags.
        $DB->delete_records('local_acad_task_tags', ['taskid' => $taskid]);

        // Insert new tags.
        foreach ($tags as $tag) {
            $record = new \stdClass();
            $record->taskid = $taskid;
            $record->tagtype = $tag['type'] ?? 'custom';
            $record->tagvalue = $tag['value'];
            $record->refid = $tag['refid'] ?? null;
            $DB->insert_record('local_acad_task_tags', $record);
        }
    }

    /**
     * Share a task with other users.
     *
     * @param int $taskid The task ID.
     * @param array $userids Array of user IDs.
     * @return bool Success status.
     */
    public static function share_task($taskid, $userids) {
        global $DB;

        foreach ($userids as $userid) {
            // Check if already shared.
            $exists = $DB->record_exists('local_acad_task_shares', ['taskid' => $taskid, 'userid' => $userid]);
            if (!$exists) {
                $share = new \stdClass();
                $share->taskid = $taskid;
                $share->userid = $userid;
                $share->timecreated = time();
                $DB->insert_record('local_acad_task_shares', $share);
            }
        }

        return true;
    }

    /**
     * Send a reminder for a task.
     *
     * @param int $taskid The task ID.
     * @param string $method Reminder method ('email', 'message').
     * @return bool Success status.
     */
    public static function send_reminder($taskid, $method = 'message') {
        global $DB, $USER;

        $task = self::get_task($taskid);
        if (!$task) {
            return false;
        }

        $subject = get_string('tasksendreminder', 'local_academic_dashboard') . ': ' . $task->title;
        $message = $task->description;

        if ($method === 'message') {
            return local_academic_dashboard_send_message($USER->id, $task->assigneeid, $subject, $message);
        } else if ($method === 'email') {
            $user = $DB->get_record('user', ['id' => $task->assigneeid]);
            return email_to_user($user, $USER, $subject, $message);
        }

        return false;
    }
}
