<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Authorization is performed with PortfolioPolicy in controller.
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('type') === 'cash' && !preg_match('/^0*1(?:\.0{1,8})?$/', (string)$this->input('unit_price'))) {
                $validator->errors()->add('unit_price', 'USD cash must have a unit value of 1.');
            }
        });
    }
    public function rules(): array
    {
        return [
            'type'=>['required',Rule::in(['stock','crypto','real_estate','cash'])],
            'name'=>['required','string','min:2','max:120'],
            'symbol'=>['nullable','string','max:32'],
            'quantity'=>['required','numeric','gt:0','decimal:0,8','max:9999999999999999'],
            'unit_price'=>['required','numeric','gte:0','decimal:0,8','max:9999999999999999'],
        ];
    }
}
