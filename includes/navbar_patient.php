<header class="navbar">
    <div class="search-box">
        <input type="text" placeholder="Search...">
    </div>
    <div class="user-info">
        <?php
        // Fetch profile image
        require_once '../includes/ProfileImageHandler.php';
        $navbarImageHandler = new ProfileImageHandler($pdo);
        $navbarProfileImage = $navbarImageHandler->getProfileImage($_SESSION['user_id']);
        ?>
        <a href="profile.php"
            style="display: block; width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #e5e7eb; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?php if ($navbarProfileImage): ?>
                <img src="../<?php echo h($navbarProfileImage); ?>" alt="Profile"
                    style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <span
                    style="font-weight: bold; color: #6b7280; font-size: 16px;"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></span>
            <?php endif; ?>
        </a>
        <div>
            <div class="user-name">
                <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'John Smith'; ?>
            </div>
            <div class="user-role">Patient</div>
        </div>
    </div>
</header>