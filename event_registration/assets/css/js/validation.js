// validation.js
// Browser-side checks for the registration form, the profile form and the
// change-password form. The rules match includes/validation.php; the server
// checks again, because browser checks can be bypassed.
document.addEventListener('DOMContentLoaded', function () {

    var namePattern = /^[A-Za-z][A-Za-z .'-]{1,99}$/;
    var emailPattern = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    var phonePattern = /^[6-9][0-9]{9}$/;

    // Shows a red or green state under one field
    function setState(input, isValid, message) {
        var feedback = input.parentElement.querySelector('.invalid-feedback');
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if (feedback) {
                feedback.textContent = message;
            }
        }
        return isValid;
    }

    function nameOk(input) {
        return setState(input, namePattern.test(input.value.trim()),
            'Enter a valid name (2 to 100 letters, spaces, dots, apostrophes or hyphens).');
    }

    function emailOk(input) {
        var value = input.value.trim();
        return setState(input, value.length <= 100 && emailPattern.test(value),
            'Enter a valid email address, for example name@example.com.');
    }

    function phoneOk(input) {
        return setState(input, phonePattern.test(input.value.trim()),
            'Enter a 10-digit mobile number starting with 6, 7, 8 or 9.');
    }

    function passwordOk(input) {
        var v = input.value;
        var ok = v.length >= 8 && v.length <= 64
                && /[A-Z]/.test(v) && /[a-z]/.test(v) && /[0-9]/.test(v);
        return setState(input, ok,
            'Password must be 8 to 64 characters with an uppercase letter, a lowercase letter and a digit.');
    }

    function confirmOk(input, original) {
        return setState(input, input.value.length > 0 && input.value === original.value,
            'The two passwords do not match.');
    }

    function filledOk(input, message) {
        return setState(input, input.value.length > 0, message);
    }

    // ---------- Registration form ----------
    var registerForm = document.getElementById('registerForm');
    if (registerForm) {
        var rName = document.getElementById('name');
        var rEmail = document.getElementById('email');
        var rPhone = document.getElementById('phone');
        var rPassword = document.getElementById('password');
        var rConfirm = document.getElementById('confirmPassword');

        rName.addEventListener('blur', function () { nameOk(rName); });
        rEmail.addEventListener('blur', function () { emailOk(rEmail); });
        rPhone.addEventListener('blur', function () { phoneOk(rPhone); });
        rPassword.addEventListener('blur', function () { passwordOk(rPassword); });
        rConfirm.addEventListener('blur', function () { confirmOk(rConfirm, rPassword); });

        registerForm.addEventListener('submit', function (event) {
            var a = nameOk(rName);
            var b = emailOk(rEmail);
            var c = phoneOk(rPhone);
            var d = passwordOk(rPassword);
            var f = confirmOk(rConfirm, rPassword);
            if (!(a && b && c && d && f)) {
                event.preventDefault();
            }
        });
    }

    // ---------- Profile form ----------
    var profileForm = document.getElementById('profileForm');
    if (profileForm) {
        var pName = document.getElementById('pName');
        var pEmail = document.getElementById('pEmail');
        var pPhone = document.getElementById('pPhone');

        pName.addEventListener('blur', function () { nameOk(pName); });
        pEmail.addEventListener('blur', function () { emailOk(pEmail); });
        pPhone.addEventListener('blur', function () { phoneOk(pPhone); });

        profileForm.addEventListener('submit', function (event) {
            var a = nameOk(pName);
            var b = emailOk(pEmail);
            var c = phoneOk(pPhone);
            if (!(a && b && c)) {
                event.preventDefault();
            }
        });
    }

    // ---------- Change-password form ----------
    var passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        var current = document.getElementById('currentPassword');
        var newPass = document.getElementById('newPassword');
        var confirmNew = document.getElementById('confirmNewPassword');

        current.addEventListener('blur', function () { filledOk(current, 'Enter your current password.'); });
        newPass.addEventListener('blur', function () { passwordOk(newPass); });
        confirmNew.addEventListener('blur', function () { confirmOk(confirmNew, newPass); });

        passwordForm.addEventListener('submit', function (event) {
            var a = filledOk(current, 'Enter your current password.');
            var b = passwordOk(newPass);
            var c = confirmOk(confirmNew, newPass);
            if (!(a && b && c)) {
                event.preventDefault();
            }
        });
    }
});