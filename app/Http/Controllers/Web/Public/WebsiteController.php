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
            'cities' => City::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => WorkspaceCategory::query()->where('is_active', true)->with('subcategories')->orderBy('sort_order')->get(),
            'amenities' => Amenity::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'workspaces' => Workspace::query()->published()->with(['city', 'category', 'subcategory', 'listingConfigs'])->latest('id')->limit(18)->get(),
        ]);
    }

    public function expansion(): View|Response
    {
        return $this->renderPortalView('website.expansion', [
            'cities' => City::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'workspaces' => Workspace::query()
                ->published()
                ->whereHas('subcategory', fn ($q) => $q->whereIn('code', ['OFFICE_COMMERCIAL', 'NEW_BUILDING']))
                ->with(['city', 'subcategory', 'listingConfigs'])
                ->latest('id')
                ->get(),
        ]);
    }

    public function workspaceProviders(): View|Response
    {
        return $this->renderPortalView('website.partner.workspace-providers', [
            'cities' => City::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'inquiryType' => 'WORKSPACE_PROVIDER',
        ]);
    }

    public function buildingOwners(): View|Response
    {
        return $this->renderPortalView('website.partner.building-owners', [
            'cities' => City::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'inquiryType' => 'BUILDING_OWNER',
        ]);
    }

    public function developers(): View|Response
    {
        return $this->renderPortalView('website.partner.developers', [
            'cities' => City::query()->where('is_active', true)->orderBy('sort_order')->get(),
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
