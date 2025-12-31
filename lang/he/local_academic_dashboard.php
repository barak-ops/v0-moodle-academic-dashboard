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
 * Hebrew language strings for local_academic_dashboard.
 *
 * @package    local_academic_dashboard
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name and general.
$string['pluginname'] = 'דשבורד אקדמי';
$string['academic_dashboard'] = 'דשבורד אקדמי';
$string['dashboard'] = 'דשבורד';

// Capabilities.
$string['academic_dashboard:viewdashboard'] = 'צפייה בדשבורד האקדמי';
$string['academic_dashboard:managetasks'] = 'ניהול משימות';
$string['academic_dashboard:viewstudentcard'] = 'צפייה בכרטיס תלמיד';
$string['academic_dashboard:viewclasscard'] = 'צפייה בכרטיס כיתה';
$string['academic_dashboard:sendmessages'] = 'שליחת הודעות';
$string['academic_dashboard:manageservicerequests'] = 'ניהול פניות שירות';
$string['academic_dashboard:viewservicerequests'] = 'צפייה בפניות שירות';
$string['academic_dashboard:viewalerts'] = 'צפייה בהתראות';
$string['academic_dashboard:viewcalendar'] = 'צפייה ביומן ניהולי';

// Navigation.
$string['nav_dashboard'] = 'דשבורד';
$string['nav_tasks'] = 'משימות';
$string['nav_students'] = 'תלמידים';
$string['nav_classes'] = 'כיתות';
$string['nav_requests'] = 'פניות שירות';
$string['nav_alerts'] = 'התראות';
$string['nav_settings'] = 'הגדרות';

// Dashboard widgets.
$string['widget_tasks_today'] = 'משימות להיום';
$string['widget_tasks_week'] = 'משימות השבוע';
$string['widget_tasks_overdue'] = 'משימות באיחור';
$string['widget_atrisk_students'] = 'תלמידים בסיכון';
$string['widget_open_requests'] = 'פניות שירות פתוחות';
$string['widget_quick_access'] = 'גישה מהירה';

// Tasks.
$string['tasks'] = 'משימות';
$string['task'] = 'משימה';
$string['newtask'] = 'משימה חדשה';
$string['edittask'] = 'עריכת משימה';
$string['deletetask'] = 'מחיקת משימה';
$string['tasktitle'] = 'כותרת';
$string['taskdescription'] = 'תיאור';
$string['taskassigntype'] = 'סוג שיוך';
$string['taskassigntype_student'] = 'תלמיד';
$string['taskassigntype_class'] = 'כיתה';
$string['taskassigntype_general'] = 'כללי';
$string['taskassignee'] = 'אחראי';
$string['taskduedate'] = 'תאריך יעד';
$string['taskpriority'] = 'עדיפות';
$string['taskpriority_low'] = 'נמוכה';
$string['taskpriority_medium'] = 'בינונית';
$string['taskpriority_high'] = 'גבוהה';
$string['taskstatus'] = 'סטטוס';
$string['taskstatus_open'] = 'פתוח';
$string['taskstatus_inprogress'] = 'בטיפול';
$string['taskstatus_completed'] = 'הושלם';
$string['taskstatus_cancelled'] = 'בוטל';
$string['taskrecurring'] = 'משימה חוזרת';
$string['taskrecurringfreq'] = 'תדירות';
$string['taskrecurringfreq_daily'] = 'יומי';
$string['taskrecurringfreq_weekly'] = 'שבועי';
$string['taskrecurringfreq_monthly'] = 'חודשי';
$string['taskrecurringday'] = 'יום';
$string['taskrecurringend'] = 'תאריך סיום';
$string['taskcourse'] = 'קורס קשור';
$string['taskactivity'] = 'פעילות קשורה';
$string['tasktags'] = 'תגיות';
$string['taskcreated'] = 'המשימה נוצרה בהצלחה';
$string['taskupdated'] = 'המשימה עודכנה בהצלחה';
$string['taskdeleted'] = 'המשימה נמחקה בהצלחה';
$string['taskmarkcomplete'] = 'סמן כהושלם';
$string['tasksendreminder'] = 'שלח תזכורת';
$string['tasksharetask'] = 'שתף משימה';
$string['tasksharedwith'] = 'משותף עם';

// Student card.
$string['studentcard'] = 'כרטיס תלמיד';
$string['studentinfo'] = 'פרטי תלמיד';
$string['studentclasses'] = 'כיתות';
$string['studentcourses'] = 'קורסים פעילים';
$string['studentprogress'] = 'מצב לימודים';
$string['studentcompletionrate'] = 'אחוז השלמה';
$string['studentlastactivity'] = 'פעילות אחרונה';
$string['studentopentasks'] = 'משימות פתוחות';
$string['studentrequests'] = 'פניות שירות';
$string['studentmessage'] = 'שלח הודעה';
$string['studentcreatetask'] = 'צור משימה';
$string['studentcreaterequest'] = 'צור פנייה';
$string['studentviewcourses'] = 'צפה בקורסים';

