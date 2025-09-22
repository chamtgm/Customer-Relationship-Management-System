<?php
session_start(); // Start session at the top
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password is empty
$dbname = "comp1044_database"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
    header("Location: LoginPage.php");
    exit();
}

$userEmail = $_SESSION["Email"]; // Retrieve email from session
$roleTitle = $_SESSION["Role_Title"]; // Retrieve role title from session
$staffID = $_SESSION["Staff_ID"]; // Retrieve Staff_ID from session

// Define the SQL query based on the user's role
if ($roleTitle === "Admin") {
    // Admin can see all users
    $sql = "
        SELECT 
            'Customer' AS Customer_Type,
            Customer_ID AS ID,
            First_Name,
            Last_Name,
            CONCAT(First_Name, ' ', Last_Name) AS Name,
            Email,
            Phone_Number,
            Company,
            Address
        FROM customer
        UNION
        SELECT 
            'Lead' AS Customer_Type,
            Lead_ID AS ID,
            First_Name,
            Last_Name,
            CONCAT(First_Name, ' ', Last_Name) AS Name,
            Email,
            Phone_Number,
            Company,
            Address
        FROM lead
        ORDER BY Name ASC
    ";
    $result = $conn->query($sql);
} else {
    // Sales representatives can only see their own users
    $sql = "
        SELECT 
            'Customer' AS Customer_Type,
            Customer_ID AS ID,
            First_Name,
            Last_Name,
            CONCAT(First_Name, ' ', Last_Name) AS Name,
            Email,
            Phone_Number,
            Company,
            Address
        FROM customer
        WHERE Staff_ID = ?
        UNION
        SELECT 
            'Lead' AS Customer_Type,
            Lead_ID AS ID,
            First_Name,
            Last_Name,
            CONCAT(First_Name, ' ', Last_Name) AS Name,
            Email,
            Phone_Number,
            Company,
            Address
        FROM lead
        WHERE Staff_ID = ?
        ORDER BY Name ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $staffID, $staffID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}

$customerCount = 0;
$leadCount = 0;
$totalCount = 0;

if ($result && $result->num_rows > 0) {
    // Reset pointer if needed, though it might not be necessary if fetched freshly
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        if ($row['Customer_Type'] === 'Customer') {
            $customerCount++;
        } elseif ($row['Customer_Type'] === 'Lead') {
            $leadCount++;
        }
    }
    $totalCount = $result->num_rows;
    // Reset pointer again so the table loop below works correctly
    $result->data_seek(0);
}

// Calculate percentages (handle division by zero)
$customerPercentage = ($totalCount > 0) ? round(($customerCount / $totalCount) * 100, 1) : 0;
$leadPercentage = ($totalCount > 0) ? round(($leadCount / $totalCount) * 100, 1) : 0;

// --- Query Interaction Types ---
$interactionLabels = [];
$interactionCounts = [];

// Re-open connection if it was closed, or move $conn->close() further down
// For simplicity, let's assume $conn is still open here. If not, reconnect.
// $conn = new mysqli($servername, $username, $password, $dbname); // Reconnect if needed

if ($roleTitle === "Admin") {
    // Admin sees all interactions
    $sqlInteractions = "SELECT Interaction_Type, COUNT(*) as Count
                        FROM interaction
                        GROUP BY Interaction_Type
                        ORDER BY Interaction_Type";
    $resultInteractions = $conn->query($sqlInteractions);
} else {
    // Sales Rep (assuming Role_ID 2 maps to the $roleTitle check) sees only their own interactions
    // Filter directly on the Staff_ID within the interaction table
    $sqlInteractions = "
        SELECT
            ir.Interaction_Type,
            COUNT(ir.Interaction_ID) as Count
        FROM
            interaction ir
        WHERE
            ir.Staff_ID = ?  -- Filter by the Staff_ID who recorded the interaction
        GROUP BY
            ir.Interaction_Type
        ORDER BY
            ir.Interaction_Type";
    $stmtInteractions = $conn->prepare($sqlInteractions);
    // Bind only the Staff_ID of the logged-in user
    $stmtInteractions->bind_param("i", $staffID); // Changed from "ii" to "i"
    $stmtInteractions->execute();
    $resultInteractions = $stmtInteractions->get_result();
    $stmtInteractions->close();
}

