var form = document.getElementById('regForm');
var statusBox = document.getElementById('formStatus');

function showError(id, message) {
    document.getElementById(id + 'Error').textContent = message;
    var input = document.getElementById(id);
    if (input) {
        input.className = (message === '') ? 'valid' : 'invalid';
    }
}

function validateName() {
    var value = document.getElementById('fullName').value.trim();
    var pattern = /^[A-Za-z][A-Za-z .'-]{2,49}$/;
    var message = '';
    if (value === '') message = 'Full name is required.';
    else if (!pattern.test(value)) message = "Use 3-50 letters only (spaces . ' - are allowed).";
    showError('fullName', message);
    return message === '';
}

function validateEmail() {
    var value = document.getElementById('email').value.trim();
    var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    var message = '';
    if (value === '') message = 'Email is required.';
    else if (!pattern.test(value)) message = 'Enter a valid email like name@example.com.';
    showError('email', message);
    return message === '';
}

function validateMobile() {
    var value = document.getElementById('mobile').value.trim();
    var pattern = /^[0-9]{9}$/;
    var message = '';
    if (value === '') message = 'Mobile number is required.';
    else if (!pattern.test(value)) message = 'Enter a 10-digit mobile number.';
    showError('mobile', message);
    return message === '';
}

function validatePassword() {
    var value = document.getElementById('password').value;
    var message = '';
    if (value === '') message = 'Password is required.';
    else if (value.length < 8) message = 'Use at least 8 characters.';
    else if (!/[a-z]/.test(value) || !/[A-Z]/.test(value)) message = 'Include both uppercase and lowercase letters.';
    else if (!/[0-9]/.test(value)) message = 'Include at least one number.';
    else if (!/[^A-Za-z0-9]/.test(value)) message = 'Include at least one special character (e.g. @ # $ !).';
    showError('password', message);
    return message === '';
}

function validateConfirm() {
    var value = document.getElementById('confirmPassword').value;
    var message = '';
    if (value === '') message = 'Please confirm your password.';
    else if (value !== document.getElementById('password').value) message = 'Passwords do not match.';
    showError('confirmPassword', message);
    return message === '';
}

function validateCourse() {
    var message = (document.getElementById('course').value === '') ? 'Please select your course.' : '';
    showError('course', message);
    return message === '';
}

function validateYear() {
    var message = (document.getElementById('year').value === '') ? 'Please select your year.' : '';
    showError('year', message);
    return message === '';
}

function validateGender() {
    var chosen = form.querySelector('input[name="gender"]:checked');
    var message = (chosen === null) ? 'Please select a gender.' : '';
    showError('gender', message);
    return message === '';
}

function validateTerms() {
    var message = document.getElementById('terms').checked ? '' : 'You must accept the terms to continue.';
    showError('terms', message);
    return message === '';
}

function checkStrength() {
    var value = document.getElementById('password').value;
    var score = 0;
    if (value.length >= 8) score++;
    if (/[a-z]/.test(value)) score++;
    if (/[A-Z]/.test(value)) score++;
    if (/[0-9]/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;

    var labels = ['', 'Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
    var bar = document.getElementById('meterBar');
    bar.style.width = (score * 20) + '%';
    bar.className = (score === 0) ? 'bar' : 'bar s' + score;
    document.getElementById('meterText').textContent = (value === '') ? '' : 'Strength: ' + labels[score];
}


function addLiveCheck(id, checkFunction) {
    var input = document.getElementById(id);
    input.addEventListener('keyup', function (event) {
        if (event.key !== 'Tab') checkFunction();
    });
    input.addEventListener('change', checkFunction);
}

addLiveCheck('fullName', validateName);
addLiveCheck('email', validateEmail);
addLiveCheck('mobile', validateMobile);
addLiveCheck('confirmPassword', validateConfirm);
addLiveCheck('course', validateCourse);
addLiveCheck('year', validateYear);
addLiveCheck('terms', validateTerms);

function livePassword() {
    checkStrength();
    validatePassword();
    if (document.getElementById('confirmPassword').value !== '') validateConfirm();
}

document.getElementById('password').addEventListener('keyup', function (event) {
    if (event.key !== 'Tab') livePassword();
});
document.getElementById('password').addEventListener('change', livePassword);

var genderButtons = form.querySelectorAll('input[name="gender"]');
for (var i = 0; i < genderButtons.length; i++) {
    genderButtons[i].addEventListener('change', validateGender);
}

function clearErrors() {
    var ids = ['fullName', 'email', 'mobile', 'password', 'confirmPassword',
               'course', 'year', 'gender', 'terms'];
    for (var n = 0; n < ids.length; n++) {
        showError(ids[n], '');
        var input = document.getElementById(ids[n]);
        if (input) input.className = '';
    }
}

form.addEventListener('submit', function (event) {
    event.preventDefault();

    var allValid = true;
    if (!validateName()) allValid = false;
    if (!validateEmail()) allValid = false;
    if (!validateMobile()) allValid = false;
    if (!validatePassword()) allValid = false;
    if (!validateConfirm()) allValid = false;
    if (!validateCourse()) allValid = false;
    if (!validateYear()) allValid = false;
    if (!validateGender()) allValid = false;
    if (!validateTerms()) allValid = false;

    if (!allValid) {
        statusBox.className = 'status fail';
        statusBox.textContent = 'Please fix the errors shown and try again.';
        return;
    }

    var name = document.getElementById('fullName').value.trim();
    form.reset();
    statusBox.className = 'status ok';
    statusBox.textContent = 'Registration successful! Welcome, ' + name + '.';
});

form.addEventListener('reset', function () {
    statusBox.className = 'status';
    statusBox.textContent = '';
    setTimeout(function () {
        clearErrors();
        checkStrength();
    }, 0);
});