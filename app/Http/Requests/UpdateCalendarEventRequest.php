<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
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
            'reminders' => 'nullable|array',
            'reminders.*.type' => 'required_with:reminders|in:absolute,relative',
            'reminders.*.minutes_before' => 'required_if:reminders.*.type,relative|nullable|integer|min:1',
            'reminders.*.remind_at' => 'required_if:reminders.*.type,absolute|nullable|date',
            'reminders.*.user_ids' => 'required_with:reminders|array|min:1',
            'reminders.*.user_ids.*' => 'exists:users,id',
        ];
    }
}
