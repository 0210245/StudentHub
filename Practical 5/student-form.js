document.getElementById("registrationForm").addEventListener("submit", function(event) {

    event.preventDefault();

    let name = document.getElementById("name").value.trim();
    let email = document.getElementById("email").value.trim();
    let mobile = document.getElementById("mobile").value.trim();
    let password = document.getElementById("password").value;
    let confirmPassword = document.getElementById("confirmPassword").value;
    let course = document.getElementById("course").value;
    let year = document.getElementById("year").value;
    let gender = document.querySelector('input[name="gender"]:checked');
    let terms = document.getElementById("terms").checked;

    let namePattern = /^[A-Za-z ]+$/;
    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    let mobilePattern = /^[0-9]{10}$/;
    let passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

   
    document.querySelectorAll("span").forEach(function(error) {
        error.innerHTML = "";
    });

    document.getElementById("successMessage").innerHTML = "";

    let valid = true;

    if (name === "") {
        document.getElementById("nameError").innerHTML = "Name is required";
        valid = false;
    }
    else if (!namePattern.test(name)) {
        document.getElementById("nameError").innerHTML =
            "Name should contain only letters and spaces";
        valid = false;
    }

    if (email === "") {
        document.getElementById("emailError").innerHTML = "Email is required";
        valid = false;
    }
    else if (!emailPattern.test(email)) {
        document.getElementById("emailError").innerHTML =
            "Enter a valid email address";
        valid = false;
    }

    if (mobile === "") {
        document.getElementById("mobileError").innerHTML =
            "Mobile number is required";
        valid = false;
    }
    else if (!mobilePattern.test(mobile)) {
        document.getElementById("mobileError").innerHTML =
            "Mobile number must contain exactly 10 digits";
        valid = false;
    }

    if (password === "") {
        document.getElementById("passwordError").innerHTML =
            "Password is required";
        valid = false;
    }
    else if (!passwordPattern.test(password)) {
        document.getElementById("passwordError").innerHTML =
            "Password must have 8+ characters, uppercase, lowercase, number and special character";
        valid = false;
    }

    if (confirmPassword === "") {
        document.getElementById("confirmPasswordError").innerHTML =
            "Please confirm your password";
        valid = false;
    }
    else if (password !== confirmPassword) {
        document.getElementById("confirmPasswordError").innerHTML =
            "Passwords do not match";
        valid = false;
    }

    if (course === "") {
        document.getElementById("courseError").innerHTML =
            "Please select a course";
        valid = false;
    }

    if (year === "") {
        document.getElementById("yearError").innerHTML =
            "Please select your year";
        valid = false;
    }

    if (!gender) {
        document.getElementById("genderError").innerHTML =
            "Please select your gender";
        valid = false;
    }

    if (!terms) {
        document.getElementById("termsError").innerHTML =
            "You must accept the Terms and Conditions";
        valid = false;
    }

    if (valid) {
        document.getElementById("successMessage").innerHTML =
            "Registration successful!";
    }
});
