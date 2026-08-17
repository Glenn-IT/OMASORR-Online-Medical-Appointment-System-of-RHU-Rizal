<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('admin');

$admin = getAdminSession();

$adminFullName = isset($admin['full_name'])
    ? $admin['full_name']
    : 'Administrator';
$adminName = htmlspecialchars($adminFullName);
$initial = strtoupper(substr($adminFullName, 0, 1));
$csrf = csrfField();
$successMsg = '';
$successType = 'success';
$errorMsg = '';
$errorType = 'danger';
/* Flash messages */
if (function_exists('getFlash')) {
    $successFlash = getFlash('user_success');
    if (is_array($successFlash)) {
        $successMsg = isset($successFlash[0]) ? $successFlash[0] : '';
        $successType = isset($successFlash[1]) ? $successFlash[1] : 'success';
    }
    $errorFlash = getFlash('user_error');
    if (is_array($errorFlash)) {
        $errorMsg = isset($errorFlash[0]) ? $errorFlash[0] : '';
        $errorType = isset($errorFlash[1]) ? $errorFlash[1] : 'danger';
    }
}
/* Get users */
$users = array();
try {
    $pdo = db();

    $sql = "
        SELECT
            u.id,
            u.username,
            u.status,
            u.created_at,
            p.patient_no,
            p.full_name,
            p.email
        FROM users u
        INNER JOIN patients p ON p.user_id = u.id
        ORDER BY p.full_name ASC
    ";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = 'Unable to load users from the database.';
    error_log(
        'Users page error: ' . $e->getMessage()
    );
}
/* User statistics */
$totalUsers = count($users);
$activeUsers = 0;
$inactiveUsers = 0;
foreach ($users as $user) {
    if (
        isset($user['status']) &&
        $user['status'] === 'Active'
    ) {
        $activeUsers++;
    } else {
        $inactiveUsers++;
    }
}
$pageTitle = 'Manage Users – RHU Rizal Admin';

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div
                class="topbar-left"
                style="display:flex;align-items:center;gap:12px;"
            >
                <button
                    type="button"
                    class="menu-toggle"
                    onclick="
                        document.getElementById('sidebar').classList.toggle('open');
                        document.getElementById('sidebarOverlay').classList.toggle('show');
                    "
                >
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h4>Manage Users</h4>
                    <p>Activate or deactivate system user accounts</p>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-user">
                    <div class="avatar">
                        <?= $initial ?>
                    </div>
                    <span class="user-name">
                        <?= $adminName ?>
                    </span>
                </div>
            </div>
        </header>
        <!-- Page Content -->
        <div class="page-content">
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-<?= htmlspecialchars($successType) ?> alert-dismissible mb-3">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= htmlspecialchars($successMsg) ?>
                    <button
                        type="button"
                        class="alert-close"
                        onclick="this.parentElement.remove()"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-<?= htmlspecialchars($errorType) ?> alert-dismissible mb-3">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($errorMsg) ?>
                    <button
                        type="button"
                        class="alert-close"
                        onclick="this.parentElement.remove()"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>
 <!-- Statistics -->
            <div class="grid-3 mb-3">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="value">
                            <?= $totalUsers ?>
                        </div>
                        <div class="label">
                            Total Users
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="value">
                            <?= $activeUsers ?>
                        </div>
                        <div class="label">
                            Active
                        </div>
                    </div>
                </div>
                <div class="stat-card">

                    <div class="stat-icon danger">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <div class="stat-info">
                        <div class="value">
                            <?= $inactiveUsers ?>
                        </div>
                        <div class="label">
                            Inactive
                        </div>
                    </div>
                </div>
            </div>
            <!-- Users Card -->
            <div class="card">
                <div class="card-header">
                    <h5>
                        <i class="fa-solid fa-users"></i>
                        User Accounts
                    </h5>
                    <div class="flex-gap">
                        <select
                            class="form-select"
                            id="statusFilter"
                            style="width:160px;"
                        >
                            <option value="All">
                                All Status
                            </option>

                            <option value="Active">
                                Active
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>
                        </select>
                        <div
                            class="search-bar"
                            style="width:220px;"
                        >
                            <i class="fa-solid fa-search"></i>
                            <input
                                type="text"
                                id="searchUser"
                                placeholder="Search users..."
                                autocomplete="off"
                            >
                        </div>
                    </div>
                </div
