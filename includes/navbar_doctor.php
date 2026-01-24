<header class="navbar">
    <div class="search-box">
        <input type="text" placeholder="Search patients...">
    </div>
    <div class="user-info">
        <div>
            <div class="user-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Dr. Sarah Johnson'; ?></div>
            <div class="user-role"><?php echo isset($_SESSION['user_role']) ? 'General Medicine' : 'Doctor'; ?></div>
        </div>
    </div>
</header>
