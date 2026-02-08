<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
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
            'description' => 'nullable|string|max:1000',
            'category' => 'sometimes|string|in:cooking,baking,dessert,snack,drink',
            'servings' => 'nullable|integer|min:1|max:100',
            'prep_time' => 'nullable|integer|min:0|max:1440',
            'cook_time' => 'nullable|integer|min:0|max:1440',
            'ingredients' => 'sometimes|string',
            'instructions' => 'sometimes|string',
            'is_favorite' => 'sometimes|boolean',
        ];
    }
}
