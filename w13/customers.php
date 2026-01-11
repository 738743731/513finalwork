<?php
session_start();
require_once 'includes/auth.php';
checkAuthentication();
require_once 'includes/database.php';

$page_title = "Customer List";
require_once 'includes/header.php';

$db = new Database();
$conn = $db->getConnection();

// Connect to your existing database with wp9k_fc_subscribers table
$existing_db = new mysqli('your_host', 'your_user', 'your_password', 'your_existing_db');

$sql = "SELECT * FROM wp9k_fc_subscribers";
$result = $existing_db->query($sql);
?>

<div class="page-header">
    <h1>Registered Customers</h1>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Hash</th>
                <th>Contact Owner</th>
                <th>Company ID</th>
                <th>Prefix</th>
                <th>Last Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['userid'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['hash'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['contact_owner'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['company_id'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['prefix'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['lastname'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>