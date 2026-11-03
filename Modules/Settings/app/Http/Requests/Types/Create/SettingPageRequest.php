<?php

namespace Modules\Settings\Http\Requests\Types\Create;

use App\Rules\ArabicLetters;
use App\Rules\EnglishLetters;
use Illuminate\Foundation\Http\FormRequest;

class SettingPageRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name_ar' => ['required', 'string', 'max:255', new ArabicLetters],
            'name_en' => ['required', 'string', 'max:255', new EnglishLetters],
            'company_name' => ['required', 'string', 'max:255'],
            'company_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'company_cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'company_email' => ['required', 'string', 'email', 'max:255'],
            'company_card_number' => 'required|numeric|digits:16',

            'company_license' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:2048',
            'company_register_license' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:2048',
            'company_professional_license' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:2048',
            'company_audited_financial_license' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:2048',
            'company_id_owner_license' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:2048',
            'company_vat_license' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:2048',
            'company_bank_account_license' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:2048',


        ];
    }
}
