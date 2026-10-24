<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'type'=>['required',Rule::in(['buy','sell'])],
            'quantity'=>['required','numeric','gt:0','decimal:0,8','max:9999999999999999'],
            'unit_price'=>['required','numeric','gte:0','decimal:0,8','max:9999999999999999'],
            'idempotency_key'=>['required','uuid'],
        ];
    }
}
