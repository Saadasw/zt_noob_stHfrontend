<header class="navbar">
    <div class="branch-selector">
        <?php
        // Fetch branches from database
        $branches_list = [];
        try {
            $branches_list = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name")->fetchAll();
        } catch (PDOException $e) {
            // Table might not exist
        }

        // Handle branch selection
        if (isset($_GET['switch_branch'])) {
            $_SESSION['selected_branch_id'] = $_GET['switch_branch'] === 'all' ? null : $_GET['switch_branch'];
            // Find branch name for display
            if ($_GET['switch_branch'] !== 'all') {
                foreach ($branches_list as $b) {
                    if ($b['id'] === $_GET['switch_branch']) {
                        $_SESSION['selected_branch_name'] = $b['name'];
                        break;
                    }
                }
            } else {
                $_SESSION['selected_branch_name'] = null;
            }
            // Redirect to remove query param
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }

        $selected_branch_id = $_SESSION['selected_branch_id'] ?? null;
        $selected_branch_name = $_SESSION['selected_branch_name'] ?? 'All Branches';
        ?>
        <select onchange="if(this.value) window.location.href='?switch_branch=' + this.value">
            <option value="all" <?php echo !$selected_branch_id ? 'selected' : ''; ?>>All Branches</option>
            <?php foreach ($branches_list as $branch): ?>
                <option value="<?php echo h($branch['id']); ?>" <?php echo ($selected_branch_id === $branch['id']) ? 'selected' : ''; ?>>
                    <?php echo h($branch['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($selected_branch_id): ?>
            <span style="margin-left: 8px; font-size: 12px; color: #666;">📍 Filtering by branch</span>
        <?php endif; ?>
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
                <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'John Administrator'; ?>
            </div>
            <div class="user-role">
                <?php echo isset($_SESSION['user_role']) ? ucfirst(htmlspecialchars($_SESSION['user_role'])) : 'System Admin'; ?>
            </div>
        </div>
    </div>
</header>