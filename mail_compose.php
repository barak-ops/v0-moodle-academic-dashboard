<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/academic_dashboard/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

require_login();
require_capability('local/academic_dashboard:view', context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$to = optional_param('to', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/local/academic_dashboard/mail_compose.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('popup');

// Handle send action
if ($action === 'send' && confirm_sesskey()) {
    $to_recipients = required_param('to_recipients', PARAM_TEXT);
    $cc_recipients = optional_param('cc_recipients', '', PARAM_TEXT);
    $subject = required_param('subject', PARAM_TEXT);
    $content = required_param('content', PARAM_RAW);
    
    // Parse recipients
    $to_emails = array_filter(array_map('trim', explode(',', $to_recipients)));
    $cc_emails = array_filter(array_map('trim', explode(',', $cc_recipients)));
    
    // Check if local_mail is installed
    if (file_exists($CFG->dirroot . '/local/mail/lib.php')) {
        require_once($CFG->dirroot . '/local/mail/lib.php');
        
        // Use local_mail plugin
        foreach ($to_emails as $email) {
            $recipient = $DB->get_record('user', ['email' => $email]);
            if ($recipient) {
                // Create mail using local_mail
                // Note: This is a simplified example, actual implementation depends on local_mail API
                $message = new stdClass();
                $message->userfrom = $USER->id;
                $message->userto = $recipient->id;
                $message->subject = $subject;
                $message->fullmessage = $content;
                $message->timecreated = time();
                
                // Send via Moodle messaging
                message_send($message);
            }
        }
    } else {
        // Use standard Moodle messaging
        foreach ($to_emails as $email) {
            $recipient = $DB->get_record('user', ['email' => $email]);
            if ($recipient) {
                $message = new \core\message\message();
                $message->component = 'local_academic_dashboard';
                $message->name = 'notification';
                $message->userfrom = $USER;
                $message->userto = $recipient;
                $message->subject = $subject;
                $message->fullmessage = $content;
                $message->fullmessageformat = FORMAT_HTML;
                $message->fullmessagehtml = $content;
                $message->smallmessage = $subject;
                $message->notification = 0;
                
                message_send($message);
            }
        }
    }
    
    echo json_encode(['success' => true]);
    die();
}

echo $OUTPUT->header();

$course = null;
$context = null;
$groups = [];
$default_recipients = [];

if ($userid > 0) {
    $recipient = $DB->get_record('user', ['id' => $userid], 'id, email, firstname, lastname');
    if ($recipient) {
        $default_recipients[] = ['email' => $recipient->email, 'name' => fullname($recipient)];
    }
} elseif ($to) {
    // Handle email address directly
    $default_recipients[] = ['email' => $to, 'name' => $to];
} elseif ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid]);
    $context = context_course::instance($courseid);
    $groups = groups_get_all_groups($courseid);
    
    if ($groupid > 0) {
        $members = groups_get_members($groupid);
        foreach ($members as $member) {
            $default_recipients[] = ['email' => $member->email, 'name' => fullname($member)];
        }
    } else {
        $sql = "SELECT DISTINCT u.id, u.email, u.firstname, u.lastname
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE e.courseid = ? AND u.deleted = 0 AND ue.status = 0
                ORDER BY u.lastname, u.firstname";
        
        $students = $DB->get_records_sql($sql, [$courseid]);
        foreach ($students as $student) {
            $isteacher = has_capability('moodle/course:update', $context, $student->id);
            if (!$isteacher) {
                $default_recipients[] = ['email' => $student->email, 'name' => fullname($student)];
            }
        }
    }
}

?>

<style>
.email-composer {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.email-composer .close-btn {
    position: absolute;
    left: 10px;
    top: 10px;
    font-size: 24px;
    cursor: pointer;
    background: none;
    border: none;
    color: #666;
}

.email-composer .close-btn:hover {
    color: #000;
}

.email-composer .form-group {
    margin-bottom: 20px;
}

.email-composer label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.email-composer input[type="text"],
.email-composer textarea,
.email-composer select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: inherit;
    height: 40px;
}

.email-composer textarea {
    min-height: 200px;
    resize: vertical;
}

.email-composer .btn-group {
    margin-top: 10px;
}

