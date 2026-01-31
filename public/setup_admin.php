<?php
$mysqli = new mysqli('tramway.proxy.rlwy.net', 'root', 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ', 'railway', 48486);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "<h3>Setting up admin user...</h3>";

$result = $mysqli->query("SELECT * FROM users WHERE id = 1");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Current admin: " . $user['username'] . " (" . $user['email'] . ")<br><br>";
    
    $username = 'admin';
    $email = 'admin@servana.com';
    $phone = '+639123456789';
    $password = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $mysqli->prepare("UPDATE users SET username=?, password=?, email=?, phone=? WHERE id=1");
    $stmt->bind_param("ssss", $username, $password, $email, $phone);
    
    if ($stmt->execute()) {
        echo "<strong style='color:green'>✓ Admin updated successfully!</strong><br><br>";
        echo "<strong>Login credentials:</strong><br>";
        echo "Email: <strong>admin@servana.com</strong><br>";
        echo "Password: <strong>admin123</strong><br><br>";
        echo "<a href='/admin/login'>Go to Admin Login</a>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "No admin user found!";
}

$mysqli->close();
?>