if ($resultInteractions && $resultInteractions->num_rows > 0) {
    while ($row = $resultInteractions->fetch_assoc()) {
        $interactionLabels[] = $row['Interaction_Type'];
        $interactionCounts[] = $row['Count'];
    }
}

// Convert PHP arrays to JSON for JavaScript
$interactionLabelsJson = json_encode($interactionLabels);
$interactionCountsJson = json_encode($interactionCounts);
// --- End Query Interaction Types ---

// --- Query Upcoming Reminder ---
$reminderDate = "N/A";
$reminderDetail = "No reminders for today."; // Updated default message
$reminderDetailShort = $reminderDetail;

$sqlReminder = "";
// Prepare the base query part - Use 'reminder_record' table and 'Description' column
// Select reminders scheduled exactly for the current date
$baseSqlReminder = "SELECT Event_Date, Description
                    FROM reminder_record
                    WHERE Event_Date = CURDATE()"; // Select reminders for today only

if ($roleTitle === "Admin") {
    // Admin sees the first reminder scheduled for today
    $sqlReminder = $baseSqlReminder . " ORDER BY Reminder_ID ASC LIMIT 1"; // Get the first one for today
    $resultReminder = $conn->query($sqlReminder);
} else {
    // Sales Rep sees their first reminder scheduled for today
    $sqlReminder = $baseSqlReminder . " AND Staff_ID = ? ORDER BY Reminder_ID ASC LIMIT 1"; // Filter by Staff_ID and get the first one for today
    $stmtReminder = $conn->prepare($sqlReminder);
    if ($stmtReminder) {
        $stmtReminder->bind_param("i", $staffID);
        $stmtReminder->execute();
        $resultReminder = $stmtReminder->get_result();
        $stmtReminder->close();
    } else {
        // Handle prepare error if needed
        $resultReminder = false;
        // Optionally log the error: error_log("Reminder query prepare failed: " . $conn->error);
    }
}

if ($resultReminder && $resultReminder->num_rows > 0) {
    $rowReminder = $resultReminder->fetch_assoc();
    $reminderDate = date("Y-m-d", strtotime($rowReminder['Event_Date'])); // Format date (will be today's date)
    $reminderDetail = $rowReminder['Description']; // Use 'Description' column
    // Limit detail length (e.g., 60 characters)
    $maxLength = 60;
    if (strlen($reminderDetail) > $maxLength) {
        $reminderDetailShort = substr($reminderDetail, 0, $maxLength) . "...";
    } else {
        $reminderDetailShort = $reminderDetail;
    }
}
// No 'else' block needed here anymore, as default values are set initially.
// --- End Query Upcoming Reminder ---

// --- Query Read Reminders ---
$reminderListHtml = "No read reminders found."; // Default message

$sqlReadReminders = "";
// Prepare the base query part - Select read reminders (Reminder_ID = 1)
// Order by Event_Date descending to show the most recent first
// --- MODIFIED: Added Reminder_Record_ID ---
$baseSqlReadReminders = "SELECT Reminder_Record_ID, Event_Date, Description
                         FROM reminder_record
                         WHERE Reminder_ID = 1"; // Select reminders marked as read

if ($roleTitle === "Admin") {
    // Admin sees all read reminders
    $sqlReadReminders = $baseSqlReadReminders . " ORDER BY Event_Date DESC"; // Order by date
    $resultReadReminders = $conn->query($sqlReadReminders);
} else {
    // Sales Rep sees their own read reminders
    $sqlReadReminders = $baseSqlReadReminders . " AND Staff_ID = ? ORDER BY Event_Date DESC"; // Filter by Staff_ID and order
    $stmtReadReminders = $conn->prepare($sqlReadReminders);
    if ($stmtReadReminders) {
        $stmtReadReminders->bind_param("i", $staffID);
        $stmtReadReminders->execute();
        $resultReadReminders = $stmtReadReminders->get_result();
        $stmtReadReminders->close();
    } else {
        // Handle prepare error if needed
        $resultReadReminders = false;
        error_log("Read Reminders query prepare failed: " . $conn->error);
    }
}

