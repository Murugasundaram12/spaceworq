<?php

use App\Http\Controllers\Web\Admin\AdminAuthController;
use App\Http\Controllers\Web\Admin\AdminPortalController;
use App\Http\Controllers\Web\Customer\CustomerAuthController;
use App\Http\Controllers\Web\Provider\ProviderAuthController;
use App\Http\Controllers\Web\Provider\ProviderPortalController;
use App\Http\Controllers\Web\Public\WebsiteController;
use Illuminate\Support\Facades\Route;

// Public Website Discovery & Acquisition Forms
Route::get('/', [WebsiteController::class, 'home'])->name('home');
Route::get('/expansion', [WebsiteController::class, 'expansion'])->name('website.expansion');
Route::get('/workspace-providers', [WebsiteController::class, 'workspaceProviders'])->name('website.workspace-providers');
Route::get('/building-owners', [WebsiteController::class, 'buildingOwners'])->name('website.building-owners');
Route::get('/developers', [WebsiteController::class, 'developers'])->name('website.developers');

Route::middleware('throttle:inquiries')->group(function (): void {
    Route::post('/consultations', [WebsiteController::class, 'storeConsultation'])->name('website.consultations.store');
    Route::post('/partner-inquiries', [WebsiteController::class, 'storePartnerInquiry'])->name('website.partner-inquiries.store');
});

// Customer Web Session Authentication & Onboarding Profile
Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
Route::get('/forgot-password', [CustomerAuthController::class, 'showForgotPassword'])->name('password.request');
Route::get('/reset-password/{token}', [CustomerAuthController::class, 'showResetPassword'])->name('password.reset');
Route::get('/auth/verify-email', [CustomerAuthController::class, 'verifyEmail'])->name('verification.verify');

Route::middleware('throttle:auth')->group(function (): void {
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('customer.login.submit');
    Route::post('/register', [CustomerAuthController::class, 'register'])->name('customer.register.submit');
    Route::post('/forgot-password', [CustomerAuthController::class, 'sendResetLink'])->name('password.email');
    Route::post('/reset-password', [CustomerAuthController::class, 'resetPassword'])->name('password.update');
    Route::post('/auth/verify-email', [CustomerAuthController::class, 'verifyEmail'])->name('customer.activation.verify');
    Route::post('/auth/resend-activation', [CustomerAuthController::class, 'resendActivation'])->name('customer.activation.resend');
});

Route::middleware('throttle:otp')->group(function (): void {
    Route::post('/auth/otp/send', [CustomerAuthController::class, 'sendOtp'])->name('customer.otp.send');
    Route::post('/auth/otp/verify', [CustomerAuthController::class, 'verifyOtp'])->name('customer.otp.verify');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
});

