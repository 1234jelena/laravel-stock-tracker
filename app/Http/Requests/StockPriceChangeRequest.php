<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockPriceChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'stock_ids' => 'required|array|min:1',
            'stock_ids.*' => 'required|integer|exists:stocks,id',
            'start_date' => 'required|date_format:Y-m-d H:i:00',
            'end_date' => 'required|date_format:Y-m-d H:i:00|after:start_date'
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after' => 'End date must be after start date'
        ];
    }
}
