<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get and sanitize input
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $mobile = trim($_POST["mobile"]);
    $course = trim($_POST["course"]);
    $year = trim($_POST["year"]);
    $gender = trim($_POST["gender"]);

    // Sanitize
    $name = htmlspecialchars($name);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $mobile = htmlspecialchars($mobile);
    $course = htmlspecialchars($course);
    $year = htmlspecialchars($year);
    $gender = htmlspecialchars($gender);

    $errors = [];

    // Server-side validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $errors[] = "Name should contain only letters and spaces.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if (empty($mobile)) {
        $errors[] = "Mobile number is required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {
        $errors[] = "Mobile number must contain exactly 10 digits.";
    }

    if (empty($course)) {
        $errors[] = "Please select a course.";
    }

    if (empty($year)) {
        $errors[] = "Please select a year.";
    }

    if (empty($gender)) {
        $errors[] = "Please select gender.";
    }

    // Display errors
    if (!empty($errors)) {

        echo "<h2>Registration Failed</h2>";

        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }

        echo "<br><a href='index.html'>Go Back</a>";

    } else {

        // CSV file
        $file = "students.csv";

        // Open file
        $handle = fopen($file, "a");

        // Add heading if file is empty
        if (filesize($file) == 0) {
            fputcsv($handle, [
                "Name",
                "Email",
                "Mobile",
                "Course",
                "Year",
                "Gender"
            ]);
        }

        // Store data
        fputcsv($handle, [
            $name,
            $email,
            $mobile,
            $course,
            $year,
            $gender
        ]);

        fclose($handle);

        // Success message
        echo "<h2 style='color:green;'>Registration Successful!</h2>";

        echo "<h3>Submitted Details</h3>";

        echo "<p><b>Name:</b> $name</p>";
        echo "<p><b>Email:</b> $email</p>";
        echo "<p><b>Mobile:</b> $mobile</p>";
        echo "<p><b>Course:</b> $course</p>";
        echo "<p><b>Year:</b> $year</p>";
        echo "<p><b>Gender:</b> $gender</p>";

        echo "<p>Data has been stored successfully in <b>students.csv</b>.</p>";

        echo "<br><a href='index.html'>Register Another Student</a>";
    }
}

?>