<?php
// Enable error display (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$page_title = "Login";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include header
require_once 'includes/header.php';

// Error message
$error = '';
$success = '';

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
    $success = "Registration successful! You can now log in with your first name, last name and email.";
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif (strlen($first_name) < 2 || strlen($last_name) < 2) {
        $error = "First name and last name must be at least 2 characters!";
    } else {
        try {
            // Include database class
            require_once 'includes/database.php';
            
            // Create database connection
            $db = new Database();
            
            // Query user from wp9k_fc_subscribers table
            $user = $db->fetchOne(
                "SELECT id, first_name, last_name, email FROM wp9k_fc_subscribers WHERE first_name = ? AND last_name = ? AND email = ?", 
                [$first_name, $last_name, $email]
            );
            
            if ($user) {
                // Login successful, set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['display_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Check if user is admin (id = 4)
                if ($user['id'] == 4) {
                    $_SESSION['is_admin'] = true;
                } else {
                    $_SESSION['is_admin'] = false;
                }
                
                // Update last login time (if the field exists)
                try {
                    $db->query(
                        "UPDATE wp9k_fc_subscribers SET last_login = NOW() WHERE id = ?",
                        [$user['id']]
                    );
                } catch (Exception $update_error) {
                    // Ignore update error if column doesn't exist
                    error_log("Update last_login error: " . $update_error->getMessage());
                }
                
                // Check if remember me is checked
                if (isset($_POST['remember']) && $_POST['remember'] == '1') {
                    // Set cookie for 30 days
                    $cookie_name = 'remember_me_' . md5($_SERVER['REMOTE_ADDR']);
                    $cookie_value = base64_encode($user['id'] . '|' . $user['email'] . '|' . time());
                    setcookie($cookie_name, $cookie_value, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                }
                
                // Redirect to homepage
                header("Location: index.php");
                exit();
            } else {
                $error = "No account found with these credentials!";
            }
        } catch (Exception $e) {
            // Log error
            error_log("Login error: " . $e->getMessage());
            $error = "System error: " . $e->getMessage();
        }
    }
}

// Check for remember me cookie
if (!$error && empty($_POST) && !isset($_SESSION['user_id'])) {
    $cookie_name = 'remember_me_' . md5($_SERVER['REMOTE_ADDR']);
    if (isset($_COOKIE[$cookie_name])) {
        try {
            $cookie_data = base64_decode($_COOKIE[$cookie_name]);
            $parts = explode('|', $cookie_data);
            
            if (count($parts) === 3) {
                $user_id = $parts[0];
                $user_email = $parts[1];
                $timestamp = $parts[2];
                
                // Check if cookie is still valid (within 30 days)
                if (time() - $timestamp < (30 * 24 * 60 * 60)) {
                    $db = new Database();
                    $user = $db->fetchOne(
                        "SELECT id, first_name, last_name, email FROM wp9k_fc_subscribers WHERE id = ? AND email = ?",
                        [$user_id, $user_email]
                    );
                    
                    if ($user) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['display_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        
                        // Check if user is admin (id = 4)
                        if ($user['id'] == 4) {
                            $_SESSION['is_admin'] = true;
                        } else {
                            $_SESSION['is_admin'] = false;
                        }
                        
                        header("Location: index.php");
                        exit();
                    }
                }
            }
        } catch (Exception $e) {
            // Clear invalid cookie
            setcookie($cookie_name, '', time() - 3600, '/');
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-form">
        <h2>User Login</h2>
        <p class="auth-subtitle">Please enter your first name, last name, and email</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logged_out']) && $_GET['logged_out'] == 'success'): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You have been successfully logged out.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="first_name">
                    <i class="fas fa-user"></i> First Name
                </label>
                <input type="text" id="first_name" name="first_name" 
                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                       placeholder="Enter your first name"
                       minlength="2" maxlength="50"
                       required>
                <small class="form-text">As registered in your account</small>
            </div>
            
            <div class="form-group">
                <label for="last_name">
                    <i class="fas fa-user"></i> Last Name
                </label>
                <input type="text" id="last_name" name="last_name" 
                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                       placeholder="Enter your last name"
                       minlength="2" maxlength="50"
                       required>
                <small class="form-text">As registered in your account</small>
            </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your registered email"
                       required>
                <small class="form-text">Must match your registration email</small>
            </div>
            
            <div class="form-group remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember" value="1"> Remember me for 30 days
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php" class="auth-link">Create Account</a></p>
            <p class="text-small">Need help? <a href="contact.php">Contact Support</a></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus first field
    document.getElementById('first_name').focus();
    
    // Form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (firstName.length < 2) {
            e.preventDefault();
            alert('First name must be at least 2 characters long.');
            document.getElementById('first_name').focus();
            return false;
        }
        
        if (lastName.length < 2) {
            e.preventDefault();
            alert('Last name must be at least 2 characters long.');
            document.getElementById('last_name').focus();
            return false;
        }
        
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            document.getElementById('email').focus();
            return false;
        }
        
        return true;
    });
});
</script>

<?php 
// Include footer
require_once 'includes/footer.php'; 
?>