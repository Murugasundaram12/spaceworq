@extends('layouts.website')

@section('title', 'My Profile — SpaceWorq')
@section('body_class', 'expansion-page-body')

@push('styles')
  @include('customer.auth._styles')
  <style>
    .customer-profile-shell {
      padding: 40px 0 64px;
    }
    .customer-profile-grid {
      display: grid;
      grid-template-columns: 1.4fr 1fr;
      gap: 24px;
      align-items: start;
    }
    .customer-panel-card {
      background: #FFFFFF;
      border: 1px solid rgba(200, 225, 226, 0.75);
      border-radius: 16px;
      padding: 28px 30px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }
    .customer-panel-title {
      font-size: 1.25rem;
      font-weight: 800;
      color: #17363B;
      margin-bottom: 6px;
    }
    .customer-panel-sub {
      font-size: 0.9rem;
      color: #6B858A;
      margin-bottom: 22px;
    }
    .profile-row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    @media (max-width: 900px) {
      .customer-profile-grid,
      .profile-row-2 {
        grid-template-columns: 1fr;
      }
    }
  </style>
@endpush

@section('content')
  <main class="customer-profile-shell">
    <div class="container">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:24px;">
        <div>
          <span style="font-size:0.78rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:1px;">Customer Account</span>
          <h1 style="font-size:1.85rem; font-weight:800; color:#17363B; margin-top:4px;">{{ $user->first_name }} {{ $user->last_name }}</h1>
          <p style="font-size:0.92rem; color:#6B858A;">{{ $user->email }} @if($user->mobile) • {{ $user->mobile }} @endif</p>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
          @csrf
          <button type="submit" class="nav-signin-btn" style="border:1px solid var(--border-color); padding:10px 20px;">Sign Out</button>
        </form>
      </div>

      <div class="customer-profile-grid">
        <section class="customer-panel-card">
          <h2 class="customer-panel-title">Profile Details</h2>
          <p class="customer-panel-sub">Update your personal details and corporate billing information.</p>

          <form id="customerProfileForm" method="POST" action="{{ route('customer.profile.update') }}">
            @csrf
            @method('PATCH')

            <div class="profile-row-2">
              <div class="auth-field">
                <label class="auth-label" for="profileFirstName">First Name</label>
                <div class="auth-input-wrap">
                  <input type="text" class="auth-input" id="profileFirstName" name="first_name" value="{{ old('first_name', $user->first_name) }}" required />
                </div>
              </div>

              <div class="auth-field">
                <label class="auth-label" for="profileLastName">Last Name</label>
                <div class="auth-input-wrap">
                  <input type="text" class="auth-input" id="profileLastName" name="last_name" value="{{ old('last_name', $user->last_name) }}" required />
                </div>
              </div>
            </div>

            <div class="profile-row-2">
              <div class="auth-field">
                <label class="auth-label" for="profileMobile">Mobile (10 digits)</label>
                <div class="auth-input-wrap">
                  <input type="tel" class="auth-input" id="profileMobile" name="mobile" value="{{ old('mobile', $user->mobile) }}" maxlength="10" placeholder="9876543210" />
                </div>
              </div>

              <div class="auth-field">
                <label class="auth-label" for="profileCity">Preferred City</label>
                <div class="auth-input-wrap">
                  <select class="auth-input" id="profileCity" name="preferred_city_id">
                    <option value="">Select preferred city</option>
                    @foreach ($cities as $city)
                      <option value="{{ $city->id }}" {{ (string) old('preferred_city_id', $profile?->preferred_city_id) === (string) $city->id ? 'selected' : '' }}>
                        {{ $city->name }}
                      </option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>

            <div class="profile-row-2">
              <div class="auth-field">
                <label class="auth-label" for="profileCompany">Company Name</label>
                <div class="auth-input-wrap">
                  <input type="text" class="auth-input" id="profileCompany" name="company_name" value="{{ old('company_name', $profile?->company_name) }}" placeholder="Acme Labs Pvt Ltd" />
                </div>
              </div>

              <div class="auth-field">
                <label class="auth-label" for="profileGstin">GSTIN (Optional)</label>
                <div class="auth-input-wrap">
                  <input type="text" class="auth-input" id="profileGstin" name="gstin" value="{{ old('gstin', $profile?->gstin) }}" placeholder="29ABCDE1234F1Z5" />
                </div>
              </div>
            </div>

            <div class="auth-field">
              <label class="auth-label" for="profileAddress">Billing Address</label>
              <div class="auth-input-wrap">
                <textarea class="auth-input" id="profileAddress" name="billing_address" rows="3" style="padding:4px 0; resize:vertical;">{{ old('billing_address', $profile?->billing_address) }}</textarea>
              </div>
            </div>

            <button type="submit" class="btn-auth-submit" style="margin-top:8px;">Save Profile Changes</button>
          </form>
        </section>

        <section class="customer-panel-card">
          <h2 class="customer-panel-title">Change Password</h2>
          <p class="customer-panel-sub">Use at least 8 characters to keep your account secure.</p>

          <form id="customerPasswordForm" method="POST" action="{{ route('customer.password.change') }}">
            @csrf

            <div class="auth-field">
              <label class="auth-label" for="currentPassword">Current Password</label>
              <div class="auth-input-wrap">
                <input type="password" class="auth-input" id="currentPassword" name="current_password" required autocomplete="current-password" />
              </div>
            </div>

            <div class="auth-field">
              <label class="auth-label" for="newPassword">New Password</label>
              <div class="auth-input-wrap">
                <input type="password" class="auth-input" id="newPassword" name="password" required autocomplete="new-password" />
              </div>
            </div>

            <div class="auth-field">
              <label class="auth-label" for="newPasswordConfirm">Confirm New Password</label>
              <div class="auth-input-wrap">
                <input type="password" class="auth-input" id="newPasswordConfirm" name="password_confirmation" required autocomplete="new-password" />
              </div>
            </div>

            <button type="submit" class="btn-auth-submit" style="margin-top:8px;">Update Password</button>
          </form>
        </section>
      </div>
    </div>
  </main>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Dropdown + drawer + modal behaviors from website shell
      const partnerDropdownItem = document.getElementById('partnerDropdownItem');
      const partnerDropdownBtn = document.getElementById('partnerDropdownBtn');
      const partnerDropdownMenu = document.getElementById('partnerDropdownMenu');
      partnerDropdownBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = partnerDropdownMenu?.classList.contains('open');
        partnerDropdownMenu?.classList.toggle('open', !isOpen);
        partnerDropdownItem?.classList.toggle('open', !isOpen);
      });
      document.addEventListener('click', () => {
        partnerDropdownMenu?.classList.remove('open');
        partnerDropdownItem?.classList.remove('open');
      });
    });
  </script>
@endpush
