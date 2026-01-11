<?php
// Enable error display (for debugging, disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$page_title = "Register";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include header
require_once 'includes/header.php';

// Error and success messages
$error = '';
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required!";
    } elseif (strlen($first_name) < 2 || strlen($first_name) > 50) {
        $error = "First name must be between 2-50 characters!";
    } elseif (strlen($last_name) < 2 || strlen($last_name) > 50) {
        $error = "Last name must be between 2-50 characters!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        try {
            // Include database class
            require_once 'includes/database.php';
            
            // Create database connection
            $db = new Database();
            
            // Check if email already exists
            $check_email = $db->fetchOne(
                "SELECT id FROM users WHERE email = ?", 
                [$email]
            );
            
            if ($check_email) {
                $error = "This email is already registered!";
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Combine first and last name for username
                $username = strtolower($first_name . '.' . $last_name);
                $display_name = $first_name . ' ' . $last_name;
                
                $result = $db->insert(
                    "INSERT INTO users (first_name, last_name, display_name, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                    [$first_name, $last_name, $display_name, $email, $hashed_password]
                );
                
                if ($result) {
                    // Registration successful, set session
                    $_SESSION['user_id'] = $result;
                    $_SESSION['username'] = $username;
                    $_SESSION['display_name'] = $display_name;
                    $_SESSION['email'] = $email;
                    $success = true;
                    
                    // Redirect to homepage
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Registration failed, please try again!";
                }
            }
        } catch (Exception $e) {
            // Log error
            error_log("Registration error: " . $e->getMessage());
            $error = "System error, please try again later!";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Create Account</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registration successful! Redirecting...
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="first_name">
                    <i class="fas fa-user"></i> First Name
                </label>
                <input type="text" id="first_name" name="first_name" 
                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                       placeholder="Enter your first name"
                       minlength="2" maxlength="50"
                       required>
                <small class="form-text">2-50 characters</small>
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
                <small class="form-text">2-50 characters</small>
            </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter a valid email address"
                       required>
                <small class="form-text">Used for login and account security</small>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" 
                       placeholder="Enter password (at least 6 characters)"
                       minlength="6"
                       required>
                <small class="form-text">Password strength: <span id="passwordStrength">Weak</span></small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i> Confirm Password
                </label>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Re-enter your password"
                       minlength="6"
                       required>
                <small class="form-text" id="passwordMatch">Please ensure passwords match</small>
            </div>
            
            <div class="form-group terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a></label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php" class="auth-link">Login here</a></p>
        </div>
    </div>
</div>

<script>
// Password validation script
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthText = document.getElementById('passwordStrength');
    const matchText = document.getElementById('passwordMatch');
    
    // Password strength check
    password.addEventListener('input', function() {
        const pass = this.value;
        let strength = 'Weak';
        let color = 'red';
        
        if (pass.length >= 8) {
            strength = 'Medium';
            color = 'orange';
        }
        
        if (pass.length >= 10 && /[A-Z]/.test(pass) && /[0-9]/.test(pass) && /[^A-Za-z0-9]/.test(pass)) {
            strength = 'Strong';
            color = 'green';
        }
        
        strengthText.textContent = strength;
        strengthText.style.color = color;
    });
    
    // Password match check
    confirmPassword.addEventListener('input', function() {
        if (password.value === this.value) {
            matchText.textContent = 'Passwords match ✓';
            matchText.style.color = 'green';
        } else {
            matchText.textContent = 'Passwords do not match ✗';
            matchText.style.color = 'red';
        }
    });
    
    // Form submission validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const termsCheckbox = document.getElementById('terms');
        
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Passwords do not match! Please check your entries.');
            confirmPassword.focus();
            return false;
        }
        
        if (!termsCheckbox.checked) {
            e.preventDefault();
            alert('You must agree to the Terms of Service and Privacy Policy.');
            return false;
        }
    });
});
</script>

<?php 
// Include footer
require_once 'includes/footer.php'; 
?>