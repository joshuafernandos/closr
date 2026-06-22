<?php

namespace App\Http\Requests\Businesses;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Rules\UniqueBusinessInvitation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBusinessInvitationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $business = $this->route('business');

        abort_if(! $business instanceof Business, 404);

        return [
            'email' => ['required', 'string', 'email', 'max:255', new UniqueBusinessInvitation($business)],
            'role' => ['required', 'string', Rule::enum(BusinessRole::class)],
        ];
    }
}