.email-composer .btn-group button {
    margin-right: 5px;
}

.recipient-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-height: 40px;
}

.recipient-tag {
    background: #007bff;
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.recipient-tag .remove {
    cursor: pointer;
    font-weight: bold;
}

.cc-options {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.recipient-input-wrapper {
    position: relative;
    width: 100%;
}

.recipient-input {
    width: 100%;
    padding: 8px;
    border: none;
    outline: none;
    font-size: 14px;
}

.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.autocomplete-item {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.autocomplete-item:hover {
    background: #f8f9fa;
}

.autocomplete-item.selected {
    background: #e9ecef;
}
</style>

<div class="email-composer">
    <button type="button" class="close-btn" onclick="window.close();">
        <i class="fa fa-times"></i>
    </button>
    
    <h3><?php echo get_string('send_email', 'local_academic_dashboard'); ?></h3>
    
    <form id="emailForm" method="post">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        <input type="hidden" name="action" value="send">
        <input type="hidden" name="to_recipients" id="toRecipientsInput">
        <input type="hidden" name="cc_recipients" id="ccRecipientsInput">
        
        <?php if ($courseid && $course && count($groups) > 0): ?>
        <div class="form-group">
            <label><?php echo get_string('select_group', 'local_academic_dashboard'); ?></label>
            <select id="groupFilterSelect" class="form-control" onchange="filterByGroup()">
                <option value="0"><?php echo get_string('all_students', 'local_academic_dashboard'); ?></option>
                <?php foreach ($groups as $group): ?>
                <option value="<?php echo $group->id; ?>" <?php echo ($groupid == $group->id) ? 'selected' : ''; ?>>
                    <?php echo format_string($group->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label><?php echo get_string('email_to', 'local_academic_dashboard'); ?></label>
            <div class="recipient-tags" id="toRecipients">
                <!-- Added input field for autocomplete -->
                <div class="recipient-input-wrapper">
                    <input type="text" class="recipient-input" id="toInput" 
                           placeholder="<?php echo get_string('type_to_search', 'local_academic_dashboard'); ?>">
                    <div class="autocomplete-dropdown" id="toDropdown"></div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label><?php echo get_string('email_cc', 'local_academic_dashboard'); ?></label>
            <div class="recipient-tags" id="ccRecipients">
                <!-- Added input field for autocomplete -->
                <div class="recipient-input-wrapper">
                    <input type="text" class="recipient-input" id="ccInput" 
                           placeholder="<?php echo get_string('type_to_search', 'local_academic_dashboard'); ?>">
                    <div class="autocomplete-dropdown" id="ccDropdown"></div>
                </div>
            </div>
            
            <?php if ($courseid && $course): ?>
            <div class="cc-options">
                <h5><?php echo get_string('email_cc_options', 'local_academic_dashboard'); ?></h5>
                
                <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addTeachers()">
                    <?php echo get_string('email_cc_teachers', 'local_academic_dashboard'); ?>
                </button>
                
                <div class="form-group mt-2">
                    <label><?php echo get_string('email_cc_specific', 'local_academic_dashboard'); ?></label>
                    <select id="userSelect" class="form-control">
                        <option value=""><?php echo get_string('select_user', 'local_academic_dashboard'); ?></option>
                        <?php
                        $users = get_enrolled_users($context);
                        foreach ($users as $user):
                        ?>
                        <option value="<?php echo $user->email; ?>" data-name="<?php echo fullname($user); ?>">
                            <?php echo fullname($user); ?> (<?php echo $user->email; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="addSpecificUser()">
                        <?php echo get_string('email_cc_add_user', 'local_academic_dashboard'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label><?php echo get_string('email_subject', 'local_academic_dashboard'); ?></label>
            <input type="text" name="subject" id="subject" value="<?php echo get_string('email_default_subject', 'local_academic_dashboard'); ?>" required>
        </div>
        
        <div class="form-group">
            <label><?php echo get_string('email_content', 'local_academic_dashboard'); ?></label>
            <textarea name="content" id="content" required></textarea>
        </div>
        
        <div class="form-group text-right">
            <button type="button" class="btn btn-secondary" onclick="window.close();">
                <?php echo get_string('cancel'); ?>
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-paper-plane"></i> <?php echo get_string('email_send', 'local_academic_dashboard'); ?>
            </button>
        </div>
    </form>
</div>

<script>
const toRecipients = new Set();
const ccRecipients = new Set();

const defaultRecipients = <?php echo json_encode($default_recipients); ?>;

<?php if ($courseid && $course): ?>
const courseId = <?php echo $courseid; ?>;
const courseUsers = <?php echo json_encode(array_values(array_map(function($u) use ($context) {
    return [
        'email' => $u->email, 
        'name' => fullname($u), 
        'isteacher' => has_capability('moodle/course:update', $context, $u->id)
    ];
}, get_enrolled_users($context)))); ?>;

const groups = <?php echo json_encode(array_map(function($g) use ($courseid, $context) {
    $members = groups_get_members($g->id);
    return [
        'id' => $g->id,
        'name' => $g->name,
        'members' => array_values(array_map(function($m) use ($context) {
            return [
                'email' => $m->email, 
                'name' => fullname($m),
                'isteacher' => has_capability('moodle/course:update', $context, $m->id)
            ];
        }, $members))
    ];
}, array_values($groups))); ?>;
<?php endif; ?>

const autocompleteState = {
    toIndex: -1,
    ccIndex: -1
};

window.addEventListener('DOMContentLoaded', function() {
    window.recipientNames = {};
    
    defaultRecipients.forEach(recipient => {
        window.recipientNames[recipient.email] = recipient.name;
        toRecipients.add(recipient.email);
    });
    
    <?php if ($to): ?>
    toRecipients.add('<?php echo addslashes($to); ?>');
    window.recipientNames['<?php echo addslashes($to); ?>'] = '<?php echo addslashes($to); ?>';
    <?php endif; ?>
    
    // Render recipients
    renderRecipients('to');
    renderRecipients('cc');
    
    // Initialize autocomplete
    initAutocomplete('toInput', 'toDropdown', 'to');
    initAutocomplete('ccInput', 'ccDropdown', 'cc');
    
    updateHiddenInputs();
});

function addRecipient(type, email, name) {
    const set = type === 'to' ? toRecipients : ccRecipients;
    
    if (!email || set.has(email)) return;
    
    set.add(email);
    
    // Store name mapping
    if (!window.recipientNames) {
        window.recipientNames = {};
    }
    window.recipientNames[email] = name;
    
    renderRecipients(type);
    updateHiddenInputs();
}

function renderRecipients(type) {
    const set = type === 'to' ? toRecipients : ccRecipients;
    const container = document.getElementById(type === 'to' ? 'toRecipients' : 'ccRecipients');
    const inputId = type === 'to' ? 'toInput' : 'ccInput';
    const dropdownId = type === 'to' ? 'toDropdown' : 'ccDropdown';
    
    container.innerHTML = '';
    
    // Add recipient tags
    set.forEach(email => {
        const tag = document.createElement('div');
        tag.className = 'recipient-tag';
        const displayName = window.recipientNames && window.recipientNames[email] ? window.recipientNames[email] : email;
        tag.innerHTML = `
            <span>${displayName}</span>
            <span class="remove" onclick="removeRecipient('${type}', '${email.replace(/'/g, "\\'")}')">&times;</span>
        `;
        container.appendChild(tag);
    });
    
    // Re-add input field
    const inputWrapper = document.createElement('div');
    inputWrapper.className = 'recipient-input-wrapper';
    inputWrapper.innerHTML = `
        <input type="text" class="recipient-input" id="${inputId}" 
               placeholder="<?php echo get_string('type_to_search', 'local_academic_dashboard'); ?>">
        <div class="autocomplete-dropdown" id="${dropdownId}"></div>
    `;
    container.appendChild(inputWrapper);
    
    // Re-initialize autocomplete for the new input
    setTimeout(() => {
        initAutocomplete(inputId, dropdownId, type);
    }, 0);
}

function removeRecipient(type, email) {
    const set = type === 'to' ? toRecipients : ccRecipients;
    set.delete(email);
    
    renderRecipients(type);
    updateHiddenInputs();
}

function updateHiddenInputs() {
    document.getElementById('toRecipientsInput').value = Array.from(toRecipients).join(',');
    document.getElementById('ccRecipientsInput').value = Array.from(ccRecipients).join(',');
}

<?php if ($courseid && $course): ?>
function filterByGroup() {
    const groupId = document.getElementById('groupFilterSelect').value;
    const url = new URL(window.location.href);
    url.searchParams.set('groupid', groupId);
    window.location.href = url.toString();
}

function addTeachers() {
    courseUsers.forEach(user => {
        if (user.isteacher) {
            addRecipient('cc', user.email, user.name);
        }
    });
}

function addSpecificUser() {
    const select = document.getElementById('userSelect');
    const email = select.value;
    const name = select.options[select.selectedIndex].dataset.name;
    
    if (email) {
        addRecipient('cc', email, name);
        select.value = '';
    }
}
<?php endif; ?>

function initAutocomplete(inputId, dropdownId, recipientType) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        if (query.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        // Filter users based on context
        const filteredUsers = filterUsersByContext(query);
        
        if (filteredUsers.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        // Display filtered users
        dropdown.innerHTML = '';
        filteredUsers.forEach((user, index) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `<strong>${user.name}</strong><br><small>${user.email}</small>`;
            item.dataset.email = user.email;
            item.dataset.name = user.name;
            item.dataset.index = index;
            
            item.addEventListener('click', function() {
                addRecipientFromAutocomplete(recipientType, user.email, user.name);
                input.value = '';
                dropdown.style.display = 'none';
            });
            
            dropdown.appendChild(item);
        });
        
        dropdown.style.display = 'block';
        autocompleteState[recipientType + 'Index'] = -1;
    });
    
    // Keyboard navigation
    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        const currentIndex = autocompleteState[recipientType + 'Index'];
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (currentIndex < items.length - 1) {
                const newIndex = currentIndex + 1;
                updateSelectedItem(items, newIndex, recipientType);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (currentIndex > 0) {
                const newIndex = currentIndex - 1;
                updateSelectedItem(items, newIndex, recipientType);
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentIndex >= 0 && currentIndex < items.length) {
                const item = items[currentIndex];
                addRecipientFromAutocomplete(recipientType, item.dataset.email, item.dataset.name);
                input.value = '';
                dropdown.style.display = 'none';
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            autocompleteState[recipientType + 'Index'] = -1;
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

function updateSelectedItem(items, newIndex, recipientType) {
    items.forEach(item => item.classList.remove('selected'));
    if (newIndex >= 0 && newIndex < items.length) {
        items[newIndex].classList.add('selected');
        items[newIndex].scrollIntoView({ block: 'nearest' });
    }
    autocompleteState[recipientType + 'Index'] = newIndex;
}

function filterUsersByContext(query) {
    let availableUsers = [];
    
    <?php if ($courseid && $course): ?>
    availableUsers = courseUsers.filter(user => {
        const nameMatch = user.name.toLowerCase().startsWith(query);
        const emailMatch = user.email.toLowerCase().startsWith(query);
        const notAlreadyAdded = !toRecipients.has(user.email) && !ccRecipients.has(user.email);
        return (nameMatch || emailMatch) && notAlreadyAdded;
    });
    <?php else: ?>
    // No context - return empty (can be extended for global search)
    availableUsers = [];
    <?php endif; ?>
    
    return availableUsers;
}

function addRecipientFromAutocomplete(type, email, name) {
    addRecipient(type, email, name);
    
    // Re-render recipients to include input field
    renderRecipients(type);
}

document.getElementById('emailForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (toRecipients.size === 0) {
        alert('<?php echo get_string('email_no_recipients', 'local_academic_dashboard'); ?>');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('mail_compose.php?courseid=<?php echo $courseid; ?>&groupid=<?php echo $groupid; ?>&userid=<?php echo $userid; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('<?php echo get_string('email_sent_success', 'local_academic_dashboard'); ?>');
            window.close();
        } else {
            alert('<?php echo get_string('email_sent_error', 'local_academic_dashboard'); ?>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?php echo get_string('email_sent_error', 'local_academic_dashboard'); ?>');
    });
});
</script>

<?php
echo $OUTPUT->footer();
?>
