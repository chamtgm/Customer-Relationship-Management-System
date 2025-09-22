<?php
session_start();

// Check if registration data exists in the session
if (!isset($_SESSION['pendingAdminSignup'])) { // Use the same session key as in LoginPage.php
    header("Location: LoginPage.php"); // Redirect back to the login page if no data
    exit();
}

// Add a variable to track error message
$errorMessage = '';

// Handle verification code submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify"])) {
    // Check if session data still exists (in case the timer expired)
    if (!isset($_SESSION['pendingAdminSignup'])) {
        echo "<script>alert('Time is up! Please try signing up again.');</script>";
        header("Location: LoginPage.php");
        exit();
    }

    // Combine individual digit inputs into one verification code
    $digit1 = isset($_POST["digit1"]) ? $_POST["digit1"] : "";
    $digit2 = isset($_POST["digit2"]) ? $_POST["digit2"] : "";
    $digit3 = isset($_POST["digit3"]) ? $_POST["digit3"] : "";
    $digit4 = isset($_POST["digit4"]) ? $_POST["digit4"] : "";
    $digit5 = isset($_POST["digit5"]) ? $_POST["digit5"] : "";
    $digit6 = isset($_POST["digit6"]) ? $_POST["digit6"] : "";
    
    $verificationCode = $digit1 . $digit2 . $digit3 . $digit4 . $digit5 . $digit6;
    $timeLeft = isset($_POST["timeLeft"]) ? intval($_POST["timeLeft"]) : 0;

    // Check if the timer has expired
    if ($timeLeft <= 0) {
        echo "<script>alert('Time is up! Please try signing up again.');</script>";
        header("Location: LoginPage.php");
        exit();
    }

    // Check if the verification code is correct
    if ($verificationCode === "888888") {
        // Retrieve registration data from the session
        $registrationData = $_SESSION['pendingAdminSignup']; // Use the same session key
        unset($_SESSION['pendingAdminSignup']); // Clear session data after use

        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "comp1044_database";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Insert the new admin into the database
        $sql = "INSERT INTO staff (first_name, last_name, address, email, username, password, Role_ID) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssi",
            $registrationData['firstName'],
            $registrationData['lastName'],
            $registrationData['address'],
            $registrationData['email'],
            $registrationData['newUsername'],
            $registrationData['newPassword'],
            $registrationData['roleID']
        );

        if ($stmt->execute()) {
            // Clear the timer from sessionStorage and alert the user
            echo "<script>
                sessionStorage.removeItem('timeLeft'); // Clear the timer
                alert('Admin registration successful! You can now log in.');
                window.location.href = 'LoginPage.php'; // Redirect to the login page
            </script>";
            exit();
        } else {
            echo "<script>alert('Error: " . $stmt->error . "');</script>";
        }

        $stmt->close();
        $conn->close();
    } else {
        // Set the error message
        $errorMessage = 'Invalid verification code. Please try again.';
        
        // Display error message and trigger the error state for the input boxes
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showErrorState();
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verification</title>
    <link rel="stylesheet" href="verification.css">
    <style>
        .error-message {
            color: #ff4d4f;
            font-size: 14px;
            margin-top: 10px;
            margin-bottom: 10px;
            font-weight: 500;
        }
    </style>
    <script>
        // Timer functionality
        let timeLeft = sessionStorage.getItem('timeLeft') || 60; // Retrieve remaining time from sessionStorage or default to 60 seconds
        let timerExpired = false; // Flag to track if the timer has expired

        function startTimer() {
            const timerElement = document.getElementById('timer');
            timerElement.textContent = timeLeft + " seconds remaining"; // Immediately display the latest time
            const timerInterval = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerExpired = true; // Set the flag to true
                    alert("Time is up! Please try signing up again.");
                    sessionStorage.removeItem('timeLeft'); // Clear the timer
                    window.location.href = "LoginPage.php"; // Redirect to login page
                } else {
                    timerElement.textContent = timeLeft + " seconds remaining";
                    timeLeft--;
                    sessionStorage.setItem('timeLeft', timeLeft); // Save remaining time to sessionStorage
                }
            }, 1000);
        }

        // Function to handle input in code fields
        function handleInput(elm) {
            // Ensure the input is only a single digit
            if (elm.value.length > 1) {
                elm.value = elm.value.slice(0, 1);
            }
            
            // Move to next input field if a digit is entered
            if (elm.value.length === 1) {
                // Add the 'filled' class to highlight the current input
                elm.classList.add('filled');
                // Remove any error state that might be present
                elm.classList.remove('error');
                
                // Hide error message when user starts typing again
                const errorMsgElement = document.getElementById('error-message');
                if (errorMsgElement) {
                    errorMsgElement.style.display = 'none';
                }
                
                const nextInput = elm.nextElementSibling;
                if (nextInput) {
                    nextInput.focus();
                }
                
                // Check if all inputs are filled
                checkAllInputs();
            } else {
                // If the input is cleared, remove the 'filled' class
                elm.classList.remove('filled');
            }
        }

        // Function to handle backspace key
        function handleKeyDown(e, elm) {
            if (e.key === 'Backspace') {
                if (elm.value.length === 0) {
                    const prevInput = elm.previousElementSibling;
                    if (prevInput) {
                        prevInput.focus();
                    }
                } else {
                    // If there was a value and it's being deleted, remove the 'filled' class
                    elm.classList.remove('filled');
                }
                
                // Check if all inputs are filled
                setTimeout(checkAllInputs, 50);
            }
        }
        
        // Function to check if all inputs are filled and highlight them
        function checkAllInputs() {
            const inputs = document.querySelectorAll('.code-input');
            const allFilled = Array.from(inputs).every(input => input.value.length === 1);
            
            if (allFilled) {
                // If all are filled, add the success class to all inputs
                inputs.forEach(input => input.classList.add('correct'));
                
                // Optional: You could also automatically submit the form after a delay
                // setTimeout(() => document.querySelector('form').submit(), 1000);
            } else {
                // If not all are filled, remove the success class
                inputs.forEach(input => input.classList.remove('correct'));
            }
        }
        
        // Function to show error state when invalid code is entered
        function showErrorState() {
            const inputs = document.querySelectorAll('.code-input');
            inputs.forEach(input => {
                input.classList.remove('correct');
                input.classList.remove('filled');
                input.classList.add('error');
            });
            
            // Show error message
            const errorMsgElement = document.getElementById('error-message');
            if (errorMsgElement) {
                errorMsgElement.style.display = 'block';
            }
            
            // Remove the error class after a delay (optional)
            setTimeout(() => {
                inputs.forEach(input => {
                    input.classList.remove('error');
                });
            }, 1500);
        }

        function validateNumericInput(elm) {
            // Replace any non-numeric character with empty string
            elm.value = elm.value.replace(/[^0-9]/g, '');
            
            // If the input is valid and contains a digit, proceed with normal handling
            if (elm.value.length > 0) {
                handleInput(elm);
            }
        }

        // Modify your existing handleInput function
        function handleInput(elm) {
            // Move to next input field if a digit is entered
            if (elm.value.length === 1) {
                // Add the 'filled' class to highlight the current input
                elm.classList.add('filled');
                // Remove any error state that might be present
                elm.classList.remove('error');

                // Hide error message when user starts typing again
                const errorMsgElement = document.getElementById('error-message');
                if (errorMsgElement) {
                    errorMsgElement.style.display = 'none';
                }

                const nextInput = elm.nextElementSibling;
                if (nextInput) {
                    nextInput.focus();
                }

                // Check if all inputs are filled
                checkAllInputs();
            } else {
                // If the input is cleared, remove the 'filled' class
                elm.classList.remove('filled');
            }
        }

        // Add this function to prevent non-numeric keypresses
        function isNumberKey(evt) {
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        // Reset the timer when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            if (!sessionStorage.getItem('timeLeft')) {
                sessionStorage.setItem('timeLeft', 60); // Reset the timer to 60 seconds
            }
            startTimer();
            
            // Focus on the first input field when page loads
            const firstInput = document.querySelector('.code-input');
            if (firstInput) {
                firstInput.focus();
            }
            
            const form = document.querySelector('form');
            form.addEventListener('submit', (event) => {
                if (timerExpired) {
                    event.preventDefault();
                    alert("Time is up! Please try signing up again.");
                } else {
                    // Add the remaining time to a hidden input field before submitting
                    const timeLeftInput = document.getElementById('timeLeft');
                    timeLeftInput.value = timeLeft;
                }
            });
            
            // Check initial state of inputs (in case of browser auto-fill)
            checkAllInputs();
        });
    </script>
