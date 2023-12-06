<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Services\Company\CompanyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Utils\ApiService as API;
use Illuminate\Validation\Rule;

class CreateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $eventCountValidationStr = 'required_if:type,' . Event::TYPE_MULTIPLE;
        $createMethodReq = 'required_if:type,' . Event::TYPE_SINGLE;
        $companyId = $this->route('companyId');
        $this->request->add(['companyId' => $companyId]);

        // $company = (new CompanyService())->details($companyId);
        // if (!empty($company)) {
        //     $this->request->add(['companyId' => $companyId]);
        // }

        return [
               
                'name' => ['required', 'max:256'],
                'description' => ['nullable', 'max:3000'],
                'department_id' => ['required'],
                'category' => [
                    Rule::requiredIf(function (){
                        return $this->request->get('type') !== Event::TYPE_MULTIPLE;
                    }),
                ],
                'timezone' => [
                    Rule::requiredIf(function ()  {
                        return $this->request->get('type') == Event::TYPE_SINGLE;
                    }),
                ],
                'start_datetime' => [
                    Rule::requiredIf(function () {
                        return $this->request->get('type') == Event::TYPE_SINGLE;
                    }),
                ],
                'duration' => [
                    Rule::requiredIf(function ()  {
                        return $this->request->get('type') == Event::TYPE_SINGLE;
                    }),
                ],
                'event_count' => [$eventCountValidationStr],
                'publish' => ['required'],
                'create_method' => [$createMethodReq],
                'is_gdpr_compliance' => ['required'],
                'companyId' => ['required'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'error' => 'Validation failed',
            'messages' => $validator->errors(),
            'message' => $validator->errors()->first(),
        ], 422));
        //throw new HttpResponseException(API::FAIL, [], $validator->errors()->first());
    }


    public function transformData(){
        $this->request->add([
            'is_active' => true,
            'publish' => ($this->request->get('publish') == 1 ? true : false),
            'is_gdpr_compliance' => ($this->request->get('is_gdpr_compliance') == 1 ? true : false),
            'is_consent' => ($this->request->get('is_gdpr_compliance') == 1 ? true : false),
            'is_registartion_confrimation' => ($this->request->get('is_gdpr_compliance') == 1 ? true : false)
        ]);
    }

    public function eventTypeWiseDataFilter($eventType){
        $cleanupData = [];
        if ($eventType == Event::TYPE_SINGLE) {
            $this->request->remove('event_count');
        } elseif ($eventType == Event::TYPE_MULTIPLE) {
            $cleanupData = $this->only(['name', 'description', 'department_id', 'type', 'event_count', 'is_active', 'publish', 'create_method', 'is_gdpr_compliance', 'is_consent', 'is_registartion_confrimation', 'desc_event_timestamp', 'asc_event_timestamp', 'desc_creation_timestamp', 'asc_creation_timestamp']);
        } else {
            $this->request->remove('event_count');
            $cleanupData = $this->only(['name', 'description', 'department_id', 'type', 'category', 'is_active', 'publish', 'is_gdpr_compliance', 'is_consent', 'is_registartion_confrimation', 'desc_event_timestamp', 'asc_event_timestamp', 'desc_creation_timestamp', 'asc_creation_timestamp']);
        }

        return $cleanupData;
    }
}