<style>

    /* Specific styles for SpaceWorq Auth Login Screen (Matches production bundle) */
    body.auth-page-body {
      background-color: #EDF6F9;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
      padding: 0;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .auth-page-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 32px 16px 48px 16px;
      width: 100%;
      box-sizing: border-box;
    }

    .auth-content-wrap {
      width: 100%;
      max-width: 460px;
      margin: 0 auto;
    }

    /* Top Row: Back Button */
    .auth-top-row {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      margin-bottom: 20px;
    }

    .auth-back-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #FFFFFF;
      border: 1px solid var(--border-color);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--text-heading);
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      box-shadow: var(--shadow-sm);
    }

    .auth-back-btn:hover {
      background-color: var(--primary-light);
      border-color: var(--primary);
      color: var(--primary);
      transform: translateX(-2px);
    }

    /* Brand Block (Matches bundle logoWrap) */
    .auth-brand-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      margin-bottom: 24px;
    }

    .auth-logo-img {
      height: 52px;
      width: auto;
      object-fit: contain;
      margin-bottom: 10px;
    }

    .auth-brand-title {
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--text-heading);
      letter-spacing: -0.3px;
      margin: 0;
    }

    .auth-brand-tagline {
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* Redirect Banner (when redirected from a partner or details route) */
    .redirect-banner {
      display: none;
      align-items: center;
      gap: 10px;
      background: rgba(0, 108, 118, 0.08);
      border: 1px solid rgba(0, 108, 118, 0.25);
      border-radius: 12px;
      padding: 10px 14px;
      margin-bottom: 18px;
      font-size: 0.85rem;
      color: var(--primary);
      font-weight: 600;
    }

    .redirect-banner.active {
      display: flex;
    }

    .redirect-banner svg {
      width: 16px;
      height: 16px;
      flex-shrink: 0;
    }

    /* Main Auth Card (Matches bundle card) */
    .auth-card {
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 32px 30px;
      box-shadow: 0 10px 30px rgba(0, 50, 60, 0.06);
      margin-bottom: 20px;
    }

    .auth-card-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text-heading);
      margin: 0 0 6px 0;
      letter-spacing: -0.2px;
    }

    .auth-card-sub {
      font-size: 0.88rem;
      color: var(--text-muted);
      margin: 0 0 22px 0;
      line-height: 1.45;
    }

    /* Form Fields */
    .auth-field {
      margin-bottom: 16px;
    }

    .auth-label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 6px;
    }

    .auth-input-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #F8FAFC;
      border: 1.5px solid var(--border-color);
      border-radius: 12px;
      padding: 11px 14px;
      transition: var(--transition);
    }

    .auth-input-wrap:focus-within {
      border-color: var(--primary);
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(0, 108, 118, 0.12);
    }

    .auth-input-wrap svg.input-icon {
      width: 18px;
      height: 18px;
      color: var(--text-muted);
      flex-shrink: 0;
    }

    .auth-input {
      flex: 1;
      border: none;
      background: transparent;
      outline: none;
      font-size: 0.95rem;
      color: var(--text-heading);
      font-family: inherit;
    }

    .auth-input::placeholder {
      color: #94A3B8;
    }

    .auth-eye-btn {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }

    .auth-eye-btn:hover {
      color: var(--text-heading);
    }

    .auth-error-box {
      display: none;
      padding: 10px 14px;
      border-radius: 10px;
      background: #FEE2E2;
      border: 1px solid #FCA5A5;
      color: #DC2626;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 16px;
      text-align: center;
    }

    .auth-error-box.active {
      display: block;
    }

    .btn-auth-submit {
      width: 100%;
      padding: 14px;
      border-radius: 14px;
      background-color: var(--primary);
      color: #FFFFFF;
      font-size: 1rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: 0 4px 14px rgba(0, 108, 118, 0.28);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 6px;
    }

    .btn-auth-submit:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0, 108, 118, 0.38);
    }

    .btn-auth-submit:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
    }

    /* Footer Navigation and Guest Buttons (Matches bundle footer & guestBtn) */
    .auth-footer {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      font-size: 0.88rem;
      color: var(--text-muted);
      margin-bottom: 14px;
    }

    .auth-footer a {
      color: var(--primary);
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
    }

    .auth-footer a:hover {
      text-decoration: underline;
    }

    .auth-guest-btn {
      width: 100%;
      text-align: center;
      padding: 11px 16px;
      border-radius: var(--radius-full);
      background-color: transparent;
      border: 1px solid var(--border-color);
      color: var(--text-main);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: block;
      text-decoration: none;
      margin-bottom: 14px;
    }

    .auth-guest-btn:hover {
      background-color: #FFFFFF;
      border-color: var(--primary);
      color: var(--primary);
    }

    .auth-resend-link {
      display: block;
      text-align: center;
      font-size: 0.82rem;
      color: var(--primary);
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: color 0.2s;
    }

    .auth-resend-link:hover {
      text-decoration: underline;
    }

    /* Resend Activation Modal */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      z-index: 300;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal-content-card {
      background: #FFFFFF;
      border-radius: 20px;
      border: 1px solid var(--border-color);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 440px;
      padding: 28px;
      position: relative;
    }

    .modal-header-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .modal-close-icon {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-close-icon:hover {
      color: var(--text-heading);
    }
  
</style>