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
 * Installation script - creates roles and assigns capabilities.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install the plugin, create roles and assign capabilities.
 *
 * @return bool
 */
function xmldb_local_academic_dashboard_install() {
    global $DB;

    $systemcontext = context_system::instance();

    // Define roles to create.
    $roles = [
        'academicmanager' => [
            'name' => 'Academic Manager',
            'shortname' => 'academicmanager',
            'description' => 'Academic manager with full access to the academic dashboard, tasks, student cards, and service requests.',
            'archetype' => 'manager',
        ],
        'pedagogicmanager' => [
            'name' => 'Pedagogic Manager',
            'shortname' => 'pedagogicmanager',
            'description' => 'Pedagogic manager with read access to dashboard and cards, without deletion or service request management.',
            'archetype' => 'editingteacher',
        ],
        'studentservice' => [
            'name' => 'Student Service Representative',
            'shortname' => 'studentservice',
            'description' => 'Student service representative with access to service requests and partial student card access.',
            'archetype' => 'teacher',
        ],
    ];

    foreach ($roles as $shortname => $roledata) {
        // Check if role already exists.
        $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);

        if (!$roleid) {
            // Create the role.
            $roleid = create_role(
                $roledata['name'],
                $roledata['shortname'],
                $roledata['description'],
                $roledata['archetype']
            );

            // Allow role to be assigned at system context.
            set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        }
    }

    return true;
}
