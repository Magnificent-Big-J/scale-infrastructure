<?php

namespace App\Http\Requests\Support;

use App\Enums\LookupType;
use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'deployment_id' => ['nullable', 'uuid', 'exists:deployments,id'],
            'support_agreement_id' => ['nullable', 'uuid', 'exists:support_agreements,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'reference' => ['required', 'string', 'max:64', Rule::unique('support_tickets', 'reference')->ignore($this->route('supportTicket'))],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', LookupType::TicketCategory->existsRule()],
            'severity' => ['required', Rule::in(SupportSeverity::values())],
            'status' => ['required', Rule::in(SupportTicketStatus::values())],
            'hours_logged' => ['nullable', 'numeric', 'min:0'],
            'opened_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'summary' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
