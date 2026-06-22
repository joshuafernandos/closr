<?php

namespace App\Http\Requests\Businesses;

use App\Models\Business;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class DeleteBusinessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('delete', $this->route('business'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('name') !== $this->business()->name) {
                    $validator->errors()->add('name', __('The business name does not match.'));
                }
            },
        ];
    }

    /**
     * Get the business associated with the request.
     */
    private function business(): Business
    {
        $business = $this->route('business');

        abort_if(! $business instanceof Business, 404);

        return $business;
    }
}
