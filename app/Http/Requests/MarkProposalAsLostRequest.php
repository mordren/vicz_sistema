<?php

namespace App\Http\Requests;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;

class MarkProposalAsLostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');

        return $proposal instanceof Proposal
            && ($this->user()?->can('markAsLost', $proposal) ?? false);
    }

    public function rules(): array
    {
        return [
            'loss_reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
