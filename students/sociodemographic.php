<?php
// Include the database connection
include('db_connect.php');

// Get form data
$marital_status = $_POST['marital_status'];
$religion = $_POST['religion'];
$orientation = $_POST['orientation'];
$father_status = $_POST['father_status'];
$father_education = $_POST['father_education'];
$father_employment = $_POST['father_employment'];
$mother_status = $_POST['mother_status'];
$mother_education = $_POST['mother_education'];
$mother_employment = $_POST['mother_employment'];
$siblings = $_POST['siblings'];
$living_with = $_POST['living_with'];
$access_computer = $_POST['access_computer'];
$access_internet = $_POST['access_internet'];
$access_mobile = $_POST['access_mobile'];
$indigenous_group = $_POST['indigenous_group'];
$first_gen_college = $_POST['first_gen_college'];
$has_disability = $_POST['has_disability'];
$disability_detail = $_POST['disability_detail'];
$was_scholar = $_POST['was_scholar'];
$received_honors = $_POST['received_honors'];

// Insert the data into the socio_demographic table
$sql = "INSERT INTO socio_demographic (
    marital_status, religion, orientation, father_status, father_education,
    father_employment, mother_status, mother_education, mother_employment,
    siblings, living_with, access_computer, access_internet, access_mobile,
    indigenous_group, first_gen_college, has_disability, disability_detail,
    was_scholar, received_honors
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)";

// Prepare the SQL statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssssssiiiiiss",
    $marital_status, $religion, $orientation, $father_status, $father_education,
    $father_employment, $mother_status, $mother_education, $mother_employment,
    $siblings, $living_with, $access_computer, $access_internet, $access_mobile,
    $indigenous_group, $first_gen_college, $has_disability, $disability_detail,
    $was_scholar, $received_honors
);

// Execute the query
if ($stmt->execute()) {
    echo "Data successfully inserted!";
} else {
    echo "Error: " . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
