@if (session('status'))
  <div class="alert alert-success alert-dismissible fade show sw-flash-alert" role="alert" style="margin-bottom: 16px; border-radius: 12px; padding: 12px 16px; background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; font-size: 0.9rem;">
    <strong>Success:</strong> {{ session('status') }}
    @if (session('activation_url'))
      <div class="mt-1" style="margin-top: 6px; word-break: break-all;">
        Activation link: <a href="{{ session('activation_url') }}" style="color: inherit; text-decoration: underline;">{{ session('activation_url') }}</a>
      </div>
    @endif
  </div>
@endif

@if (session('error') || session('status_error'))
  <div class="alert alert-danger alert-dismissible fade show sw-flash-alert" role="alert" style="margin-bottom: 16px; border-radius: 12px; padding: 12px 16px; background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; font-size: 0.9rem;">
    {{ session('error') ?: session('status_error') }}
  </div>
@endif

@if ($errors->any())
  <div class="alert alert-danger sw-flash-alert" role="alert" style="margin-bottom: 16px; border-radius: 12px; padding: 12px 16px; background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; font-size: 0.9rem;">
    <ul style="margin: 0; padding-left: 18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
