<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResumeQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'connection' => $this->route('connection'),
            'queue' => $this->route('queue'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'connection' => ['required', 'string', Rule::in(array_keys(config('queue.connections', [])))],
            'queue' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{connection: string, queue: string}
     */
    public function validatedData(): array
    {
        /** @var array{connection: string, queue: string} $validated */
        $validated = $this->validated();

        return $validated;
    }
}
