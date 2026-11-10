<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $categoryId = $this->route('category') ? $this->route('category')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', ...($this->expectsJson() ? [Rule::unique('categories', 'name')->ignore($categoryId)] : [])],
            'slug' => [
                'required',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'status' => 'boolean',
        ];
    }

    protected function prepareForValidation()
    {
        // Auto-generate slug if not provided
        if (! $this->filled('slug')) {
            $this->merge([
                'slug' => Category::generateUniqueSlug($this->name),
            ]);
        }
    }
}
