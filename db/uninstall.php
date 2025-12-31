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
 * Uninstall script.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Uninstall the plugin.
 *
 * @return bool
 */
function xmldb_local_academic_dashboard_uninstall() {
    global $DB;

    // Delete custom roles created by this plugin.
    $roles = ['academicmanager', 'pedagogicmanager', 'studentservice'];

    foreach ($roles as $shortname) {
        $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
        if ($roleid) {
            // Check if there are any role assignments.
            $assignments = $DB->count_records('role_assignments', ['roleid' => $roleid]);
            if ($assignments == 0) {
                delete_role($roleid);
            }
        }
    }

    return true;
}