><!-- Users Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Patient No</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-users-slash"></i>
                                            <p> No users found </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $userId = isset($user['id'])
                                        ? (int)$user['id']
                                        : 0;
                                    $username = isset($user['username'])
                                        ? $user['username']
                                        : '';
                                    $status = isset($user['status'])
                                        ? $user['status']
                                        : 'Inactive';
                                    $fullName = isset($user['full_name'])
                                        ? $user['full_name']
                                        : 'Unknown User';
                                    $email = isset($user['email'])
                                        ? $user['email']
                                        : '';
                                    $patientNo = isset($user['patient_no'])
                                        ? $user['patient_no']
                                        : '—';
                                    $createdAt = isset($user['created_at'])
                                        ? $user['created_at']
                                        : '';
                                    $isActive = ($status === 'Active');
                                    $nameInitial = !empty($fullName)
                                        ? strtoupper(substr($fullName, 0, 1))
                                        : '?';
                                    $searchText = strtolower(
                                        $fullName . ' ' .
                                        $username . ' ' .
                                        $email . ' ' .
                                        $patientNo
                                    );

                                    ?>
                                    <tr
                                        data-status="<?= htmlspecialchars($status) ?>"
                                        data-search="<?= htmlspecialchars($searchText) ?>"
                                    >
                                        <td>
                                            <span
                                                style="
                                                    font-weight:600;
                                                    color:var(--primary);
                                                "
                                            >
                                                <?= htmlspecialchars($patientNo) ?>
                                            </span>

                                        </td>
                                        <td>
                                            <div
                                                style="
                                                    display:flex;
                                                    align-items:center;
                                                    gap:10px;
                                                "
                                            >
                                                <div
                                                    style="
                                                        width:34px;
                                                        height:34px;
                                                        border-radius:50%;
                                                        display:flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        font-weight:700;
                                                        font-size:14px;
                                                        flex-shrink:0;
                                                        background:<?= $isActive
                                                            ? 'var(--primary-light)'
                                                            : 'var(--gray-200)' ?>;
                                                        color:<?= $isActive
                                                            ? 'var(--primary)'
                                                            : 'var(--gray-400)' ?>;
                                                    "
                                                >
                                                    <?= htmlspecialchars($nameInitial) ?>
                                                </div>

                                                <span style="font-weight:500;">
                                                    <?= htmlspecialchars($fullName) ?>
                                                </span>

                                            </div>
                                        </td>
                                        <td>
                                            <code>
                                                <?= htmlspecialchars($username) ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?= !empty($email)
                                                ? htmlspecialchars($email)
                                                : '—'
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($createdAt)): ?>
                                                <span
                                                    class="fmt-date"
                                                    data-date="<?= htmlspecialchars($createdAt) ?>"
                                                >
                                                    <?= htmlspecialchars($createdAt) ?>
                                                </span>

                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div
                                                style="
                                                    display:flex;
                                                    align-items:center;
                                                    gap:6px;
                                                "
                                            >
                                                <span
                                                    class="status-dot <?= $isActive
                                                        ? 'active'
                                                        : 'inactive' ?>"
                                                ></span>
                                                <span
                                                    class="status-badge-wrap"
                                                    data-status="<?= htmlspecialchars($status) ?>"
                                                >
                                                    <?= htmlspecialchars($status) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <form
                                                    method="POST"
                                                    action="<?= BASE_URL ?>/actions/admin/toggle-user-status.php"
                                                    style="display:inline;"
                                                >
                                                    <?= $csrf ?>
                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?= $userId ?>"
                                                    >
                                                    <?php if ($isActive): ?>
                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-warning"
                                                            onclick="return confirm('Are you sure you want to deactivate this user?');"
                                                        >
                                                            <i class="fa-solid fa-user-slash"></i>
                                                            Deactivate
                                                        </button>
                                                    <?php else: ?>
                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-success"
                                                            onclick="return confirm('Are you sure you want to activate this user?');"
                                                        >
                                                            <i class="fa-solid fa-user-check"></i>
                                                            Activate
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                  <?php endforeach; ?>
             <?php endif; ?>
         </tbody>

      </table>
   </div>
 </div>
 </div>
 </div>
</div>
<?php
$extraScripts = <<<'SCRIPTS'
<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('searchUser');
    var statusFilter = document.getElementById('statusFilter');
    function filterUsers() {
        var search = searchInput.value.toLowerCase().trim();
        var selectedStatus = statusFilter.value;
        var rows = document.querySelectorAll(
            '#usersTable tr[data-status]'
        );
        rows.forEach(function (row) {
            var status = row.getAttribute('data-status') || '';
            var searchText = row.getAttribute('data-search') || '';
            var statusMatch =
                selectedStatus === 'All' ||
                status === selectedStatus;
            var searchMatch =
                search === '' ||
                searchText.indexOf(search) !== -1;
            row.style.display =
                statusMatch && searchMatch ? '' : 'none';
        });
    }
    if (searchInput) {
        searchInput.addEventListener('input', filterUsers);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterUsers);
    }
    document.querySelectorAll('.fmt-date[data-date]').forEach(function (element) {
        if (typeof formatDate === 'function') {
            element.textContent = formatDate(
                element.getAttribute('data-date')
            );
        }
    });
    document.querySelectorAll('.status-badge-wrap[data-status]').forEach(function (element) {

        if (typeof statusBadge === 'function') {
            element.innerHTML = statusBadge(
                element.getAttribute('data-status')
            );
        }
    });
});
</script>
SCRIPTS;

require_once __DIR__ . '/../../includes/footer.php';
?>