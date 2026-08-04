<?php if($_SESSION['role'] == 'admin'): ?>
<div id="ManageUsers" class="tab-content">
    <div class="dashboard-wrapper">
        <div class="side-panel">
            <div class="generic-container">
                <h3>Provision New Account</h3>
                <?php echo $user_msg; ?>
                <form method="POST">
                    <input type="hidden" name="add_user" value="1">
                    <label>Username</label><input type="text" name="username" required>
                    <label>Email</label><input type="email" name="email" required>
                    <label>Password</label><input type="password" name="password" required>
                    <label>Assign Role</label>
                    <select name="role" required>
                        <option value="staff">Staff / Official</option>
                        <option value="admin">Admin</option>
                    </select>
                    <button type="submit" class="btn-submit">Create Account</button>
                </form>
            </div>
        </div>
        <div class="center-panel">
            <h3>System Access List</h3>
            <table>
                <thead><tr><th>Username</th><th>Role</th><th class="center">Joined</th></tr></thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($users_result, 0);
                    while($user = mysqli_fetch_assoc($users_result)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo strtoupper($user['role']); ?></td>
                        <td class="center"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>