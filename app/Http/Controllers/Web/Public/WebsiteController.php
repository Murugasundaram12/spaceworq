<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WebConsultationRequest;
use App\Http\Requests\Web\WebPartnerInquiryRequest;
use App\Models\Amenity;
use App\Models\City;
use App\Models\PartnerInquiry;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Services\Public\InquiryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function __construct(
        private readonly InquiryService $inquiryService
    ) {}

    public function home(Request $request): View|Response
    {
        if (! view()->exists('website.home') && view()->exists('welcome')) {
            return response()->view('welcome');
        }

        return $this->renderPortalView('website.home', [
            'cities' => Schema::hasTable('cities')
                ? City::query()->where('is_active', true)->orderBy('sort_order')->get()
                : collect(),
            'categories' => Schema::hasTable('workspace_categories')
                ? WorkspaceCategory::query()->where('is_active', true)->with('subcategories')->orderBy('sort_order')->get()
                : collect(),
            'amenities' => Schema::hasTable('amenities')
                ? Amenity::query()->where('is_active', true)->orderBy('sort_order')->get()
                : collect(),
            'workspaces' => Schema::hasTable('workspaces')
                ? Workspace::query()->published()->with(['city', 'locality', 'category', 'subcategory', 'listingConfigs', 'amenities', 'images'])->latest('id')->limit(18)->get()
                : collect(),
        ]);
    }

    public function expansion(): View|Response
    {
        return $this->renderPortalView('website.expansion', [
            'cities' => Schema::hasTable('cities')
                ? City::query()->where('is_active', true)->orderBy('sort_order')->get()
                : collect(),
            'workspaces' => Schema::hasTable('workspaces')
                ? Workspace::query()
                    ->published()
                    ->whereHas('subcategory', fn ($q) => $q->whereIn('code', ['OFFICE_COMMERCIAL', 'NEW_BUILDING']))
                    ->with(['city', 'locality', 'subcategory', 'listingConfigs'])
                    ->latest('id')
                    ->get()
                : collect(),
        ]);
    }

    public function workspaceProviders(): View|Response
    {
        return $this->renderPortalView('website.partner.workspace-providers', [
            'cities' => Schema::hasTable('cities')
                ? City::query()->where('is_active', true)->orderBy('sort_order')->get()
                : collect(),
            'inquiryType' => 'WORKSPACE_PROVIDER',
        ]);
    }

    public function buildingOwners(): View|Response
    {
        return $this->renderPortalView('website.partner.building-owners', [
            'cities' => Schema::hasTable('cities')
                ? City::query()->where('is_active', true)->orderBy('sort_order')->get()
                : collect(),
            'inquiryType' => 'BUILDING_OWNER',
        ]);
    }

    public function developers(): View|Response
    {
        return $this->renderPortalView('website.partner.developers', [
            'cities' => Schema::hasTable('cities')
                ? City::query()->where('is_active', true)->orderBy('sort_order')->get()
                : collect(),
            'inquiryType' => 'DEVELOPER',
        ]);
    }

    public function storeConsultation(WebConsultationRequest $request): JsonResponse|RedirectResponse
    {
        $consultation = $this->inquiryService->storeConsultation(
            $request->validated(),
            $request->user(),
            true,
            $request
        );

        $message = 'Enterprise inquiry received! Our dedicated manager will contact you shortly.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => [
                    'id' => $consultation->id,
                    'status' => $consultation->status,
                ],
            ], 201);
        }

        return back()->with('status', $message);
    }

    public function storePartnerInquiry(WebPartnerInquiryRequest $request): JsonResponse|RedirectResponse
    {
        $inquiry = $this->inquiryService->storePartnerInquiry(
            $request->validated(),
            $request->user(),
            true,
            $request
        );

        $message = 'Partner details submitted! Our team will review and reach out shortly.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => [
                    'id' => $inquiry->id,
                    'inquiry_type' => $inquiry->inquiry_type,
                    'status' => $inquiry->status,
                ],
            ], 201);
        }

        return back()->with('status', $message);
    }
}
