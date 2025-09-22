<?php
session_start(); // Start session at the top
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password is empty
$dbname = "comp1044_database"; // Your database name

// Initialize message variables
$errorMsg = "";
$successMsg = "";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $errorMsg = "Connection failed: " . $conn->connect_error;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Use a prepared statement to prevent SQL injection
    $sql = "SELECT staff.Staff_ID, staff.Email, staff.username, staff.password, role.Role_Title 
            FROM staff 
            INNER JOIN role ON staff.Role_ID = role.Role_ID 
            WHERE BINARY staff.username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password (if passwords are hashed, use password_verify)
        if ($password === $user["password"]) { // Replace with password_verify($password, $user["password"]) if hashed
            $_SESSION["Email"] = $user["Email"]; // Store email in session
            $_SESSION["username"] = $user["username"]; // Store username
            $_SESSION["Staff_ID"] = $user["Staff_ID"]; // Store Staff_ID in session
            $_SESSION["Role_Title"] = $user["Role_Title"]; // Store role title in session

            // Set success message
            $successMsg = "Login successful! Redirecting...";

            // Redirect to homepage
            echo "<script>
                sessionStorage.setItem('redirectAfterLogin', 'HomePage.php');
                sessionStorage.setItem('shouldRedirect', 'true');
            </script>";
        } else {
            $errorMsg = "Invalid password.";
        }
    } else {
        $errorMsg = "Invalid username or password.";
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["signup"])) {
    $firstName = trim($_POST["firstName"]);
    $lastName = trim($_POST["lastName"]);
    $address = trim($_POST["address"]);
    $email = trim($_POST["email"]);
    $newUsername = trim($_POST["newUsername"]);
    $newPassword = trim($_POST["newPassword"]);
    $role = trim($_POST["role"]);

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($newUsername) || empty($newPassword) || empty($role)) {
        $errorMsg = "All fields are required. Please fill out the form completely.";
    } else {
        // Check if username or email already exists
        $checkDuplicateSql = "SELECT * FROM staff WHERE username = ? OR email = ?";
        $checkStmt = $conn->prepare($checkDuplicateSql);
        $checkStmt->bind_param("ss", $newUsername, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $errorMsg = "Signup failed! Username or email already exists. Please use a unique username and email.";
        } else {
            // Check if the role already exists in the role table
            $roleCheckSql = "SELECT Role_ID FROM role WHERE Role_Title = ?";
            $roleStmt = $conn->prepare($roleCheckSql);
            $roleStmt->bind_param("s", $role);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();

            if ($roleResult->num_rows > 0) {
                $roleRow = $roleResult->fetch_assoc();
                $roleID = $roleRow['Role_ID'];
            } else {
                $insertRoleSql = "INSERT INTO role (Role_Title) VALUES (?)";
                $insertRoleStmt = $conn->prepare($insertRoleSql);
                $insertRoleStmt->bind_param("s", $role);

                if ($insertRoleStmt->execute()) {
                    $roleID = $conn->insert_id;
                } else {
                    error_log("Role Insert Error: " . $conn->error);
                    exit();
                }
            }

            // Handle signup based on role
            if (strcasecmp(trim($role), 'Admin') === 0) {
                // Store admin data in session and redirect to verification page
                $_SESSION['pendingAdminSignup'] = [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'address' => $address,
                    'email' => $email,
                    'newUsername' => $newUsername,
                    'newPassword' => $newPassword,
                    'roleID' => $roleID // Add roleID to session data
                ];
                header("Location: VerificationPage.php");
                exit();
            } else {
                // Insert the new sales representative into the database
                $sql = "INSERT INTO staff (first_name, last_name, address, email, username, password, Role_ID) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $firstName, $lastName, $address, $email, $newUsername, $newPassword, $roleID);

                if ($stmt->execute()) {
                    $successMsg = "Signup successful!";
                } else {
                    error_log("Staff Insert Error: " . $stmt->error);
                    $errorMsg = "Error: " . $stmt->error;
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Signup</title>
    <link rel="stylesheet" href="Style.css">
</head>
<body class="background-login">
    <div class="bubbles-container">
        <div class="bubbles">
            <?php for ($i = 1; $i <= 50; $i++): ?>
                <span style="--i:<?php echo rand(10, 30); ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
    <div class="form-container">
        <div class="forms-wrapper">
            <div class="login-box">
                <div class="login-bubbles-container">
                    <div class="login-bubbles">
                        <span style="--i:11"></span>
                        <span style="--i:12"></span>
                        <span style="--i:24"></span>
                        <span style="--i:10"></span>
                        <span style="--i:14"></span>
                        <span style="--i:23"></span>
                        <span style="--i:18"></span>
                        <span style="--i:16"></span>
                        <span style="--i:19"></span>
                        <span style="--i:20"></span>
                    </div>
                </div>
                <h1 id="heading">ABB ROBOTICS</h1>
                <div class="text-box">
                    <!-- Display error message if there is one -->
                    <?php if (!empty($errorMsg)): ?>
                        <div class="message-box error-message"><?php echo $errorMsg; ?></div>
                    <?php endif; ?>
                    
                    <!-- Display success message if there is one -->
                    <?php if (!empty($successMsg)): ?>
                        <div class="message-box success-message"><?php echo $successMsg; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <input type="text" name="username" placeholder="Username" class="input-field" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <input type="password" name="password" placeholder="Password" class="input-field" required>
                        <button type="submit" name="login" class="submit-button">Login</button>
                    </form>
                    <p class="toggle-text">Don't have an account? <a href="#" class="toggle-link" id="showSignup">Sign Up</a></p>
                </div>
            </div>
            <div class="signup-box">
                <div class="signup-bubbles-container">
                    <div class="signup-bubbles">
                        <span style="--i:11"></span>
                        <span style="--i:12"></span>
                        <span style="--i:24"></span>
                        <span style="--i:10"></span>
                        <span style="--i:14"></span>
                        <span style="--i:23"></span>
                        <span style="--i:18"></span>
                        <span style="--i:16"></span>
                        <span style="--i:19"></span>
                        <span style="--i:20"></span>
                    </div>
                </div>
                <h1 id="heading">Create Account</h1>
                <div class="text-box">
                    <!-- Display error message if there is one -->
                    <?php if (!empty($errorMsg) && isset($_POST['signup'])): ?>
                        <div class="message-box error-message"><?php echo $errorMsg; ?></div>
                    <?php endif; ?>
                    
                    <!-- Display success message if there is one -->
                    <?php if (!empty($successMsg) && isset($_POST['signup'])): ?>
                        <div class="message-box success-message"><?php echo $successMsg; ?></div>
                    <?php endif; ?>
                    
                    <form id="signupFormPart1">
                        <input type="text" name="firstName" placeholder="First Name" class="input-field" required>
                        <input type="text" name="lastName" placeholder="Last Name" class="input-field" required>
                        <input type="textarea" name="address" placeholder="Address" class="input-field">
                        <button type="button" class="submit-button" id="nextButton">Next</button>
                    </form>
                    <p class="toggle-text">Already have an account? <a href="#" class="toggle-link" id="showLogin">Login</a></p>
                </div>
            </div>
            <div class="signup-box" id="signupPart2">
                <div class="signup-bubbles-container">
                    <div class="signup-bubbles">
                        <span style="--i:11"></span>
                        <span style="--i:12"></span>
                        <span style="--i:24"></span>
                        <span style="--i:10"></span>
                        <span style="--i:14"></span>
                        <span style="--i:23"></span>
                        <span style="--i:18"></span>
                        <span style="--i:16"></span>
                        <span style="--i:19"></span>
                        <span style="--i:20"></span>
                    </div>
                </div>
                <h1 id="heading">Complete Registration</h1>
                <div class="text-box" id="information">
                    <!-- Display error message if there is one -->
                    <?php if (!empty($errorMsg) && isset($_POST['signup'])): ?>
                        <div class="message-box error-message"><?php echo $errorMsg; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <input type="hidden" name="firstName" id="hiddenFirstName" required>
                        <input type="hidden" name="lastName" id="hiddenLastName" required>
                        <input type="hidden" name="address" id="hiddenAddress">
                        <input type="email" name="email" placeholder="Email" class="input-field" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address.">                        <input type="text" name="newUsername" placeholder="Username" class="input-field" required>
                        <input type="password" name="newPassword" placeholder="Password" class="input-field" required>
                        <select name="role" class="input-field" required>
                            <option value="Sales Representative">Sales Representative</option>
                            <option value="Admin">Admin</option>
                        </select>
                        <button type="submit" name="signup" class="submit-button" id="signupSubmit">Sign Up</button>
                  </form>
                    <p class="toggle-text"><a href="#" class="toggle-link" id="backToFirstPart">Back</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <script src="script.js"></script>
    <script>
        const body = document.body;
        const showSignupBtn = document.getElementById('showSignup');
        const showLoginBtn = document.getElementById('showLogin');
        const nextButton = document.getElementById('nextButton');
        const backToFirstPart = document.getElementById('backToFirstPart');
        const signupPart2 = document.getElementById('signupPart2');
        const formsWrapper = document.querySelector('.forms-wrapper');

        signupPart2.style.display = 'none';

        showSignupBtn.addEventListener('click', function () {
            body.classList.remove('background-login');
            body.classList.add('background-signup');
            formsWrapper.classList.add('show-signup'); // this class should trigger your slide effect
        });

        showLoginBtn.addEventListener('click', function () {
            body.classList.remove('background-signup');
            body.classList.add('background-login');
            formsWrapper.classList.remove('show-signup');
        });

        nextButton.addEventListener('click', function(event) {
            event.preventDefault();
            const firstName = document.querySelector('input[name="firstName"]');
            const lastName = document.querySelector('input[name="lastName"]');
            const address = document.querySelector('input[name="address"]');

            let isValid = true;
            const nameRegex = /^[A-Za-z\s]+$/;

            if (!nameRegex.test(lastName.value.trim())) {
                lastName.setCustomValidity("Last name cannot contain numbers or special characters.");
                lastName.reportValidity();
                isValid = false;
            } else {
                lastName.setCustomValidity("");
            }

            if (!nameRegex.test(firstName.value.trim())) {
                firstName.setCustomValidity("First name cannot contain numbers or special characters.");
                firstName.reportValidity();
                isValid = false;
            } else {
                firstName.setCustomValidity("");
            }

            if (!firstName.value.trim() || !lastName.value.trim()) {
                isValid = false;
            }

            if (isValid) {
                document.getElementById('hiddenFirstName').value = firstName.value.trim();
                document.getElementById('hiddenLastName').value = lastName.value.trim();
                document.getElementById('hiddenAddress').value = address.value.trim();

                document.querySelector('.signup-box:not(#signupPart2)').style.display = 'none';
                signupPart2.style.display = 'flex';
            }
        });

        backToFirstPart.addEventListener('click', function(event) {
            event.preventDefault();
            document.querySelector('.signup-box:not(#signupPart2)').style.display = 'flex';
            signupPart2.style.display = 'none';
        });

        // Show loading animation on form submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function () {
                document.getElementById('loadingOverlay').style.display = 'flex';
            });
        });

        window.addEventListener('load', () => {
            const shouldRedirect = sessionStorage.getItem('shouldRedirect');
            const redirectTarget = sessionStorage.getItem('redirectAfterLogin');

            // Only do this once, then clear flags before redirecting
            if (shouldRedirect === 'true' && redirectTarget) {
                // Show loading spinner
                document.getElementById('loadingOverlay').style.display = 'flex';
            
                // Clear the sessionStorage BEFORE redirecting
                sessionStorage.removeItem('shouldRedirect');
                sessionStorage.removeItem('redirectAfterLogin');
            
                setTimeout(() => {
                    window.location.href = redirectTarget;
                }, 2000);
            }
        });

        const signupSubmitButton = document.getElementById('signupSubmit'); // Make sure your actual submit button has this ID
        const roleSelect = document.getElementById('role'); // Make sure your role <select> has this ID

        signupSubmitButton.addEventListener('click', function (event) {
            const selectedRole = roleSelect.value;

            if (selectedRole === 'Admin') { // Ensure consistent casing
                event.preventDefault();
            
                const formData = {
                    username: document.querySelector('input[name="newUsername"]').value,
                    password: document.querySelector('input[name="newPassword"]').value,
                    firstName: document.getElementById('hiddenFirstName').value,
                    lastName: document.getElementById('hiddenLastName').value,
                    address: document.getElementById('hiddenAddress').value,
                    role: selectedRole
                };
            
                sessionStorage.setItem('pendingAdminSignup', JSON.stringify(formData));
                window.location.href = 'VerificationPage.php';
            }
        });

        // Automatically hide messages after 3 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message-box');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 3000);
    </script>
</body>
</html>