<?php

namespace App\Http\Controllers\Api\Front;

use App\Enums\ListingStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Front\ListingResource;
use App\Http\Resources\Front\PaymentResource;
use App\Repositories\Module\UserRepository;
use App\Rules\ImageBase64Rule;
use App\Rules\Base64ImageControlRule;
use App\Traits\Controller\HasValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use HasValidatesRequests;
    public UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Favorites
     * */
    public function favorites(): JsonResponse
    {
        $listings = $this->userRepository->fetchFavorites();
        return response()->json([
            'data' => ListingResource::collection($listings),
            'total' => $listings->count()
        ]);
    }

    /**
     * Favorite Add Or Remove
     *
     * @throws ValidationException
     */
    public function favoriteAction(Request $request): JsonResponse
    {
        $this->validateRequest($request, [
            'uuid' => ['required', 'exists:listings,uuid'],
        ]);

        return response()->json($this->userRepository->favoriteAction($request->uuid));
    }

    /**
     * Listings
     * */
    public function listings(Request $request): JsonResponse
    {
        $listings = $this->userRepository->fetchListingsByStatus($request->status);
        return response()->json([
            'data' => ListingResource::collection($listings),
            'total' => $listings->total()
        ]);
    }

    /**
     * Listings Total
     * */
    public function listingsTotal(Request $request): JsonResponse
    {
        $activeListings = $this->userRepository->fetchListingsByStatus(ListingStatusEnum::ACTIVE);
        $pendingListings = $this->userRepository->fetchListingsByStatus(ListingStatusEnum::PENDING);
        $expiredListings = $this->userRepository->fetchListingsByStatus(ListingStatusEnum::EXPIRED);
        $rejectedListings = $this->userRepository->fetchListingsByStatus(ListingStatusEnum::REJECTED);
        $suspendedListings = $this->userRepository->fetchListingsByStatus(ListingStatusEnum::SUSPENDED);
        $archiveListings = $this->userRepository->fetchListingsByStatus(ListingStatusEnum::ARCHIVED);
        return response()->json([
            'total_active' => $activeListings->total(),
            'total_pending' => $pendingListings->total(),
            'total_expired' => $expiredListings->total(),
            'total_rejected' => $rejectedListings->total(),
            'total_suspended' => $suspendedListings->total(),
            'total_archive' => $archiveListings->total(),
        ]);
    }

    /**
     * Balance
     * */
    public function balance()
    {
        $data = $this->userRepository->fetchBalance();
        return response()->json($data);
    }

    /**
     * Referral Links
     * */
    public function referrals()
    {
        $data = $this->userRepository->fetchReferral();
        return response()->json($data);
    }

    /**
     * Payment Histories
     * */
    public function paymentHistories()
    {
        $data = $this->userRepository->fetchPaymentHistories();
        return response()->json([
            'data' => PaymentResource::collection($data),
            'total' => $data->total()
        ]);
    }

    /**
     * Company Gallery
     * */
    public function companyGallery()
    {
        $data = $this->userRepository->fetchCompanyGalleries();
        return response()->json($data);
    }

    /**
     * Company Gallery Delete
     * */
    public function companyGalleryDelete(Request $request)
    {
        $this->validateRequest($request, [
            'path' => ['required']
        ]);

        $this->userRepository->fetchCompanyGalleryDelete($request->path);
        return response()->json(['message' => 'Company gallery deleted successfully.']);
    }

    /**
     * Company Gallery Upload
     * */
    public function companyGalleryUpload(Request $request)
    {
        $this->validateRequest($request, [
            'photo' => ['required', new ImageBase64Rule(), new Base64ImageControlRule()]
        ]);

        $this->userRepository->fetchCompanyGalleryUpload($request->photo);
        return response()->json(['message' => 'Company gallery uploaded successfully.']);
    }
}
