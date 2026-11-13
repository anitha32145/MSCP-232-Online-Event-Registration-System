// login.js
// Changes the label of the login field depending on whether "User" or
// "Admin" is selected, and checks that both fields are filled before sending.
document.addEventListener('DOMContentLoaded', function () {

    var form = document.getElementById('loginForm');
    if (!form) {
        return;
    }

    var radios = document.querySelectorAll('input[name="role"]');
    var label = document.getElementById('loginIdLabel');
    var input = document.getElementById('loginId');
    var registerBox = document.getElementById('registerBox');
    var clientError = document.getElementById('clientError');

    function getRole() {
        var checked = document.querySelector('input[name="role"]:checked');
        return checked ? checked.value : 'user';
    }

    // Updates the label, the placeholder and the "Register" link
    function updateRole() {
        if (getRole() === 'admin') {
            label.textContent = 'Admin username';
            input.placeholder = 'Enter admin username';
            registerBox.style.display = 'none';
        } else {
            label.textContent = 'Email address';
            input.placeholder = 'Enter your email';
            registerBox.style.display = 'block';
        }
    }

    for (var i = 0; i < radios.length; i++) {
        radios[i].addEventListener('change', updateRole);
    }
    updateRole();

    // Stops the form if a field is empty
    form.addEventListener('submit', function (event) {
        var password = document.getElementById('password');
        if (input.value.trim() === '' || password.value === '') {
            event.preventDefault();
            clientError.textContent = 'Please enter your login ID and password.';
            clientError.classList.remove('d-none');
        }
    });
});