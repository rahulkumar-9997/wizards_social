$(document).ready(function () {
    const emailInput = $('#emailInput');
    const sendOtpBtn = $('#sendOtpBtn');
    const passwordSection = $('#passwordSection');
    const otpSection = $('#otpSection');
    const switchToOtp = $('#switchToOtp');
    const switchToPassword = $('#switchToPassword');
    const loginMethod = $('#loginMethod');
    const submitBtn = $('#submitBtn');
    const resendOtp = $('#resendOtp');
    const countdownElement = $('#countdown');
    const otpInputs = $('.otp-input');
    const loginForm = $('#loginForm');
    const errorContainer = $('#error-container');
    let countdown;
    let countdownTime = 60;
    function showToast(message, type = 'success') {
        const backgroundColor = type === 'success' ? '#28a745' : '#dc3545';
        Toastify({
            text: message,
            duration: 10000,
            gravity: "top",
            position: "right",
            backgroundColor: backgroundColor,
            close: true,
            stopOnFocus: true,
        }).showToast();
    }
    function showFormError(message) {
        errorContainer.html(
            `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                ${message}
            </div>`
        );
    }
    function clearFormErrors() {
        errorContainer.html('');
    }
    emailInput.on('input', function () {
        const value = emailInput.val().trim();
        const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        if (isEmail) {
            sendOtpBtn.show();
        } else {
            sendOtpBtn.hide();
            if (loginMethod.val() === 'otp') {
                switchToPasswordLogin();
            }
        }
    });
    switchToOtp.on('click', function () {
        const value = emailInput.val().trim();
        const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        if (!isEmail) {
            showToast('Please enter a valid email address to use OTP login.', 'error');
            return;
        }
        loginMethod.val('otp');
        passwordSection.hide();
        otpSection.show();
        switchToOtp.hide();
        switchToPassword.show();
        submitBtn.text('Verify OTP');
        sendOtp();
    });
    switchToPassword.on('click', function () {
        switchToPasswordLogin();
    });

    function switchToPasswordLogin() {
        loginMethod.val('password');
        passwordSection.show();
        otpSection.hide();
        switchToOtp.show();
        switchToPassword.hide();
        submitBtn.text('Sign In');
        clearInterval(countdown);
        otpInputs.val('');
        clearFormErrors();
    }
    sendOtpBtn.on('click', sendOtp);
    function sendOtp() {
        const email = emailInput.val().trim();
        if (!email) {
            showToast('Please enter your email address.', 'error');
            return;
        }
        const originalButtonText = sendOtpBtn.html();
        sendOtpBtn.prop('disabled', true).addClass('btn-loading').html('Sending...');
        $.ajax({
            url: generateOtpUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                email: email
            },
            success: function (response) {
                if (response.success) {
                    showToast('OTP sent to your email!', 'success');
                    startCountdown();
                    if (loginMethod.val() !== 'otp') {
                        loginMethod.val('otp');
                        passwordSection.hide();
                        otpSection.show();
                        switchToOtp.hide();
                        switchToPassword.show();
                        submitBtn.text('Verify OTP');
                    }
                    otpInputs.first().focus();
                } else {
                    showToast('Failed to send OTP: ' + response.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                let errorMessage = 'Failed to send OTP. Please try again.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = '';
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        errorMessage += messages.join(', ') + ' ';
                    });
                }

                showToast(errorMessage, 'error');
            },
            complete: function () {
                sendOtpBtn.prop('disabled', false).removeClass('btn-loading').html(originalButtonText);
            }
        });
    }
    otpInputs.on('input', function () {
        const currentIndex = parseInt($(this).data('index'));
        const value = $(this).val();
        if (value.length === 1) {
            if (currentIndex < 5) {
                otpInputs.eq(currentIndex + 1).focus();
            }
        }
    });
    otpInputs.on('keydown', function (e) {
        const currentIndex = parseInt($(this).data('index'));
        if (e.key === 'Backspace') {
            if ($(this).val() === '') {
                if (currentIndex > 0) {
                    otpInputs.eq(currentIndex - 1).focus();
                }
            } else {
                $(this).val('');
            }
        }
        if (!/^[0-9]$/.test(e.key) &&
            e.key !== 'Backspace' &&
            e.key !== 'Delete' &&
            e.key !== 'Tab' &&
            e.key !== 'ArrowLeft' &&
            e.key !== 'ArrowRight') {
            e.preventDefault();
        }
    });
    function startCountdown() {
        countdownTime = 60;
        countdownElement.text(countdownTime);
        resendOtp.css({
            'pointer-events': 'none',
            'color': '#6c757d'
        });

        clearInterval(countdown);
        countdown = setInterval(function () {
            countdownTime--;
            countdownElement.text(countdownTime);

            if (countdownTime <= 0) {
                clearInterval(countdown);
                resendOtp.css({
                    'pointer-events': 'auto',
                    'color': '#0d6efd'
                });
                countdownElement.text('0');
            }
        }, 1000);
    }
    resendOtp.on('click', function () {
        if (countdownTime > 0) return;
        sendOtp();
    });
    loginForm.on('submit', function (e) {
        e.preventDefault();
        clearFormErrors();
        const form = $(this);
        const originalButtonText = submitBtn.html();
        submitBtn.prop('disabled', true).addClass('btn-loading').html('Signing In...');
        if (loginMethod.val() === 'otp') {
            let otp = '';
            let isValid = true;
            otpInputs.each(function () {
                if ($(this).val() === '') {
                    isValid = false;
                } else {
                    otp += $(this).val();
                }
            });
            if (!isValid || otp.length !== 6) {
                showToast('Please enter the complete 6-digit OTP.', 'error');
                submitBtn.prop('disabled', false).removeClass('btn-loading').html(originalButtonText);
                return;
            }
            if ($('#otp_code').length === 0) {
                form.append('<input type="hidden" name="otp_code" id="otp_code" value="' + otp + '">');
            } else {
                $('#otp_code').val(otp);
            }
        }
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: form.serialize(),
            success: function (response) {
                if (response.redirect) {
                    showToast(response.message || 'Login successful!', 'success');
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1000);
                } else {
                    showToast(response.message || 'Login successful!', 'success');
                }
            },
            error: function (xhr, status, error) {
                console.error('Login Error:', error);
                let errorMessage = 'Login failed. Please try again.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = '';
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        errorMessage += messages.join(', ') + ' ';
                    });
                }

                showToast(errorMessage, 'error');
                showFormError(errorMessage);
            },
            complete: function () {
                submitBtn.prop('disabled', false).removeClass('btn-loading').html(originalButtonText);
            }
        });
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});