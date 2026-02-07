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
    <div class="navbar-right" style="display: flex; align-items: center; gap: 10px;">
        <?php
        // Fetch profile image
        require_once '../includes/ProfileImageHandler.php';
        $navbarImageHandler = new ProfileImageHandler($pdo);
        $navbarProfileImage = $navbarImageHandler->getProfileImage($_SESSION['user_id']);
        ?>
        <a href="profile.php"
            style="display: block; width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #e5e7eb; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-decoration: none;">
            <?php if ($navbarProfileImage): ?>
                <img src="../<?php echo h($navbarProfileImage); ?>" alt="Profile"
                    style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <span
                    style="font-weight: bold; color: #6b7280; font-size: 14px;"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></span>
            <?php endif; ?>
        </a>
        <span class="navbar-user">
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