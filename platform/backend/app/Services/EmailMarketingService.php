<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailMarketingService
{
    /**
     * Subscribe an email to the store's marketing list.
     */
    public function subscribe(int $storeId, string $email, ?string $firstName = null, string $source = 'footer_form'): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::withoutGlobalScope('store')
            ->where('store_id', $storeId)
            ->where('email', strtolower($email))
            ->first();

        if ($subscriber) {
            if ($subscriber->status === 'unsubscribed') {
                $subscriber->update([
                    'status' => 'subscribed',
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                    'first_name' => $firstName ?? $subscriber->first_name,
                ]);
            }
            return $subscriber->fresh();
        }

        $subscriber = NewsletterSubscriber::create([
            'store_id' => $storeId,
            'email' => strtolower($email),
            'first_name' => $firstName,
            'status' => 'subscribed',
            'source' => $source,
            'subscribed_at' => now(),
        ]);

        // Sync to Mailchimp if configured
        $this->syncToMailchimp($storeId, $subscriber);

        return $subscriber;
    }

    /**
     * Unsubscribe an email.
     */
    public function unsubscribe(int $storeId, string $email): void
    {
        NewsletterSubscriber::withoutGlobalScope('store')
            ->where('store_id', $storeId)
            ->where('email', strtolower($email))
            ->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);
    }

    /**
     * Sync subscriber to Mailchimp if configured.
     */
    public function syncToMailchimp(int $storeId, NewsletterSubscriber $subscriber): void
    {
        $settings = $this->getMailchimpConfig($storeId);

        if (!$settings['enabled'] || !$settings['api_key'] || !$settings['list_id']) {
            return;
        }

        try {
            $dataCenter = explode('-', $settings['api_key'])[1] ?? 'us1';
            $listId = $settings['list_id'];
            $emailHash = md5(strtolower($subscriber->email));

            Http::withBasicAuth('anystring', $settings['api_key'])
                ->put("https://{$dataCenter}.api.mailchimp.com/3.0/lists/{$listId}/members/{$emailHash}", [
                    'email_address' => $subscriber->email,
                    'status_if_new' => 'subscribed',
                    'status' => 'subscribed',
                    'merge_fields' => [
                        'FNAME' => $subscriber->first_name ?? '',
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning("Mailchimp sync failed for store {$storeId}: " . $e->getMessage());
        }
    }

    /**
     * Get Mailchimp configuration from store settings.
     */
    public function getMailchimpConfig(int $storeId): array
    {
        $settings = DB::table('store_settings')
            ->where('store_id', $storeId)
            ->whereIn('key', ['mailchimp_api_key', 'mailchimp_list_id', 'email_marketing_enabled'])
            ->pluck('value', 'key')
            ->toArray();

        return [
            'enabled' => filter_var($settings['email_marketing_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'api_key' => $settings['mailchimp_api_key'] ?? null,
            'list_id' => $settings['mailchimp_list_id'] ?? null,
        ];
    }

    /**
     * Get subscriber count for the store.
     */
    public function getSubscriberCount(int $storeId): int
    {
        return NewsletterSubscriber::withoutGlobalScope('store')
            ->where('store_id', $storeId)
            ->where('status', 'subscribed')
            ->count();
    }
}