if ($resultReadReminders && $resultReadReminders->num_rows > 0) {
    $reminderListHtml = ""; // Clear default message
    while ($rowReminder = $resultReadReminders->fetch_assoc()) {
        $reminderId = $rowReminder['Reminder_Record_ID']; // Get the ID
        $eventDate = date("Y-m-d", strtotime($rowReminder['Event_Date']));
        $description = htmlspecialchars($rowReminder['Description']);

        // --- MODIFIED: Changed div to styled link (<a>) ---
        $reminderListHtml .= "<a href='ReminderPage.php#reminder-" . $reminderId . "' "; // Link to ReminderPage with hash
        $reminderListHtml .= " style='display: block; border: 1px solid #d6d6d6; padding: 5px; margin-bottom: 5px; border-radius: 4px; text-decoration: none; color: inherit; transition: background-color 0.2s ease;' "; // Styling
        $reminderListHtml .= " onmouseover=\"this.style.backgroundColor='#b8b8b8'\" onmouseout=\"this.style.backgroundColor='transparent'\" >"; // Hover effect
        $reminderListHtml .= "<strong>" . $eventDate . ":</strong> " . $description;
        $reminderListHtml .= "</a>";
        // --- END MODIFICATION ---
    }
}
// --- End Query Read Reminders ---

