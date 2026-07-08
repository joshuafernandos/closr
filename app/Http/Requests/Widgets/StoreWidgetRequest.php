<?php

namespace App\Http\Requests\Widgets;

use App\Enums\WidgetTemplate;
use App\Models\Widget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreWidgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->currentBusiness !== null
            && $this->user()->can('create', Widget::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $business = $this->user()->currentBusiness;

        return [
            'name' => ['required', 'string', 'max:255'],
            'template' => ['required', new Enum(WidgetTemplate::class)],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'catalogue_origin_id' => [
                'nullable',
                Rule::exists('catalogue_origins', 'id')->where('business_id', $business->id),
            ],
        ];
    }
}
