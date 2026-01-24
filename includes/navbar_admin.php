<header class="navbar">
    <div class="branch-selector">
        <select>
            <option>All Branches</option>
            <option>Melbourne CBD</option>
            <option>Sydney CBD</option>
            <option>Brisbane</option>
        </select>
    </div>
    <div class="user-info">
        <div>
            <div class="user-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'John Administrator'; ?></div>
            <div class="user-role"><?php echo isset($_SESSION['user_role']) ? ucfirst(htmlspecialchars($_SESSION['user_role'])) : 'System Admin'; ?></div>
        </div>
    </div>
</header>
