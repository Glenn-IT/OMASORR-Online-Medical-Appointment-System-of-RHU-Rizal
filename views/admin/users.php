<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin('admin');
$admin     = getAdminSession();
$adminName = htmlspecialchars($admin['full_name']);
$initial   = strtoupper($adminName[0]);
$csrf      = csrfField();

$pdo = db();

$users = $pdo->query("
    SELECT u.id, u.username, u.status, u.created_at,
           p.patient_no, p.full_name, p.email
    FROM users u
    JOIN patients p ON p.user_id = u.id
    ORDER BY p.full_name ASC
")->fetchAll();

$totalUsers    = count($users);
$activeUsers   = count(array_filter($users, fn($u) => $u['status'] === 'Active'));
$inactiveUsers = $totalUsers - $activeUsers;

[$successMsg, $successType] = getFlash('user_success');
[$errorMsg,   $errorType]   = getFlash('user_error');

$pageTitle = 'Manage Users – RHU Rizal Admin';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
  <?php require_once __DIR__ . '/../../includes/admin-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
        <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div>
          <h4>Manage Users</h4>
          <p>Activate or deactivate system user accounts</p>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-user">
          <div class="avatar"><?= $initial ?></div>
          <span class="user-name"><?= $adminName ?></span>
        </div>
      </div>
    </header>

    <div class="page-content">
      <?php if ($successMsg): ?>
      <div class="alert alert-<?= $successType ?> alert-dismissible mb-3">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?>
        <button class="alert-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
      <div class="alert alert-<?= $errorType ?> alert-dismissible mb-3">
        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?>
        <button class="alert-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <?php endif; ?>

      <div class="grid-3 mb-3">
        <div class="stat-card">
          <div class="stat-icon primary"><i class="fa-solid fa-users"></i></div>
          <div class="stat-info"><div class="value"><?= $totalUsers ?></div><div class="label">Total Users</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon success"><i class="fa-solid fa-user-check"></i></div>
          <div class="stat-info"><div class="value"><?= $activeUsers ?></div><div class="label">Active</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon danger"><i class="fa-solid fa-user-slash"></i></div>
          <div class="stat-info"><div class="value"><?= $inactiveUsers ?></div><div class="label">Inactive</div></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h5><i class="fa-solid fa-users"></i> User Accounts</h5>
          <div class="flex-gap">
            <select class="form-select" id="statusFilter" onchange="filterUsers()" style="width:160px;">
              <option value="All">All Status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
            <div class="search-bar" style="width:220px;">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="searchUser" placeholder="Search users..." oninput="filterUsers()" />
            </div>
          </div>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Patient No</th><th>Name</th><th>Username</th><th>Email</th><th>Registered</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="usersTable">
              <?php if (empty($users)): ?>
              <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-users-slash"></i><p>No users found</p></div></td></tr>
              <?php else: foreach ($users as $u): ?>
              <tr data-status="<?= htmlspecialchars($u['status']) ?>"
                  data-search="<?= htmlspecialchars(strtolower($u['full_name'].' '.$u['username'].' '.($u['email']??''))) ?>">
                <td><span style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($u['patient_no']) ?></span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:34px;height:34px;background:<?= $u['status']==='Active' ? 'var(--primary-light)' : 'var(--gray-200)' ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:<?= $u['status']==='Active' ? 'var(--primary)' : 'var(--gray-400)' ?>;font-weight:700;font-size:14px;flex-shrink:0;">
                      <?= strtoupper($u['full_name'][0]) ?>
                    </div>
                    <span style="font-weight:500;"><?= htmlspecialchars($u['full_name']) ?></span>
                  </div>
                </td>
                <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td><span class="fmt-date" data-date="<?= htmlspecialchars($u['created_at']) ?>"></span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:6px;">
                    <span class="status-dot <?= $u['status']==='Active' ? 'active' : 'inactive' ?>"></span>
                    <span class="status-badge-wrap" data-status="<?= htmlspecialchars($u['status']) ?>"></span>
                  </div>
                </td>
                <td>
                  <div class="actions">
                    <form method="post" action="<?= BASE_URL ?>/actions/admin/toggle-user-status.php" style="display:inline">
                      <?= $csrf ?>
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= $u['status']==='Active' ? 'btn-warning' : 'btn-success' ?>">
                        <i class="fa-solid fa-<?= $u['status']==='Active' ? 'user-slash' : 'user-check' ?>"></i>
                        <?= $u['status']==='Active' ? 'Deactivate' : 'Activate' ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
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
  function filterUsers() {
    const search  = document.getElementById("searchUser").value.toLowerCase();
    const statusF = document.getElementById("statusFilter").value;
    document.querySelectorAll("#usersTable tr[data-status]").forEach(row => {
      const matchStatus = statusF === "All" || row.dataset.status === statusF;
      const matchSearch = !search || row.dataset.search.includes(search);
      row.style.display = (matchStatus && matchSearch) ? "" : "none";
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".fmt-date[data-date]").forEach(el => el.textContent = formatDate(el.dataset.date));
    document.querySelectorAll(".status-badge-wrap[data-status]").forEach(el => el.innerHTML = statusBadge(el.dataset.status));
  });
</script>
SCRIPTS;
require_once __DIR__ . '/../../includes/footer.php';
?>
