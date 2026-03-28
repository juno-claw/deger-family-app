<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventReminderRequest extends FormRequest
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
            'user_id' => 'sometimes|exists:users,id',
            'type' => 'sometimes|in:absolute,relative',
            'remind_at' => 'required_if:type,absolute|nullable|date',
            'minutes_before' => 'required_if:type,relative|nullable|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'remind_at.required_if' => 'Ein Datum/Uhrzeit ist erforderlich fuer absolute Erinnerungen.',
            'minutes_before.required_if' => 'Minuten vorher ist erforderlich fuer relative Erinnerungen.',
            'minutes_before.min' => 'Minuten vorher muss mindestens 1 sein.',
        ];
    }
}
