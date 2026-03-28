<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'all_day' => 'boolean',
            'recurrence' => 'in:none,daily,weekly,monthly,yearly',
            'color' => 'nullable|string|max:7',
            'reminders' => 'nullable|array',
            'reminders.*.type' => 'required_with:reminders|in:absolute,relative',
            'reminders.*.minutes_before' => 'required_if:reminders.*.type,relative|nullable|integer|min:1',
            'reminders.*.remind_at' => 'required_if:reminders.*.type,absolute|nullable|date',
            'reminders.*.user_ids' => 'required_with:reminders|array|min:1',
            'reminders.*.user_ids.*' => 'exists:users,id',
        ];
    }
}