</head>
<body>
    <div class="form-container">
        <div style="text-align: center; margin-bottom: 20px;">
            <!-- Verification icon - you can replace with an actual image if you prefer -->
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="20" y="20" width="40" height="40" rx="5" stroke="#8CC63F" stroke-width="2"/>
                <path d="M30 40L37 47L50 33" stroke="#8CC63F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15 35H18M15 45H18M62 35H65M62 45H65" stroke="#8CC63F" stroke-width="2"/>
            </svg>
        </div>
        
        <h1>Verification Code</h1>
        <p>We have sent a code to your email. Please enter it below to verify your account.</p>
        <p id="timer">60 seconds remaining</p> <!-- Timer display -->
        
        <!-- Error message display -->
        <?php if (!empty($errorMessage)): ?>
            <div id="error-message" class="error-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
            <div id="error-message" class="error-message" style="display: none;">
                Invalid verification code. Please try again.
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <input type="hidden" name="timeLeft" id="timeLeft" value="60"> <!-- Hidden input to store remaining time -->
            
            <div class="code-container">
                <input type="text" name="digit1" maxlength="1" class="code-input" oninput="validateNumericInput(this)" onkeydown="handleKeyDown(event, this)" onkeypress="return isNumberKey(event)" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="digit2" maxlength="1" class="code-input" oninput="validateNumericInput(this)" onkeydown="handleKeyDown(event, this)" onkeypress="return isNumberKey(event)" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="digit3" maxlength="1" class="code-input" oninput="validateNumericInput(this)" onkeydown="handleKeyDown(event, this)" onkeypress="return isNumberKey(event)" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="digit4" maxlength="1" class="code-input" oninput="validateNumericInput(this)" onkeydown="handleKeyDown(event, this)" onkeypress="return isNumberKey(event)" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="digit5" maxlength="1" class="code-input" oninput="validateNumericInput(this)" onkeydown="handleKeyDown(event, this)" onkeypress="return isNumberKey(event)" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="digit6" maxlength="1" class="code-input" oninput="validateNumericInput(this)" onkeydown="handleKeyDown(event, this)" onkeypress="return isNumberKey(event)" pattern="[0-9]" inputmode="numeric" required>
            </div>
            
            <button type="submit" name="verify" class="submit-button">Confirm Code</button>
        </form>
        <a href="#" class="resend-link">Didn't receive code? Resend</a>
    </div>
</body>
</html>