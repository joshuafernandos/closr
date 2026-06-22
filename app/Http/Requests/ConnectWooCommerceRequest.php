<?php

namespace App\Http\Requests;

use App\Catalogue\Sources\WooCommerceSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Throwable;

class ConnectWooCommerceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $business = $this->user()?->currentBusiness;

        return $business !== null && $this->user()->can('update', $business);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url', 'starts_with:https://', 'max:255'],
            'key' => ['required', 'string', 'max:255'],
            'secret' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Verify the credentials actually connect before they are stored.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! $this->credentialsConnect()) {
                    $validator->errors()->add(
                        'url',
                        __('Could not reach your WooCommerce store with these credentials. Double-check the store URL and that the API key has Read access.'),
                    );
                }
            },
        ];
    }

    /**
     * Attempt a live request against the store to confirm the credentials work.
     */
    private function credentialsConnect(): bool
    {
        try {
            (new WooCommerceSource(
                url: $this->string('url')->value(),
                key: $this->string('key')->value(),
                secret: $this->string('secret')->value(),
                timeout: 10,
            ))->search('', limit: 1);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get the WooCommerce credential config to persist.
     *
     * @return array{url: string, key: string, secret: string}
     */
    public function credentials(): array
    {
        return [
            'url' => $this->string('url')->value(),
            'key' => $this->string('key')->value(),
            'secret' => $this->string('secret')->value(),
        ];
    }
}
