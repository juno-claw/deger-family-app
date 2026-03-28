<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventReminderRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:absolute,relative',
            'remind_at' => 'required_if:type,absolute|nullable|date|after:now',
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
            'remind_at.after' => 'Der Erinnerungszeitpunkt muss in der Zukunft liegen.',
            'minutes_before.required_if' => 'Minuten vorher ist erforderlich fuer relative Erinnerungen.',
            'minutes_before.min' => 'Minuten vorher muss mindestens 1 sein.',
        ];
    }
}
