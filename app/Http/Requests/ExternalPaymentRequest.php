<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExternalPaymentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'passphrase' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.00001'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }
}
