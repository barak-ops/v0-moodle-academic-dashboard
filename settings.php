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
 * Plugin settings.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_academic_dashboard', get_string('pluginname', 'local_academic_dashboard'));

    // General settings.
    $settings->add(new admin_setting_heading(
        'local_academic_dashboard/general',
        get_string('settings_general', 'local_academic_dashboard'),
        ''
    ));

    // Alert settings.
    $settings->add(new admin_setting_heading(
        'local_academic_dashboard/alerts',
        get_string('settings_alerts', 'local_academic_dashboard'),
        get_string('settings_alerts_desc', 'local_academic_dashboard')
    ));

    // Inactivity threshold.
    $settings->add(new admin_setting_configtext(
        'local_academic_dashboard/inactivity_days',
        get_string('settings_inactivity_days', 'local_academic_dashboard'),
        get_string('settings_inactivity_days_desc', 'local_academic_dashboard'),
        7,
        PARAM_INT
    ));

    // Completion threshold.
    $settings->add(new admin_setting_configtext(
        'local_academic_dashboard/completion_threshold',
        get_string('settings_completion_threshold', 'local_academic_dashboard'),
        get_string('settings_completion_threshold_desc', 'local_academic_dashboard'),
        50,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
