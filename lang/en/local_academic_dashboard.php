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
 * English language strings for local_academic_dashboard.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name and general.
$string['pluginname'] = 'Academic Dashboard';
$string['academic_dashboard'] = 'Academic Dashboard';
$string['dashboard'] = 'Dashboard';
$string['backtodashboard'] = 'Back to Dashboard';

// Capabilities.
$string['academic_dashboard:viewdashboard'] = 'View academic dashboard';
$string['academic_dashboard:managetasks'] = 'Manage tasks';
$string['academic_dashboard:viewstudentcard'] = 'View student cards';
$string['academic_dashboard:viewclasscard'] = 'View class cards';
$string['academic_dashboard:sendmessages'] = 'Send messages';
$string['academic_dashboard:manageservicerequests'] = 'Manage service requests';
$string['academic_dashboard:viewservicerequests'] = 'View service requests';
$string['academic_dashboard:viewalerts'] = 'View alerts';
$string['academic_dashboard:viewcalendar'] = 'View management calendar';

// Navigation.
$string['nav_dashboard'] = 'Dashboard';
$string['nav_tasks'] = 'Tasks';
$string['nav_students'] = 'Students';
$string['nav_classes'] = 'Classes';
$string['nav_requests'] = 'Service Requests';
$string['nav_alerts'] = 'Alerts';
$string['nav_settings'] = 'Settings';

// Dashboard widgets.
$string['widget_tasks_today'] = 'Tasks Today';
$string['widget_tasks_week'] = 'Tasks This Week';
$string['widget_tasks_overdue'] = 'Overdue Tasks';
$string['widget_atrisk_students'] = 'At-Risk Students';
$string['widget_open_requests'] = 'Open Service Requests';
$string['widget_quick_access'] = 'Quick Access';

// Tasks.
$string['tasks'] = 'Tasks';
$string['task'] = 'Task';
$string['newtask'] = 'New Task';
$string['edittask'] = 'Edit Task';
$string['deletetask'] = 'Delete Task';
$string['tasktitle'] = 'Title';
$string['taskdescription'] = 'Description';
$string['taskassigntype'] = 'Assignment Type';
$string['taskassigntype_student'] = 'Student';
$string['taskassigntype_class'] = 'Class';
$string['taskassigntype_general'] = 'General';
$string['taskassignee'] = 'Assignee';
$string['taskduedate'] = 'Due Date';
$string['taskpriority'] = 'Priority';
$string['taskpriority_low'] = 'Low';
$string['taskpriority_medium'] = 'Medium';
$string['taskpriority_high'] = 'High';
$string['taskstatus'] = 'Status';
$string['taskstatus_open'] = 'Open';
$string['taskstatus_inprogress'] = 'In Progress';
$string['taskstatus_completed'] = 'Completed';
$string['taskstatus_cancelled'] = 'Cancelled';
$string['taskrecurring'] = 'Recurring Task';
$string['taskrecurringfreq'] = 'Frequency';
$string['taskrecurringfreq_daily'] = 'Daily';
$string['taskrecurringfreq_weekly'] = 'Weekly';
$string['taskrecurringfreq_monthly'] = 'Monthly';
$string['taskrecurringday'] = 'Day';
$string['taskrecurringend'] = 'End Date';
$string['taskcourse'] = 'Related Course';
$string['taskactivity'] = 'Related Activity';
$string['tasktags'] = 'Tags';
$string['taskcreated'] = 'Task created successfully';
$string['taskupdated'] = 'Task updated successfully';
$string['taskdeleted'] = 'Task deleted successfully';
$string['taskmarkcomplete'] = 'Mark as Complete';
$string['tasksendreminder'] = 'Send Reminder';
$string['tasksharetask'] = 'Share Task';
$string['tasksharedwith'] = 'Shared with';

// Student card.
$string['studentcard'] = 'Student Card';
$string['studentinfo'] = 'Student Information';
$string['studentclasses'] = 'Classes';
$string['studentcourses'] = 'Active Courses';
$string['studentprogress'] = 'Learning Progress';
$string['studentcompletionrate'] = 'Completion Rate';
$string['studentlastactivity'] = 'Last Activity';
$string['studentopentasks'] = 'Open Tasks';
$string['studentrequests'] = 'Service Requests';
$string['studentmessage'] = 'Send Message';
$string['studentcreatetask'] = 'Create Task';
$string['studentcreaterequest'] = 'Create Service Request';
$string['studentviewcourses'] = 'View Courses';

