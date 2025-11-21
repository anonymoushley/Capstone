<?php
$conn = new mysqli("localhost", "root", "", "admission");
if ($conn->connect_error) die("Connection failed");

$id = intval($_POST["id"]);
$field = $_POST["field"];
$value = $conn->real_escape_string($_POST["value"]);

$allowed = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'answer', 'points'];
if (in_array($field, $allowed)) {
    $conn->query("UPDATE questions SET $field = '$value' WHERE id = $id");
    echo "Updated $field.";
} else {
    echo "Invalid field.";
}
?>
