<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\EmailMarketingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Newsletter
 *
 * Newsletter subscription endpoints.
 */
class NewsletterController extends Controller
{
    public function __construct(private readonly EmailMarketingService $service) {}

    /**
     * Subscribe to newsletter
     *
     * @bodyParam email string required Customer's email address. Example: hello@example.com
     * @bodyParam first_name string Optional first name. Example: Jane
     *
     * @response 200 {"message": "Successfully subscribed!", "data": {"email": "hello@example.com", "status": "subscribed"}}
     * @response 422 {"message": "Validation failed", "errors": {"email": ["The email field is required."]}}
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'first_name' => 'nullable|string|max:100',
        ]);

        $subscriber = $this->service->subscribe(
            storeId: tenant()->id,
            email: $request->input('email'),
            firstName: $request->input('first_name'),
            source: 'footer_form',
        );

        return response()->json([
            'message' => 'Successfully subscribed!',
            'data' => [
                'email' => $subscriber->email,
                'status' => $subscriber->status,
            ],
        ]);
    }

    /**
     * Unsubscribe from newsletter
     *
     * @bodyParam email string required Email to unsubscribe. Example: hello@example.com
     *
     * @response 200 {"message": "Successfully unsubscribed."}
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $this->service->unsubscribe(tenant()->id, $request->input('email'));

        return response()->json(['message' => 'Successfully unsubscribed.']);
    }
}
