<?php
defined('MOODLE_INTERNAL') || die();

global $DB, $OUTPUT;

?>

<div class="users-search">
    <div class="form-group">
        <label for="userSearch"><?php echo get_string('search_users', 'local_academic_dashboard'); ?></label>
        <input type="text" class="form-control" id="userSearch" placeholder="<?php echo get_string('search_users', 'local_academic_dashboard'); ?>">
    </div>
    
    <!-- Added table structure with styled header matching courses table -->
    <div id="searchResults" class="mt-3">
        <table class="table table-hover">
            <thead class="table-header-cyan">
                <tr>
                    <th><?php echo get_string('username', 'local_academic_dashboard'); ?></th>
                    <th><?php echo get_string('email'); ?></th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
            </tbody>
        </table>
    </div>
</div>

<script>
// Load all users into JavaScript
const allUsers = <?php 
    $users = $DB->get_records_sql("SELECT id, firstname, lastname, email FROM {user} WHERE deleted = 0 AND suspended = 0 ORDER BY lastname, firstname");
    echo json_encode(array_values($users)); 
?>;

const searchInput = document.getElementById('userSearch');
const tableBody = document.getElementById('usersTableBody');

searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    
    if (query.length === 0) {
        tableBody.innerHTML = '';
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
        tableBody.innerHTML = '<tr><td colspan="2"><div class="alert alert-info"><?php echo get_string('too_many_results', 'local_academic_dashboard'); ?></div></td></tr>';
    } else if (filtered.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="2"><div class="alert alert-warning"><?php echo get_string('no_results', 'local_academic_dashboard'); ?></div></td></tr>';
    } else {
        let html = '';
        filtered.forEach(user => {
            html += `<tr>
                <td>
                    <a href="user.php?id=${user.id}" class="course-name-link">
                        <strong>${user.firstname} ${user.lastname}</strong>
                    </a>
                </td>
                <td>
                    <a href="#" onclick="window.open('mail_compose.php?to=${encodeURIComponent(user.email)}', 'email_composer', 'width=800,height=600'); return false;">
                        <i class="fa fa-envelope"></i> ${user.email}
                    </a>
                </td>
            </tr>`;
        });
        tableBody.innerHTML = html;
    }
});
</script>
