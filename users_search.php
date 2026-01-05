<?php
defined('MOODLE_INTERNAL') || die();

global $DB, $OUTPUT;

?>

<div class="users-search">
    <div class="form-group">
        <label for="userSearch"><?php echo get_string('search_users', 'local_academic_dashboard'); ?></label>
        <input type="text" class="form-control" id="userSearch" placeholder="<?php echo get_string('search_users', 'local_academic_dashboard'); ?>">
    </div>
    
    <div id="searchResults" class="mt-3"></div>
</div>

<script>
// Load all users into JavaScript
const allUsers = <?php 
    $users = $DB->get_records_sql("SELECT id, firstname, lastname, email FROM {user} WHERE deleted = 0 AND suspended = 0 ORDER BY lastname, firstname");
    echo json_encode(array_values($users)); 
?>;

const searchInput = document.getElementById('userSearch');
const resultsDiv = document.getElementById('searchResults');

searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    
    if (query.length === 0) {
        resultsDiv.innerHTML = '';
        return;
    }
    
    // Filter users
    const filtered = allUsers.filter(user => 
        user.firstname.toLowerCase().includes(query) || 
        user.lastname.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query)
    );
    
    // Display results
    if (filtered.length > 20) {
        resultsDiv.innerHTML = '<div class="alert alert-info"><?php echo get_string('too_many_results', 'local_academic_dashboard'); ?></div>';
    } else if (filtered.length === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-warning"><?php echo get_string('no_results', 'local_academic_dashboard'); ?></div>';
    } else {
        let html = '<div class="list-group">';
        filtered.forEach(user => {
            html += `<a href="user.php?id=${user.id}" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-center">
                    <span>${user.firstname} ${user.lastname}</span>
                    <a href="#" class="badge badge-primary" onclick="openEmailModal('${user.email}'); return false;">
                        <i class="fa fa-envelope"></i> ${user.email}
                    </a>
                </div>
            </a>`;
        });
        html += '</div>';
        resultsDiv.innerHTML = html;
    }
});

function openEmailModal(email) {
    // TODO: Implement email modal
    alert('Send email to: ' + email);
}
</script>
