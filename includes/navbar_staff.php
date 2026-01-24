<header class="navbar">
    <div class="search-box"><input type="text" placeholder="Search..."></div>
    <div class="user-info">
        <div>
            <div class="user-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Jane Smith'; ?></div>
            <div class="user-role"><?php echo isset($_SESSION['user_role']) ? ucfirst(htmlspecialchars($_SESSION['user_role'])) : 'General Staff'; ?></div>
        </div>
    </div>
</header>
