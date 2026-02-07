<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:2000',
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|nullable|date|after_or_equal:start_at',
            'all_day' => 'sometimes|boolean',
            'recurrence' => 'sometimes|in:none,daily,weekly,monthly,yearly',
            'color' => 'sometimes|nullable|string|max:7',
        ];
    }
}
