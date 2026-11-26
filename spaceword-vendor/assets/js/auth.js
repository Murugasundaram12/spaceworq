/**
 * AdminPanel — Authentication Interactions & Validation
 */
(function () {
  "use strict";

  const EYE_ICON_SVG = `
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
      <circle cx="12" cy="12" r="3"></circle>
    </svg>`;

  const EYE_OFF_ICON_SVG = `
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-6.5 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94"></path>
      <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"></path>
      <line x1="1" y1="1" x2="23" y2="23"></line>
      <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
    </svg>`;

  /* --------------------------------------------------------------------------
     Password Visibility Toggles
     -------------------------------------------------------------------------- */
  function initPasswordToggles() {
    document.querySelectorAll("[data-toggle-password]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const targetId = btn.getAttribute("data-toggle-password");
        const input = document.getElementById(targetId);
        if (!input) return;

        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        btn.innerHTML = isPassword ? EYE_OFF_ICON_SVG : EYE_ICON_SVG;
        btn.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
        btn.setAttribute("aria-pressed", String(isPassword));
      });
    });
  }

  /* --------------------------------------------------------------------------
     Field Validation Helpers
     -------------------------------------------------------------------------- */
  function setFieldError(input, message) {
    if (!input) return;
    input.classList.add("is-invalid");
    const errorEl = input.closest(".sw-field")?.querySelector(".sw-error-msg");
    if (errorEl && message) {
      errorEl.textContent = message;
    }
  }

  function clearFieldError(input) {
    if (!input) return;
    input.classList.remove("is-invalid");
  }

  function showAlert(containerId, type, message) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = `
      <div class="sw-alert sw-alert-${type}" role="alert">
        ${message}
      </div>`;
  }

  function clearAlert(containerId) {
    const container = document.getElementById(containerId);
    if (container) container.innerHTML = "";
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
  }

  function isValidMobile(mobile) {
    const digits = mobile.replace(/[\s\-()+]/g, "");
    return /^\d{7,15}$/.test(digits);
  }

  function setButtonLoading(button, isLoading, loadingText, defaultText) {
    if (!button) return;
    if (isLoading) {
      button.disabled = true;
      button.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span>${loadingText}</span>`;
    } else {
      button.disabled = false;
      button.textContent = defaultText;
    }
  }

  /* --------------------------------------------------------------------------
     Login Form Handler
     -------------------------------------------------------------------------- */
  function initLoginForm() {
    const form = document.getElementById("loginForm");
    if (!form) return;

    const identifierInput = document.getElementById("loginIdentifier");
    const passwordInput = document.getElementById("loginPassword");
    const submitBtn = document.getElementById("loginSubmitBtn");

    [identifierInput, passwordInput].forEach(function (input) {
      input?.addEventListener("input", function () {
        clearFieldError(input);
        clearAlert("loginAlert");
      });
    });

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      clearAlert("loginAlert");

      let valid = true;
      const identifierVal = identifierInput.value.trim();
      const passwordVal = passwordInput.value;

      if (!identifierVal) {
        setFieldError(identifierInput, "Please enter your email, mobile, or username.");
        valid = false;
      }

      if (!passwordVal) {
        setFieldError(passwordInput, "Please enter your password.");
        valid = false;
      } else if (passwordVal.length < 6) {
        setFieldError(passwordInput, "Password must be at least 6 characters.");
        valid = false;
      }

      if (!valid) return;

      setButtonLoading(submitBtn, true, "Signing in...", "Sign in");

      // Store session details and redirect to dashboard
      const displayName = identifierVal.includes("@")
        ? identifierVal.split("@")[0]
        : identifierVal;
      const emailDisplay = identifierVal.includes("@")
        ? identifierVal
        : `${identifierVal}@adminpanel.com`;

      try {
        window.localStorage.setItem(
          "adminpanel_user",
          JSON.stringify({
            name: displayName || "admin",
            email: emailDisplay,
            role: "Admin",
            portalSubtitle: "System Administrator Portal",
          })
        );
      } catch (_) {
        // Ignore storage errors in restricted environments
      }

      window.setTimeout(function () {
        showAlert(
          "loginAlert",
          "success",
          "Signed in successfully. Redirecting to your dashboard..."
        );
        window.setTimeout(function () {
          window.location.href = "dashboard.html";
        }, 450);
      }, 650);
    });
  }

  /* --------------------------------------------------------------------------
     Register Form Handler & Password Strength Indicator
     -------------------------------------------------------------------------- */
  function evaluatePasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    return score;
  }

  function initRegisterForm() {
    const form = document.getElementById("registerForm");
    if (!form) return;

    const firstNameInput = document.getElementById("firstName");
    const lastNameInput = document.getElementById("lastName");
    const emailInput = document.getElementById("registerEmail");
    const mobileInput = document.getElementById("registerMobile");
    const passwordInput = document.getElementById("registerPassword");
    const confirmInput = document.getElementById("confirmPassword");
    const submitBtn = document.getElementById("registerSubmitBtn");

    const strengthWrap = document.getElementById("passwordStrengthWrap");
    const strengthBars = document.querySelectorAll("#passwordStrengthWrap .strength-bar");
    const strengthLabel = document.getElementById("passwordStrengthLabel");

    const allInputs = [
      firstNameInput,
      lastNameInput,
      emailInput,
      mobileInput,
      passwordInput,
      confirmInput,
    ];

    allInputs.forEach(function (input) {
      input?.addEventListener("input", function () {
        clearFieldError(input);
        clearAlert("registerAlert");
      });
    });

    // Live password strength feedback
    passwordInput?.addEventListener("input", function () {
      const value = passwordInput.value;
      if (!strengthWrap) return;

      if (!value) {
        strengthWrap.classList.remove("visible");
        return;
      }

      strengthWrap.classList.add("visible");
      const score = evaluatePasswordStrength(value);
      const levels = [
        { label: "Weak password", color: "#ef4444" },
        { label: "Fair password", color: "#f59e0b" },
        { label: "Good password", color: "#3b82f6" },
        { label: "Strong password", color: "#16a34a" },
      ];
      const activeIndex = Math.max(0, Math.min(score - 1, 3));
      const activeLevel = levels[activeIndex];

      strengthBars.forEach(function (bar, idx) {
        bar.style.backgroundColor = idx < score ? activeLevel.color : "#dfe3ec";
      });

      if (strengthLabel) {
        strengthLabel.textContent = activeLevel.label;
        strengthLabel.style.color = activeLevel.color;
      }
    });

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      clearAlert("registerAlert");

      let valid = true;

      if (!firstNameInput.value.trim()) {
        setFieldError(firstNameInput, "First name is required.");
        valid = false;
      }

      if (!lastNameInput.value.trim()) {
        setFieldError(lastNameInput, "Last name is required.");
        valid = false;
      }

      if (!emailInput.value.trim()) {
        setFieldError(emailInput, "Email address is required.");
        valid = false;
      } else if (!isValidEmail(emailInput.value)) {
        setFieldError(emailInput, "Please enter a valid email address.");
        valid = false;
      }

      if (!mobileInput.value.trim()) {
        setFieldError(mobileInput, "Mobile number is required.");
        valid = false;
      } else if (!isValidMobile(mobileInput.value)) {
        setFieldError(mobileInput, "Please enter a valid mobile number.");
        valid = false;
      }

      if (!passwordInput.value) {
        setFieldError(passwordInput, "Password is required.");
        valid = false;
      } else if (passwordInput.value.length < 8) {
        setFieldError(passwordInput, "Password must be at least 8 characters.");
        valid = false;
      }

      if (!confirmInput.value) {
        setFieldError(confirmInput, "Please confirm your password.");
        valid = false;
      } else if (confirmInput.value !== passwordInput.value) {
        setFieldError(confirmInput, "Passwords do not match.");
        valid = false;
      }

      if (!valid) return;

      setButtonLoading(submitBtn, true, "Creating account...", "Create partner account");

      // Store newly registered partner session and redirect to dashboard
      const fullName = `${firstNameInput.value.trim()} ${lastNameInput.value.trim()}`.trim();
      try {
        window.localStorage.setItem(
          "adminpanel_user",
          JSON.stringify({
            name: firstNameInput.value.trim() || fullName || "admin",
            email: emailInput.value.trim(),
            role: "Partner Admin",
            portalSubtitle: "Space Partner Administrator Portal",
          })
        );
      } catch (_) {
        // Ignore storage errors in restricted environments
      }

      window.setTimeout(function () {
        showAlert(
          "registerAlert",
          "success",
          "Partner account created! Redirecting to your dashboard..."
        );
        window.setTimeout(function () {
          window.location.href = "dashboard.html";
        }, 500);
      }, 700);
    });
  }

  /* --------------------------------------------------------------------------
     Modal Forms on Login Page (Forgot Password & Resend Activation)
     -------------------------------------------------------------------------- */
  function initSupportModals() {
    const forgotForm = document.getElementById("forgotPasswordForm");
    if (forgotForm) {
      const emailInput = document.getElementById("forgotEmail");
      emailInput?.addEventListener("input", function () {
        clearFieldError(emailInput);
        clearAlert("forgotAlert");
      });

      forgotForm.addEventListener("submit", function (event) {
        event.preventDefault();
        clearAlert("forgotAlert");

        if (!emailInput.value.trim() || !isValidEmail(emailInput.value)) {
          setFieldError(emailInput, "Please enter a valid registered email address.");
          return;
        }

        showAlert(
          "forgotAlert",
          "success",
          "If an account exists for that email, a password reset link has been sent."
        );
        forgotForm.reset();
      });
    }

    const activationForm = document.getElementById("resendActivationForm");
    if (activationForm) {
      const emailInput = document.getElementById("activationEmail");
      emailInput?.addEventListener("input", function () {
        clearFieldError(emailInput);
        clearAlert("activationAlert");
      });

      activationForm.addEventListener("submit", function (event) {
        event.preventDefault();
        clearAlert("activationAlert");

        if (!emailInput.value.trim() || !isValidEmail(emailInput.value)) {
          setFieldError(emailInput, "Please enter a valid registered email address.");
          return;
        }

        showAlert(
          "activationAlert",
          "success",
          "Activation email resent! Please check your inbox and spam folder."
        );
        activationForm.reset();
      });
    }
  }

  /* --------------------------------------------------------------------------
     Bootstrap Initialization
     -------------------------------------------------------------------------- */
  document.addEventListener("DOMContentLoaded", function () {
    initPasswordToggles();
    initLoginForm();
    initRegisterForm();
    initSupportModals();
  });
})();
