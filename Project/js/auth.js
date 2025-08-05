// Authentication Pages JavaScript
$(document).ready(function() {
    
    // Toggle Password Visibility
    $('.toggle-password').on('click', function() {
        const passwordInput = $(this).siblings('input');
        const passwordType = passwordInput.attr('type');
        
        // Toggle password visibility
        if (passwordType === 'password') {
            passwordInput.attr('type', 'text');
            $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Password Strength Meter (Signup Page)
    if ($('#password').length && $('.strength-meter-fill').length) {
        $('#password').on('input', function() {
            const password = $(this).val();
            const strength = calculatePasswordStrength(password);
            updatePasswordStrengthUI(strength);
        });
    }
    
    // Form Validation
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        // Simple validation
        const email = $('#email').val().trim();
        const password = $('#password').val();
        
        if (!email || !isValidEmail(email)) {
            showFormError($('#email'), 'Please enter a valid email address');
            return;
        }
        
        if (!password) {
            showFormError($('#password'), 'Please enter your password');
            return;
        }
        
        // If validation passes, you would normally submit the form
        // For demo purposes, we'll just show a success message
        showSuccessMessage('Login successful! Redirecting...');
        
        // Simulate redirect after login
        setTimeout(function() {
            window.location.href = 'homepage.html';
        }, 2000);
    });
    
    $('#signupForm').on('submit', function(e) {
        e.preventDefault();
        
        // Simple validation
        const fullName = $('#fullName').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        const agreeTerms = $('#agreeTerms').is(':checked');
        
        if (!fullName) {
            showFormError($('#fullName'), 'Please enter your full name');
            return;
        }
        
        if (!email || !isValidEmail(email)) {
            showFormError($('#email'), 'Please enter a valid email address');
            return;
        }
        
        if (!password) {
            showFormError($('#password'), 'Please create a password');
            return;
        }
        
        if (calculatePasswordStrength(password) < 2) {
            showFormError($('#password'), 'Please create a stronger password');
            return;
        }
        
        if (password !== confirmPassword) {
            showFormError($('#confirmPassword'), 'Passwords do not match');
            return;
        }
        
        if (!agreeTerms) {
            showFormError($('#agreeTerms').parent(), 'You must agree to the Terms of Service and Privacy Policy');
            return;
        }
        
        // If validation passes, you would normally submit the form
        // For demo purposes, we'll just show a success message
        showSuccessMessage('Account created successfully! Redirecting...');
        
        // Simulate redirect after signup
        setTimeout(function() {
            window.location.href = 'homepage.html';
        }, 2000);
    });
    
    // Helper Functions
    
    // Calculate password strength (0-4)
    function calculatePasswordStrength(password) {
        if (!password) return 0;
        
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 1;
        
        // Character variety checks
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        return strength;
    }
    
    // Update password strength UI
    function updatePasswordStrengthUI(strength) {
        const $strengthMeter = $('.strength-meter-fill');
        const $strengthText = $('.strength-text span');
        
        $strengthMeter.attr('data-strength', strength);
        
        switch (strength) {
            case 0:
                $strengthText.text('Weak');
                break;
            case 1:
                $strengthText.text('Fair');
                break;
            case 2:
                $strengthText.text('Good');
                break;
            case 3:
                $strengthText.text('Strong');
                break;
            case 4:
                $strengthText.text('Very Strong');
                break;
        }
    }
    
    // Validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Show form error
    function showFormError($element, message) {
        // Remove any existing error messages
        $('.form-error').remove();
        
        // Add error class to form group
        $element.closest('.form-group').addClass('has-error');
        
        // Add error message
        $element.after('<div class="form-error text-danger">' + message + '</div>');
        
        // Focus on the element
        $element.focus();
        
        // Remove error after 3 seconds
        setTimeout(function() {
            $element.closest('.form-group').removeClass('has-error');
            $('.form-error').fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Show success message
    function showSuccessMessage(message) {
        // Remove any existing messages
        $('.alert').remove();
        
        // Add success message
        const $alert = $('<div class="alert alert-success text-center">' + message + '</div>');
        $('form').before($alert);
        
        // Remove message after 3 seconds
        setTimeout(function() {
            $alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});