Route::prefix('customer')->name('customer.')->middleware(['auth', 'user.type:customer'])->group(function (): void {
    Route::get('/profile', [CustomerAuthController::class, 'profile'])->name('profile.show');
    Route::patch('/profile', [CustomerAuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/change-password', [CustomerAuthController::class, 'changePassword'])->name('password.change');
});

// Provider / Vendor Web Session Authentication & Strict Workflow
Route::get('/auth/activate-provider', [ProviderAuthController::class, 'activate'])->name('provider.activate.link');

Route::prefix('provider')->name('provider.')->group(function (): void {
    Route::get('/login', [ProviderAuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [ProviderAuthController::class, 'showRegister'])->name('register');
    Route::get('/forgot-password', [ProviderAuthController::class, 'showForgotPassword'])->name('password.request');
    Route::get('/reset-password/{token}', [ProviderAuthController::class, 'showResetPassword'])->name('password.reset');

    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/login', [ProviderAuthController::class, 'login'])->name('login.submit');
        Route::post('/register', [ProviderAuthController::class, 'register'])->name('register.submit');
        Route::post('/forgot-password', [ProviderAuthController::class, 'sendResetLink'])->name('password.email');
        Route::post('/reset-password', [ProviderAuthController::class, 'resetPassword'])->name('password.update');
        Route::post('/activate', [ProviderAuthController::class, 'activate'])->name('activate.submit');
        Route::post('/resend-activation', [ProviderAuthController::class, 'resendActivation'])->name('activation.resend');
    });

    Route::middleware(['auth', 'user.type:provider'])->group(function (): void {
        Route::post('/logout', [ProviderAuthController::class, 'logout'])->name('logout');

        // Read-only application status & onboarding overview (allowed for pending, rejected, suspended, active)
        Route::middleware('provider.access:status')->group(function (): void {
            Route::get('/status', [ProviderPortalController::class, 'status'])->name('status');
            Route::get('/profile', [ProviderPortalController::class, 'showProfile'])->name('profile.show');
        });

        // Onboarding mutations & private KYC document download (allowed for pending, rejected, active; blocked for suspended)
        Route::middleware('provider.access:onboarding')->group(function (): void {
            Route::patch('/profile', [ProviderPortalController::class, 'updateProfile'])->name('profile.update');
            Route::post('/documents', [ProviderPortalController::class, 'uploadDocument'])->name('documents.store');
            Route::get('/documents/{document}/download', [ProviderPortalController::class, 'downloadDocument'])->name('documents.download');
        });

        // Approved operational vendor features (strictly requires canonical ACTIVE status + KYC_VERIFIED)
        Route::middleware('provider.access:true')->group(function (): void {
            Route::get('/dashboard', [ProviderPortalController::class, 'dashboard'])
                ->middleware('provider.permission:providers.dashboard.view')
                ->name('dashboard');

            Route::middleware('provider.permission:providers.workspaceManagement.view')->prefix('workspaces')->name('workspaces.')->group(function (): void {
                Route::get('/', [ProviderPortalController::class, 'workspaces'])->name('index');
                Route::post('/', [ProviderPortalController::class, 'storeWorkspace'])->name('store');
                Route::patch('/{workspace}', [ProviderPortalController::class, 'updateWorkspace'])->name('update');
                Route::delete('/{workspace}', [ProviderPortalController::class, 'destroyWorkspace'])->name('destroy');
                Route::post('/{workspace}/submit-review', [ProviderPortalController::class, 'submitWorkspaceForReview'])->name('submit-review');
                Route::post('/{workspace}/images', [ProviderPortalController::class, 'uploadWorkspaceImage'])->name('images.store');
                Route::delete('/{workspace}/images/{image}', [ProviderPortalController::class, 'deleteWorkspaceImage'])->name('images.destroy');
            });

            Route::middleware('provider.permission:providers.floors.view')->group(function (): void {
                Route::put('/workspaces/{workspace}/floors', [ProviderPortalController::class, 'upsertWorkspaceFloors'])->name('workspaces.floors.upsert');
            });

            Route::middleware('provider.permission:providers.users.view')->prefix('users')->name('users.')->group(function (): void {
                Route::get('/', [ProviderPortalController::class, 'users'])->name('index');
                Route::post('/', [ProviderPortalController::class, 'storeStaff'])->name('store');
                Route::patch('/{staff}', [ProviderPortalController::class, 'updateStaff'])->name('update');
                Route::delete('/{staff}', [ProviderPortalController::class, 'destroyStaff'])->name('destroy');
            });

            Route::middleware('provider.permission:providers.roleManagement.view')->prefix('roles')->name('roles.')->group(function (): void {
                Route::get('/', [ProviderPortalController::class, 'roles'])->name('index');
                Route::post('/', [ProviderPortalController::class, 'storeRole'])->name('store');
                Route::patch('/{role}', [ProviderPortalController::class, 'updateRole'])->name('update');
                Route::delete('/{role}', [ProviderPortalController::class, 'destroyRole'])->name('destroy');
            });

            Route::middleware('provider.permission:providers.layoutDesigner.view')->prefix('templates')->name('templates.')->group(function (): void {
                Route::get('/', [ProviderPortalController::class, 'templates'])->name('index');
                Route::post('/', [ProviderPortalController::class, 'storeTemplate'])->name('store');
                Route::patch('/{template}', [ProviderPortalController::class, 'updateTemplate'])->name('update');
                Route::delete('/{template}', [ProviderPortalController::class, 'destroyTemplate'])->name('destroy');
            });

            Route::middleware('provider.permission:providers.templateMapping.view')->group(function (): void {
                Route::get('/template-mapping', [ProviderPortalController::class, 'templateMappings'])->name('template-mapping.index');
                Route::post('/template-mapping', [ProviderPortalController::class, 'mapTemplate'])->name('template-mapping.store');
            });
        });
    });
});

// Super Admin Web Session Authentication & Operations
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:auth')->name('login.submit');

    Route::middleware(['auth', 'super.admin'])->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::middleware('permission:admin.read')->get('/dashboard', [AdminPortalController::class, 'dashboard'])->name('dashboard');

        Route::middleware('permission:admin.providers.manage')->group(function (): void {
            Route::get('/providers', [AdminPortalController::class, 'providers'])->name('providers.index');
            Route::get('/providers/{provider}', [AdminPortalController::class, 'showProvider'])->name('providers.show');
            Route::patch('/providers/{provider}/status', [AdminPortalController::class, 'updateProviderStatus'])->name('providers.status');
            Route::post('/providers/{provider}/verify-kyc', [AdminPortalController::class, 'verifyProviderKyc'])->name('providers.verify-kyc');
            Route::get('/provider-documents/{document}/download', [AdminPortalController::class, 'downloadProviderDocument'])->name('provider-documents.download');

            Route::get('/customers', [AdminPortalController::class, 'customers'])->name('customers.index');
            Route::patch('/customers/{user}/status', [AdminPortalController::class, 'updateCustomerStatus'])->name('customers.status');
        });

        Route::middleware('permission:admin.workspaces.approve')->group(function (): void {
            Route::get('/workspaces', [AdminPortalController::class, 'workspaces'])->name('workspaces.index');
            Route::post('/workspaces/{workspace}/decision', [AdminPortalController::class, 'decideWorkspace'])->name('workspaces.decision');
        });

        Route::middleware('permission:admin.support.manage')->group(function (): void {
            Route::get('/consultations', [AdminPortalController::class, 'consultations'])->name('consultations.index');
            Route::patch('/consultations/{consultation}', [AdminPortalController::class, 'updateConsultation'])->name('consultations.update');
            Route::get('/partner-inquiries', [AdminPortalController::class, 'partnerInquiries'])->name('partner-inquiries.index');
            Route::patch('/partner-inquiries/{inquiry}', [AdminPortalController::class, 'updatePartnerInquiry'])->name('partner-inquiries.update');
            Route::get('/support-tickets', [AdminPortalController::class, 'supportTickets'])->name('support-tickets.index');
            Route::patch('/support-tickets/{ticket}', [AdminPortalController::class, 'updateSupportTicket'])->name('support-tickets.update');
        });

        Route::middleware('permission:admin.reviews.manage')->group(function (): void {
            Route::get('/reviews', [AdminPortalController::class, 'reviews'])->name('reviews.index');
            Route::patch('/reviews/{review}/moderate', [AdminPortalController::class, 'moderateReview'])->name('reviews.moderate');
        });

        Route::middleware('permission:admin.master_data.manage')->group(function (): void {
            Route::get('/master-data', [AdminPortalController::class, 'masterData'])->name('master-data.index');
            Route::post('/cities', [AdminPortalController::class, 'storeCity'])->name('cities.store');
            Route::patch('/cities/{city}', [AdminPortalController::class, 'updateCity'])->name('cities.update');
        });

        Route::middleware('permission:admin.audit_logs.view')->group(function (): void {
            Route::get('/audit-logs', [AdminPortalController::class, 'auditLogs'])->name('audit-logs.index');
        });
    });
});
