<?php

use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminMasterDataController;
use App\Http\Controllers\Api\V1\Admin\AdminOperationsController;
use App\Http\Controllers\Api\V1\Admin\AdminProviderController;
use App\Http\Controllers\Api\V1\Admin\AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\AdminWorkspaceApprovalController;
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\ProviderAuthController;
use App\Http\Controllers\Api\V1\Customer\BookingController;
use App\Http\Controllers\Api\V1\Customer\CustomerProfileController;
use App\Http\Controllers\Api\V1\Customer\PaymentController;
use App\Http\Controllers\Api\V1\Customer\ReserveRequestController;
use App\Http\Controllers\Api\V1\Customer\VaultController;
use App\Http\Controllers\Api\V1\Provider\ProviderDashboardController;
use App\Http\Controllers\Api\V1\Provider\ProviderInstantBookingController;
use App\Http\Controllers\Api\V1\Provider\ProviderProfileController;
use App\Http\Controllers\Api\V1\Provider\ProviderReportController;
use App\Http\Controllers\Api\V1\Provider\ProviderReserveRequestController;
use App\Http\Controllers\Api\V1\Provider\ProviderRoleController;
use App\Http\Controllers\Api\V1\Provider\ProviderStaffController;
use App\Http\Controllers\Api\V1\Provider\ProviderTemplateController;
use App\Http\Controllers\Api\V1\Provider\ProviderWorkspaceController;
use App\Http\Controllers\Api\V1\Public\InquiryController;
use App\Http\Controllers\Api\V1\Public\PublicCatalogController;
use App\Http\Controllers\Api\V1\Public\SupportFaqController;
use App\Http\Controllers\Api\V1\Public\WorkspaceReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    // Public Discovery & Website Endpoints
    Route::prefix('public')->name('public.')->group(function (): void {
        Route::get('/home', [PublicCatalogController::class, 'home'])->name('home');
    });
    Route::get('/cities', [PublicCatalogController::class, 'cities'])->name('cities.index');
    Route::get('/expansion-opportunities', [PublicCatalogController::class, 'expansion'])->name('expansion.index');
    Route::get('/support/faqs', [SupportFaqController::class, 'faqs'])->name('support.faqs');

    Route::prefix('workspaces/published')->name('workspaces.published.')->group(function (): void {
        Route::get('/', [PublicCatalogController::class, 'publishedWorkspaces'])->name('index');
        Route::get('/amenities', [PublicCatalogController::class, 'amenities'])->name('amenities');
        Route::get('/suggestions', [PublicCatalogController::class, 'suggestions'])->name('suggestions');
        Route::get('/compare', [PublicCatalogController::class, 'compare'])->name('compare');
        Route::get('/{workspace}', [PublicCatalogController::class, 'show'])->name('show');
        Route::get('/{workspace}/images/{image}/file', [PublicCatalogController::class, 'image'])->name('image');
        Route::get('/{workspace}/reviews', [WorkspaceReviewController::class, 'index'])->name('reviews.index');
        Route::match(['get', 'post'], '/{workspace}/instant-book/availability', [BookingController::class, 'availability'])->name('instant-book.availability');
        Route::post('/{workspace}/instant-book/quote', [BookingController::class, 'quote'])->name('instant-book.quote');

        Route::middleware(['auth:sanctum', 'user.type:customer'])->group(function (): void {
            Route::post('/{workspace}/reviews', [WorkspaceReviewController::class, 'upsert'])->name('reviews.upsert');
            Route::post('/{workspace}/instant-book', [BookingController::class, 'store'])
                ->middleware('throttle:booking')
                ->name('instant-book.store');
            Route::post('/{workspace}/reserve-request', [ReserveRequestController::class, 'store'])
                ->middleware('throttle:inquiries')
                ->name('reserve-request.store');
            Route::post('/{workspace}/reserve-request/reminder', [ReserveRequestController::class, 'remind'])
                ->middleware('throttle:inquiries')
                ->name('reserve-request.reminder');
        });
    });

    Route::middleware('throttle:inquiries')->group(function (): void {
        Route::post('/consultations', [InquiryController::class, 'storeConsultation'])->name('consultations.store');
        Route::post('/partner-inquiries', [InquiryController::class, 'storePartnerInquiry'])->name('partner-inquiries.store');
    });
    // Public & Customer Authentication
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::middleware('throttle:auth')->group(function (): void {
            Route::post('/register', [AuthController::class, 'register'])->name('register');
            Route::post('/register-provider', [ProviderAuthController::class, 'registerProvider'])->name('register-provider');
            Route::post('/login', [AuthController::class, 'login'])->name('login');
            Route::post('/customer/login', [AuthController::class, 'customerLogin'])->name('customer.login');
            Route::post('/provider/login', [ProviderAuthController::class, 'login'])->name('provider.login');
            Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify-email');
            Route::post('/activate-provider', [ProviderAuthController::class, 'activateProvider'])->name('activate-provider');
            Route::post('/resend-client-activation', [AuthController::class, 'resendClientActivation'])->name('resend-client-activation');
            Route::post('/resend-provider-activation', [ProviderAuthController::class, 'resendProviderActivation'])->name('resend-provider-activation');
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
            Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
        });

        Route::middleware('throttle:otp')->group(function (): void {
            Route::post('/send-otp', [AuthController::class, 'sendOtp'])->name('send-otp');
            Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
        });

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
            Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
        });
    });

    // Partner role lookup (compatible with official partners.spaceworq.com /users/role)
    Route::middleware(['auth:sanctum', 'user.type:provider'])->get('/users/role', [ProviderAuthController::class, 'roleContext'])->name('users.role');

    // Customer Account, Bookings, Payments, Reserve Requests, Vault & Support
    Route::middleware(['auth:sanctum', 'user.type:customer'])->group(function (): void {
        // Vault / Favorites
        Route::get('/workspaces/favorites', [VaultController::class, 'index'])->name('workspaces.favorites.index');
        Route::post('/workspaces/{workspace}/favorite', [VaultController::class, 'store'])->name('workspaces.favorites.store');
        Route::delete('/workspaces/{workspace}/favorite', [VaultController::class, 'destroy'])->name('workspaces.favorites.destroy');

        // Official / Canonical Instant Booking & Reserve Request Paths
        Route::get('/workspaces/instant-bookings/me', [BookingController::class, 'index'])->name('workspaces.instant-bookings.me');
        Route::post('/workspaces/instant-bookings/{booking}/complete', [BookingController::class, 'complete'])->name('workspaces.instant-bookings.complete');
        Route::get('/workspaces/reserve-requests/me', [ReserveRequestController::class, 'index'])->name('workspaces.reserve-requests.me');
        Route::post('/workspaces/reserve-requests/{reserveRequest}/cancel', [ReserveRequestController::class, 'cancel'])->name('workspaces.reserve-requests.cancel');

        Route::prefix('bookings')->name('bookings.')->group(function (): void {
            Route::get('/', [BookingController::class, 'index'])->name('index');
            Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
            Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
            Route::post('/{booking}/payments/initiate', [PaymentController::class, 'initiate'])
                ->middleware('throttle:booking')
                ->name('payments.initiate');
            Route::post('/{booking}/payments/verify', [PaymentController::class, 'verify'])
                ->middleware('throttle:booking')
                ->name('payments.verify');
        });

        Route::prefix('customer')->name('customer.')->group(function (): void {
            Route::get('/profile', [CustomerProfileController::class, 'show'])->name('profile.show');
            Route::patch('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
            Route::get('/notifications', [CustomerProfileController::class, 'notifications'])->name('notifications.index');
            Route::post('/notifications/{notificationId}/read', [CustomerProfileController::class, 'markNotificationRead'])->name('notifications.read');
        });

        Route::prefix('support')->name('support.')->group(function (): void {
            Route::get('/tickets', [CustomerProfileController::class, 'supportTickets'])->name('tickets.index');
            Route::post('/tickets', [CustomerProfileController::class, 'storeSupportTicket'])
                ->middleware('throttle:inquiries')
                ->name('tickets.store');
            Route::get('/tickets/{ticket}', [CustomerProfileController::class, 'showSupportTicket'])->name('tickets.show');
        });
    });

    // Provider / Vendor Panel
    Route::prefix('provider')->name('provider.')->middleware(['auth:sanctum', 'user.type:provider'])->group(function (): void {
        Route::middleware('provider.access:status')->get('/status', [ProviderProfileController::class, 'status'])->name('status');

        Route::middleware('provider.access')->group(function (): void {
            Route::get('/profile', [ProviderProfileController::class, 'show'])->name('profile.show');
            Route::patch('/profile', [ProviderProfileController::class, 'update'])->name('profile.update');
            Route::post('/documents', [ProviderProfileController::class, 'uploadDocument'])->name('documents.store');
            Route::get('/documents/{document}/download', [ProviderProfileController::class, 'downloadDocument'])->name('documents.download');
            Route::post('/bank-accounts', [ProviderProfileController::class, 'storeBankAccount'])->name('bank-accounts.store');
            Route::delete('/bank-accounts/{bankAccount}', [ProviderProfileController::class, 'deleteBankAccount'])->name('bank-accounts.destroy');

            Route::middleware('provider.permission:providers.reserveRequests.view')->prefix('reserve-requests')->name('reserve-requests.')->group(function (): void {
                Route::get('/', [ProviderReserveRequestController::class, 'index'])->name('index');
                Route::patch('/{reserveRequest}/status', [ProviderReserveRequestController::class, 'updateStatus'])->name('status');
                Route::post('/{reserveRequest}/assign', [ProviderReserveRequestController::class, 'assign'])->name('assign');
            });

            Route::middleware('provider.permission:providers.instantBookingRequests.view')->prefix('instant-bookings')->name('instant-bookings.')->group(function (): void {
                Route::get('/', [ProviderInstantBookingController::class, 'index'])->name('index');
                Route::post('/reset-past', [ProviderInstantBookingController::class, 'resetPast'])->name('reset-past');
                Route::patch('/{booking}/status', [ProviderInstantBookingController::class, 'updateStatus'])->name('status');
                Route::post('/{booking}/assign', [ProviderInstantBookingController::class, 'assign'])->name('assign');
            });

            Route::middleware('provider.permission:providers.reports.view')->prefix('reports')->name('reports.')->group(function (): void {
                Route::get('/reserve-requests', [ProviderReportController::class, 'reserveRequests'])->name('reserve-requests');
                Route::get('/reserve-requests/export', [ProviderReportController::class, 'reserveRequestsExport'])->name('reserve-requests.export');
                Route::get('/instant-bookings', [ProviderReportController::class, 'instantBookings'])->name('instant-bookings');
                Route::get('/instant-bookings/export', [ProviderReportController::class, 'instantBookingsExport'])->name('instant-bookings.export');
            });
        });

        // Strict approval + KYC gate for non-booking operational provider endpoints
        Route::middleware('provider.access:true')->group(function (): void {
            Route::get('/dashboard', [ProviderDashboardController::class, 'index'])
                ->middleware('provider.permission:providers.dashboard.view')
                ->name('dashboard');

            Route::middleware('provider.permission:providers.workspaceManagement.view')->prefix('workspaces')->name('workspaces.')->group(function (): void {
                Route::get('/', [ProviderWorkspaceController::class, 'index'])->name('index');
                Route::post('/', [ProviderWorkspaceController::class, 'store'])->name('store');
                Route::get('/{workspace}', [ProviderWorkspaceController::class, 'show'])->name('show');
                Route::patch('/{workspace}', [ProviderWorkspaceController::class, 'update'])->name('update');
                Route::delete('/{workspace}', [ProviderWorkspaceController::class, 'destroy'])->name('destroy');
                Route::post('/{workspace}/submit-review', [ProviderWorkspaceController::class, 'submitForReview'])->name('submit-review');
                Route::post('/{workspace}/images', [ProviderWorkspaceController::class, 'uploadImage'])->name('images.store');
                Route::delete('/{workspace}/images/{image}', [ProviderWorkspaceController::class, 'deleteImage'])->name('images.destroy');
            });

            Route::middleware('provider.permission:providers.floors.view')->group(function (): void {
                Route::get('/workspaces/{workspace}/floors', [ProviderWorkspaceController::class, 'floors'])->name('workspaces.floors.index');
                Route::put('/workspaces/{workspace}/floors', [ProviderWorkspaceController::class, 'upsertFloors'])->name('workspaces.floors.upsert');
            });

            Route::middleware('provider.permission:providers.roleManagement.view')->group(function (): void {
                Route::get('/permissions', [ProviderRoleController::class, 'permissions'])->name('permissions.index');
                Route::get('/roles', [ProviderRoleController::class, 'index'])->name('roles.index');
                Route::post('/roles', [ProviderRoleController::class, 'store'])->name('roles.store');
                Route::patch('/roles/{role}', [ProviderRoleController::class, 'update'])->name('roles.update');
                Route::delete('/roles/{role}', [ProviderRoleController::class, 'destroy'])->name('roles.destroy');
            });

            Route::middleware('provider.permission:providers.users.view')->prefix('staff')->name('staff.')->group(function (): void {
                Route::get('/', [ProviderStaffController::class, 'index'])->name('index');
                Route::post('/', [ProviderStaffController::class, 'store'])->name('store');
                Route::patch('/{staff}', [ProviderStaffController::class, 'update'])->name('update');
                Route::delete('/{staff}', [ProviderStaffController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('templates')->name('templates.')->group(function (): void {
                Route::middleware('provider.permission:providers.templateMapping.view')->group(function (): void {
                    Route::get('/mappings', [ProviderTemplateController::class, 'mappings'])->name('mappings.index');
                    Route::post('/mappings', [ProviderTemplateController::class, 'mapFloor'])->name('mappings.store');
                });

                Route::middleware('provider.permission:providers.layoutDesigner.view')->group(function (): void {
                    Route::get('/', [ProviderTemplateController::class, 'index'])->name('index');
                    Route::post('/', [ProviderTemplateController::class, 'store'])->name('store');
                    Route::get('/{template}', [ProviderTemplateController::class, 'show'])->name('show');
                    Route::patch('/{template}', [ProviderTemplateController::class, 'update'])->name('update');
                    Route::delete('/{template}', [ProviderTemplateController::class, 'destroy'])->name('destroy');
                    Route::post('/{template}/clone', [ProviderTemplateController::class, 'clone'])->name('clone');
                });
            });
        });
    });

    // External Payment Gateway Webhooks (HMAC verified inside service)
    Route::post('/webhooks/payments/{gateway}', [PaymentController::class, 'webhook'])->name('webhooks.payments');

    // Super Admin Auth & Portal
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::prefix('auth')->name('auth.')->middleware('throttle:auth')->group(function (): void {
            Route::post('/login', [AdminAuthController::class, 'login'])->name('login');
        });

        Route::middleware(['auth:sanctum', 'super.admin'])->group(function (): void {
            Route::middleware('permission:admin.read')->group(function (): void {
                Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
                Route::get('/roles', [AdminOperationsController::class, 'roles'])->name('roles.index');
            });

            Route::middleware('permission:admin.providers.manage')->group(function (): void {
                Route::get('/providers', [AdminProviderController::class, 'index'])->name('providers.index');
                Route::get('/providers/{provider}', [AdminProviderController::class, 'show'])->name('providers.show');
                Route::patch('/providers/{provider}/status', [AdminProviderController::class, 'updateStatus'])->name('providers.status');
                Route::post('/providers/{provider}/verify-kyc', [AdminProviderController::class, 'verifyKyc'])->name('providers.verify-kyc');
                Route::get('/provider-documents/{document}/download', [AdminProviderController::class, 'downloadDocument'])->name('provider-documents.download');
            });

            Route::middleware('permission:admin.workspaces.approve')->group(function (): void {
                Route::get('/workspaces', [AdminWorkspaceApprovalController::class, 'index'])->name('workspaces.index');
                Route::get('/workspaces/{workspace}', [AdminWorkspaceApprovalController::class, 'show'])->name('workspaces.show');
                Route::post('/workspaces/{workspace}/decision', [AdminWorkspaceApprovalController::class, 'decide'])->name('workspaces.decision');
            });

            Route::middleware('permission:admin.master_data.manage')->group(function (): void {
                Route::get('/cities', [AdminMasterDataController::class, 'cities'])->name('cities.index');
                Route::post('/cities', [AdminMasterDataController::class, 'storeCity'])->name('cities.store');
                Route::patch('/cities/{city}', [AdminMasterDataController::class, 'updateCity'])->name('cities.update');

                Route::get('/localities', [AdminMasterDataController::class, 'localities'])->name('localities.index');
                Route::post('/localities', [AdminMasterDataController::class, 'storeLocality'])->name('localities.store');

                Route::get('/categories', [AdminMasterDataController::class, 'categories'])->name('categories.index');
                Route::post('/categories', [AdminMasterDataController::class, 'storeCategory'])->name('categories.store');
                Route::post('/subcategories', [AdminMasterDataController::class, 'storeSubcategory'])->name('subcategories.store');

                Route::get('/amenities', [AdminMasterDataController::class, 'amenities'])->name('amenities.index');
                Route::post('/amenities', [AdminMasterDataController::class, 'storeAmenity'])->name('amenities.store');
                Route::patch('/amenities/{amenity}', [AdminMasterDataController::class, 'updateAmenity'])->name('amenities.update');
            });

            Route::middleware('permission:admin.bookings.manage')->group(function (): void {
                Route::get('/customers', [AdminOperationsController::class, 'customers'])->name('customers.index');
                Route::patch('/customers/{user}/status', [AdminOperationsController::class, 'updateCustomerStatus'])->name('customers.status');

                Route::get('/bookings', [AdminOperationsController::class, 'bookings'])->name('bookings.index');
                Route::post('/bookings/{booking}/refund', [AdminOperationsController::class, 'recordRefund'])->name('bookings.refund');
                Route::get('/reserve-requests', [AdminOperationsController::class, 'reserveRequests'])->name('reserve-requests.index');
            });

            Route::middleware('permission:admin.coupons.manage')->group(function (): void {
                Route::get('/coupons', [AdminOperationsController::class, 'coupons'])->name('coupons.index');
                Route::post('/coupons', [AdminOperationsController::class, 'storeCoupon'])->name('coupons.store');
                Route::patch('/coupons/{coupon}', [AdminOperationsController::class, 'updateCoupon'])->name('coupons.update');
                Route::delete('/coupons/{coupon}', [AdminOperationsController::class, 'deleteCoupon'])->name('coupons.destroy');
            });

            Route::middleware('permission:admin.payments.view')->group(function (): void {
                Route::get('/payments', [AdminOperationsController::class, 'payments'])->name('payments.index');
                Route::get('/webhook-events', [AdminOperationsController::class, 'webhookEvents'])->name('webhook-events.index');
                Route::get('/refunds', [AdminOperationsController::class, 'refunds'])->name('refunds.index');
            });

            Route::middleware('permission:admin.support.manage')->group(function (): void {
                Route::get('/consultations', [AdminOperationsController::class, 'consultations'])->name('consultations.index');
                Route::patch('/consultations/{consultation}', [AdminOperationsController::class, 'updateConsultation'])->name('consultations.update');

                Route::get('/partner-inquiries', [AdminOperationsController::class, 'partnerInquiries'])->name('partner-inquiries.index');
                Route::patch('/partner-inquiries/{inquiry}', [AdminOperationsController::class, 'updatePartnerInquiry'])->name('partner-inquiries.update');

                Route::get('/support-tickets', [AdminOperationsController::class, 'supportTickets'])->name('support-tickets.index');
                Route::patch('/support-tickets/{ticket}', [AdminOperationsController::class, 'updateSupportTicket'])->name('support-tickets.update');
            });

            Route::middleware('permission:admin.settings.manage')->group(function (): void {
                Route::get('/settings', [AdminOperationsController::class, 'settings'])->name('settings.index');
                Route::put('/settings', [AdminOperationsController::class, 'upsertSetting'])->name('settings.upsert');
            });

            Route::middleware('permission:admin.audit_logs.view')->group(function (): void {
                Route::get('/audit-logs', [AdminOperationsController::class, 'auditLogs'])->name('audit-logs.index');
            });

            Route::middleware('permission:admin.reviews.manage')->group(function (): void {
                Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
                Route::patch('/reviews/{review}/moderate', [AdminReviewController::class, 'moderate'])->name('reviews.moderate');
            });
        });
    });
});
