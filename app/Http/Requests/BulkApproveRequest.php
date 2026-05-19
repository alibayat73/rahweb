<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['required', 'integer', 'exists:tickets,id'],
            'note' => ['required', 'string', 'max:1000'],
        ];
    }
}