$conn->close(); // Now close the connection after all queries
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>ABB Robotics CRM - Home</title>
    <!-- Link to external CSS file -->
    <link rel="stylesheet" href="home.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <!-- Add Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        <!-- TOP BAR -->
        <header>         
            <div class="search-wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" fill="#888">
                <path d="M19.021 17.271 14.8 13.05q-.75.65-1.75 1.025-1 .375-2.1.375-2.625 0-4.462-1.837Q4.65 10.775 4.65 8.15q0-2.625 1.838-4.463Q8.325 1.85 10.95 1.85q2.625 0 4.462 1.837Q17.25 5.525 17.25 8.15q0 1.1-.388 2.125T15.863 12l4.2 4.2q.275.275.275.625 0 .35-.275.625-.275.275-.625.275-.35 0-.625-.275ZM10.95 9.95q.75 0 1.35-.6t.6-1.35q0-.75-.6-1.35t-1.35-.6q-.75 0-1.35.6t-.6 1.35q0 .75.6 1.35t1.35.6Z"/>
              </svg>
                <!-- Search form -->
                <form method="get" action="Search.php">
                    <input type="search" name="q" placeholder="Search..." />
                    <input type="hidden" name="source" value="HomePage">
                </form>
            </div>
            <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
        </header>

        <!-- ===== CHART SECTION ===== -->
        <div class="charts-wrapper" style="display: flex; justify-content: space-around; align-items: flex-start; flex-wrap: wrap; margin: 20px 0;">
            <!-- Customer vs Lead Pie Chart -->
            <div class="chart-container" style="width: 35%; max-width: 300px; height: 350px; text-align: center; position: relative; border: 2px solid #d6d6d6; border-radius: 20px; padding: 25px; margin-bottom: 20px;">
                 <h3>Customer vs Lead</h3>
                 <canvas id="customerLeadChart"></canvas>
            </div>
            <!-- Interaction Type Bar Chart -->
            <div class="chart-container" style="width: 35%; max-width: 300px; height: 350px; text-align: center; position: relative; border: 2px solid #d6d6d6; border-radius: 20px; padding: 25px; margin-bottom: 20px;">
                 <h3>Interaction Types</h3>
                 <canvas id="interactionTypeChart"></canvas>
            </div>
            <!-- Reminder Box -->
            <div class="reminder-box" style="width: 35%; max-width: 600px; height: 350px; border: 2px solid #d6d6d6; border-radius: 20px; padding: 25px; text-align: left; margin-bottom: 20px; box-sizing: border-box;">
                <h3>Read Reminders</h3> <!-- Changed title -->
                <hr style="margin-bottom: 15px;">
                <div style="height: 250px; overflow-y: auto;"> <!-- Added scrollable div -->
                    <?php echo $reminderListHtml; // Display the list of read reminders ?>
                </div>
                 <!-- Optional: Link to view all reminders -->
                 <a href="ReminderPage.php"
                    style="display: inline-block;
                           margin-top: 15px;
                           padding: 8px 15px;
                           background-color: #c0392b;
                           color: white;
                           text-decoration: none;
                           border-radius: 5px;
                           text-align: center;
                           transition: background-color 0.3s ease;
                           "
                    onmouseover="this.style.backgroundColor='#0056b3'" 
                    onmouseout="this.style.backgroundColor='#c0392b'"
                 >View All Reminders</a>
            </div>
        </div>
        <!-- ===== END CHART SECTION ===== -->

        <!-- DASHBOARD CONTENT -->
        <main>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'empty_search'): ?>
            <div class="error-message">
           <strong>Error:</strong> Please enter a search term before searching.
            </div>
        <?php endif; ?>          
            <div class="dashboard-header">
                <h2>Dashboard</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Customer Type</th>
                        <th>Phone No</th>
                        <th>Company</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr onclick="showContactDetails('<?php echo htmlspecialchars($row['ID']); ?>', 
                                                   '<?php echo htmlspecialchars($row['Customer_Type']); ?>', 
                                                   '<?php echo htmlspecialchars($row['Name']); ?>', 
                                                   '<?php echo htmlspecialchars($row['Email']); ?>', 
                                                   '<?php echo htmlspecialchars($row['Phone_Number']); ?>', 
                                                   '<?php echo htmlspecialchars($row['Company']); ?>', 
                                                   '<?php echo htmlspecialchars($row['Address']); ?>')" style="cursor:pointer;">
                                <td><?php echo htmlspecialchars($row['Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td><?php echo htmlspecialchars($row['Customer_Type']); ?></td>
                                <td><?php echo htmlspecialchars($row['Phone_Number']); ?></td>
                                <td><?php echo htmlspecialchars($row['Company']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
        <!-- ===== END DASHBOARD CONTENT ===== -->

        <!-- ===== CHART SCRIPT ===== -->
        <script>
            const ctx = document.getElementById('customerLeadChart').getContext('2d');
            const customerLeadChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: [
                        `Customer: <?php echo $customerPercentage; ?>%`,
                        `Lead: <?php echo $leadPercentage; ?>%`
                    ],
                    datasets: [{
                        label: 'Distribution',
                        data: [<?php echo $customerCount; ?>, <?php echo $leadCount; ?>],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)', // Blue for Customers
                            'rgba(255, 99, 132, 0.8)'  // Red for Leads
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Add this line
                    plugins: {
                        legend: {
                            position: 'top', // Or 'bottom', 'left', 'right'
                        },
                        tooltip: {
                            callbacks: {
                                // Show count in tooltip
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        // Extract type from the main label
                                        let typeLabel = context.label.split(':')[0];
                                        label += `${typeLabel} Count: ${context.parsed}`;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        </script>
        <!-- ===== END CHART SCRIPT ===== -->

        <!-- ===== NEW BAR CHART SCRIPT ===== -->
        <script>
            const interactionCtx = document.getElementById('interactionTypeChart').getContext('2d');
            const interactionTypeChart = new Chart(interactionCtx, { // Keep this initialization
                type: 'bar', // Set chart type to bar
                data: {
                    labels: <?php echo $interactionLabelsJson; ?>, // Use PHP variable for labels
                    datasets: [{
                        label: 'Interaction Count',
                        data: <?php echo $interactionCountsJson; ?>, // Use PHP variable for data
                        backgroundColor: [ // Add colors for bars
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                        ],
                        borderColor: [
                            'rgba(255, 159, 64, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Add this line
                    plugins: {
                        legend: {
                            display: false // Hide legend for bar chart if desired
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true, // Start Y-axis at 0
                            ticks: {
                                // Ensure only whole numbers are shown on the Y-axis
                                stepSize: 1,
                                precision: 0
                            }
                        }
                    }
                }
            });
        </script>
        <!-- ===== END NEW BAR CHART SCRIPT ===== -->

    </div> <!-- End main-content -->

    <!-- Contact Detail Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content modal-small"> <!-- Added modal-small class -->
            <span class="close-button" onclick="closeContactModal()" style= "cursor:pointer">&times;</span>
            <h2>Contact Details</h2> <!-- Title remains Contact Details -->
            <hr style="margin-top: 5px; margin-bottom: 15px;">
            <div class="modal-body">
                <!-- Using a slightly different structure for label/value -->
                <div class="detail-row"><span class="detail-label">ID:</span> <span id="modalContactID"></span></div>
                <div class="detail-row"><span class="detail-label">Type:</span> <span id="modalContactType"></span></div>
                <div class="detail-row"><span class="detail-label">First Name:</span> <span id="modalContactFirstName"></span></div>
                <div class="detail-row"><span class="detail-label">Last Name:</span> <span id="modalContactLastName"></span></div>
                <div class="detail-row"><span class="detail-label">Email:</span> <span id="modalContactEmail"></span></div>
                <div class="detail-row"><span class="detail-label">Phone:</span> <span id="modalContactPhone"></span></div>
                <div class="detail-row"><span class="detail-label">Company:</span> <span id="modalContactCompany"></span></div>
                <div class="detail-row"><span class="detail-label">Address:</span> <span id="modalContactAddress"></span></div>

                <!-- No Description section for contacts in this version -->

            </div>
            <hr style="margin-top: 15px; margin-bottom: 15px;">
            <div class="modal-footer">
                <!-- Updated buttons with consistent styling -->
                <button onclick="goToEdit()" class="form-button modal-button-edit">EDIT</button>
                <button onclick="viewInteractions()" class="form-button modal-button-view">VIEW INTERACTIONS</button>
            </div>
        </div>
    </div>
    <!-- End Contact Detail Modal -->

    <script>
        // Keep track of the currently displayed entity
        let currentId = null;
        let currentType = null;

        function showContactDetails(id, type, name, email, phone, company, address) {
            // --- Store current entity ---
            currentId = id;
            currentType = type;
            // --- End Store ---

            // Split name (assuming "First Last" format)
            const nameParts = name.split(' ');
            const firstName = nameParts.shift() || ''; // Get first part
            const lastName = nameParts.join(' ') || ''; // Get the rest

            // Populate basic info
            document.getElementById('modalContactID').textContent = id;
            document.getElementById('modalContactType').textContent = type;
            document.getElementById('modalContactFirstName').textContent = firstName;
            document.getElementById('modalContactLastName').textContent = lastName;
            document.getElementById('modalContactEmail').textContent = email || 'N/A';
            document.getElementById('modalContactPhone').textContent = phone || 'N/A';
            document.getElementById('modalContactCompany').textContent = company || 'N/A';
            document.getElementById('modalContactAddress').textContent = address || 'N/A';

            // Clear previous dynamic content (References removed as elements are gone)
            // document.getElementById('leadOnlyFields').style.display = 'none';
            // document.getElementById('customerOnlyFields').style.display = 'none';
            // document.getElementById('customerOnlyFields').innerHTML = '<h4>Customer Details</h4>';


            // Handle type-specific fields (Calls removed as elements are gone)
            // if (type === 'Lead') {
            //     fetchLeadDetails(id);
            //     document.getElementById('leadOnlyFields').style.display = 'block';
            // } else if (type === 'Customer') {
            //     fetchCustomerDetails(id);
            //     document.getElementById('customerOnlyFields').style.display = 'block';
            // }

            // Show the modal
            document.getElementById('contactModal').style.display = 'block';
        }

        // Close contact modal
        function closeContactModal() {
            document.getElementById('contactModal').style.display = 'none';
        }

        // Fetch lead-specific details (Function can be removed if not used elsewhere)
        // function fetchLeadDetails(id) { ... }

        // Fetch customer-specific details (Function can be removed if not used elsewhere)
        // function fetchCustomerDetails(id) { ... }


        // Go to edit page
        function goToEdit() {
            if (!currentId || !currentType) return; // Safety check
            const entityType = currentType.toLowerCase();
            window.location.href = `manageEntity.php?type=${entityType}&action=edit&id=${currentId}`;
        }

        // View interactions
        function viewInteractions() {
             if (!currentId || !currentType) return; // Safety check
            window.location.href = `Interactions.php?entityType=${currentType.toLowerCase()}&entityId=${currentId}`;
        }

        // When user clicks outside the modal, close it
        window.onclick = function(event) {
            const contactModal = document.getElementById('contactModal');
            if (event.target == contactModal) {
                closeContactModal();
            }
        };

        // --- Keep your Chart.js scripts here ---

    </script>

</body>
</html>