// Class card.
$string['classcard'] = 'כרטיס כיתה';
$string['classinfo'] = 'פרטי כיתה';
$string['classstudents'] = 'תלמידים';
$string['classgroups'] = 'קבוצות';
$string['classstatus'] = 'סטטוס פעילות';
$string['classatrisk'] = 'תלמידים בסיכון';
$string['classevents'] = 'אירועים קרובים';
$string['classopentasks'] = 'משימות פתוחות';
$string['classmessageall'] = 'שלח הודעה לכל התלמידים';
$string['classmessagegroup'] = 'שלח הודעה לקבוצה';
$string['classcreatetask'] = 'צור משימה לכיתה';
$string['classviewrequests'] = 'צפה בפניות שירות';

// Service requests.
$string['servicerequests'] = 'פניות שירות';
$string['servicerequest'] = 'פניית שירות';
$string['newrequest'] = 'פנייה חדשה';
$string['editrequest'] = 'עריכת פנייה';
$string['requesttype'] = 'סוג פנייה';
$string['requesttype_academic'] = 'אקדמי';
$string['requesttype_administrative'] = 'מנהלי';
$string['requesttype_technical'] = 'טכני';
$string['requesttype_other'] = 'אחר';
$string['requestdescription'] = 'תיאור';
$string['requeststatus'] = 'סטטוס';
$string['requeststatus_open'] = 'פתוח';
$string['requeststatus_inprogress'] = 'בטיפול';
$string['requeststatus_resolved'] = 'טופל';
$string['requeststatus_closed'] = 'סגור';
$string['requestassignee'] = 'מטפל';
$string['requestinternalnotes'] = 'הערות פנימיות';
$string['requesthistory'] = 'היסטוריה';
$string['requestcreated'] = 'הפנייה נוצרה בהצלחה';
$string['requestupdated'] = 'הפנייה עודכנה בהצלחה';

// Alerts.
$string['alerts'] = 'התראות';
$string['alert'] = 'התראה';
$string['alerttype'] = 'סוג התראה';
$string['alerttype_no_activity'] = 'אין פעילות';
$string['alerttype_no_completion'] = 'אין השלמות';
$string['alerttype_low_grade'] = 'ציון נמוך';
$string['alertstatus'] = 'סטטוס';
$string['alertstatus_active'] = 'פעיל';
$string['alertstatus_acknowledged'] = 'נצפה';
$string['alertstatus_resolved'] = 'טופל';
$string['alertacknowledge'] = 'סמן כנצפה';
$string['alertresolve'] = 'סמן כטופל';
$string['alertcreatetask'] = 'צור משימת מעקב';
$string['alertsendmessage'] = 'שלח הודעה';

// Filters.
$string['filterclass'] = 'סינון לפי כיתה';
$string['filterstudent'] = 'סינון לפי תלמיד';
$string['filterdaterange'] = 'טווח תאריכים';
$string['filterfrom'] = 'מתאריך';
$string['filterto'] = 'עד תאריך';
$string['filterapply'] = 'החל סינון';
$string['filterclear'] = 'נקה סינון';
$string['filterall'] = 'הכול';

// Calendar.
$string['calendar'] = 'יומן';
$string['calendarevent'] = 'אירוע ביומן';
$string['calendarview'] = 'צפה ביומן';

// Messages.
$string['sendmessage'] = 'שלח הודעה';
$string['messagesubject'] = 'נושא';
$string['messagebody'] = 'תוכן ההודעה';
$string['messagesent'] = 'ההודעה נשלחה בהצלחה';
$string['messageerror'] = 'שגיאה בשליחת ההודעה';

// Settings.
$string['settings'] = 'הגדרות';
$string['settings_general'] = 'הגדרות כלליות';
$string['settings_alerts'] = 'הגדרות התראות';
$string['settings_alerts_desc'] = 'הגדר מתי יופעלו התראות עבור תלמידים בסיכון.';
$string['settings_inactivity_days'] = 'סף חוסר פעילות (ימים)';
$string['settings_inactivity_days_desc'] = 'מספר ימים ללא פעילות לפני הפעלת התראה.';
$string['settings_completion_threshold'] = 'סף השלמה (%)';
$string['settings_completion_threshold_desc'] = 'אחוז השלמה מינימלי לפני הפעלת התראה.';

// Errors.
$string['error_nopermission'] = 'אין לך הרשאה לגשת לדף זה.';
$string['error_notfound'] = 'הפריט המבוקש לא נמצא.';
$string['error_invaliddata'] = 'נתונים לא תקינים.';

// Misc.
$string['confirm'] = 'אישור';
$string['cancel'] = 'ביטול';
$string['save'] = 'שמור';
$string['delete'] = 'מחק';
$string['edit'] = 'ערוך';
$string['view'] = 'צפה';
$string['create'] = 'צור';
$string['close'] = 'סגור';
$string['actions'] = 'פעולות';
$string['nodata'] = 'אין נתונים להצגה';
$string['loading'] = 'טוען...';
