<?php
// Get branch name for navbar display
$branch_name = 'Your Branch';
if (isset($_SESSION['branch_admin_branch_id'])) {
    $stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
    $stmt->execute([$_SESSION['branch_admin_branch_id']]);
    $nav_branch = $stmt->fetch();
    if ($nav_branch) {
        $branch_name = $nav_branch['name'];
    }
}
?>
<nav class="top-navbar">
    <div class="navbar-left">
        <h2 class="navbar-title">
            <?php echo $page_title ?? 'Branch Admin'; ?>
        </h2>
        <span class="branch-badge">🏥
            <?php echo h($branch_name); ?>
        </span>
    </div>
    <div class="navbar-right">
        <span class="navbar-user">👤
            <?php echo h($_SESSION['user_name']); ?>
        </span>
    </div>
</nav>
<style>
    .branch-badge {
        background: #dbeafe;
        color: #1e40af;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 500;
        margin-left: 12px;
    }
</style>