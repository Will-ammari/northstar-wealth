<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreValuationRequest extends FormRequest
{
    public function authorize(): bool {return true;}
    public function rules(): array {return ['unit_price'=>['required','numeric','gte:0','decimal:0,8','max:9999999999999999']];}
}
