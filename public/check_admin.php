<?php
// Check admin user in Railway database
$host = 'tramway.proxy.rlwy.net';
$port = 48486;
$user = 'root';
$pass = 'wHNGLnPmKZNzlwPJzUVnwlzKXMQOqjMc';
$db = 'railway';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Admin User Check</h2>";

// Check users table for admin
$sql = "SELECT u.id, u.email, u.phone, u.first_name, u.active, ug.group_id 
        FROM users u 
        LEFT JOIN users_groups ug ON u.id = ug.user_id 
        WHERE u.email = 'admin@servana.ph' OR ug.group_id = 1";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h3>Found Admin Users:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Phone</th><th>Name</th><th>Active</th><th>Group ID</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['phone'] . "</td>";
        echo "<td>" . $row['first_name'] . "</td>";
        echo "<td>" . $row['active'] . "</td>";
        echo "<td>" . $row['group_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No admin user found with email admin@servana.ph or group_id=1</p>";
}

// Check all users with group_id = 1 (admin group)
echo "<h3>All Users in Admin Group (group_id=1):</h3>";
$sql2 = "SELECT u.id, u.email, u.phone, u.first_name, u.active, ug.group_id 
         FROM users u 
         INNER JOIN users_groups ug ON u.id = ug.user_id 
         WHERE ug.group_id = 1";

$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Phone</th><th>Name</th><th>Active</th><th>Group ID</th></tr>";
    while($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['phone'] . "</td>";
        echo "<td>" . $row['first_name'] . "</td>";
        echo "<td>" . $row['active'] . "</td>";
        echo "<td>" . $row['group_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No users found in admin group</p>";
}

// Check groups table
echo "<h3>Groups Table:</h3>";
$sql3 = "SELECT * FROM groups";
$result3 = $conn->query($sql3);

if ($result3->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
    while($row = $result3->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['description'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No groups found</p>";
}

$conn->close();
?>
