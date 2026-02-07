<?php

namespace Laravel\Horizon\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PauseQueueRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'connection' => 'required|string',
            'queue' => 'required|string',
        ];
    }
}