// Class card.
$string['classcard'] = 'Class Card';
$string['classinfo'] = 'Class Information';
$string['classstudents'] = 'Students';
$string['classgroups'] = 'Groups';
$string['classstatus'] = 'Activity Status';
$string['classatrisk'] = 'At-Risk Students';
$string['classevents'] = 'Upcoming Events';
$string['classopentasks'] = 'Open Tasks';
$string['classmessageall'] = 'Message All Students';
$string['classmessagegroup'] = 'Message Group';
$string['classcreatetask'] = 'Create Class Task';
$string['classviewrequests'] = 'View Service Requests';

// Service requests.
$string['servicerequests'] = 'Service Requests';
$string['servicerequest'] = 'Service Request';
$string['newrequest'] = 'New Request';
$string['editrequest'] = 'Edit Request';
$string['requesttype'] = 'Request Type';
$string['requesttype_academic'] = 'Academic';
$string['requesttype_administrative'] = 'Administrative';
$string['requesttype_technical'] = 'Technical';
$string['requesttype_other'] = 'Other';
$string['requestdescription'] = 'Description';
$string['requeststatus'] = 'Status';
$string['requeststatus_open'] = 'Open';
$string['requeststatus_inprogress'] = 'In Progress';
$string['requeststatus_resolved'] = 'Resolved';
$string['requeststatus_closed'] = 'Closed';
$string['requestassignee'] = 'Assigned To';
$string['requestinternalnotes'] = 'Internal Notes';
$string['requesthistory'] = 'History';
$string['requestcreated'] = 'Request created successfully';
$string['requestupdated'] = 'Request updated successfully';

// Alerts.
$string['alerts'] = 'Alerts';
$string['alert'] = 'Alert';
$string['alerttype'] = 'Alert Type';
$string['alerttype_no_activity'] = 'No Activity';
$string['alerttype_no_completion'] = 'No Completions';
$string['alerttype_low_grade'] = 'Low Grade';
$string['alertstatus'] = 'Status';
$string['alertstatus_active'] = 'Active';
$string['alertstatus_acknowledged'] = 'Acknowledged';
$string['alertstatus_resolved'] = 'Resolved';
$string['alertacknowledge'] = 'Acknowledge';
$string['alertresolve'] = 'Resolve';
$string['alertcreatetask'] = 'Create Follow-up Task';
$string['alertsendmessage'] = 'Send Message';

// Filters.
$string['filterclass'] = 'Filter by Class';
$string['filterstudent'] = 'Filter by Student';
$string['filterdaterange'] = 'Date Range';
$string['filterfrom'] = 'From';
$string['filterto'] = 'To';
$string['filterapply'] = 'Apply Filters';
$string['filterclear'] = 'Clear Filters';
$string['filterall'] = 'All';

// Calendar.
$string['calendar'] = 'Calendar';
$string['calendarevent'] = 'Calendar Event';
$string['calendarview'] = 'View Calendar';

// Messages.
$string['sendmessage'] = 'Send Message';
$string['messagesubject'] = 'Subject';
$string['messagebody'] = 'Message';
$string['messagesent'] = 'Message sent successfully';
$string['messageerror'] = 'Error sending message';

// Settings.
$string['settings'] = 'Settings';
$string['settings_general'] = 'General Settings';
$string['settings_alerts'] = 'Alert Settings';
$string['settings_alerts_desc'] = 'Configure when alerts are triggered for at-risk students.';
$string['settings_inactivity_days'] = 'Inactivity threshold (days)';
$string['settings_inactivity_days_desc'] = 'Number of days without activity before triggering an alert.';
$string['settings_completion_threshold'] = 'Completion threshold (%)';
$string['settings_completion_threshold_desc'] = 'Minimum completion percentage before triggering an alert.';

// Errors.
$string['error_nopermission'] = 'You do not have permission to access this page.';
$string['error_notfound'] = 'The requested item was not found.';
$string['error_invaliddata'] = 'Invalid data provided.';

// Misc.
$string['confirm'] = 'Confirm';
$string['cancel'] = 'Cancel';
$string['save'] = 'Save';
$string['delete'] = 'Delete';
$string['edit'] = 'Edit';
$string['view'] = 'View';
$string['create'] = 'Create';
$string['close'] = 'Close';
$string['actions'] = 'Actions';
$string['nodata'] = 'No data available';
$string['loading'] = 'Loading...';
