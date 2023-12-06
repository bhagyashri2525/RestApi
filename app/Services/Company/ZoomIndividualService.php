<?php

namespace App\Services\Company;

use App\Models\Event;
use App\Models\IndividualZoomAccount;
use App\Models\User;
use App\Services\Utils\CommonService;
use BaoPham\DynamoDb\Facades\DynamoDb;

class ZoomIndividualService
{
    public function checkForExistance($data) {
        $model = IndividualZoomAccount::where([
                'email' => $data['email'],
                'company_id' => $data['company_id'],
                'is_deleted' => false
            ])
            ->get()
            ->count();
        if($model) {
            return false;
        }
        return true;
    }

    public function addIndividualZoomAccount($data) {
        $model = IndividualZoomAccount::where([
            'email' => $data['email'],
            'company_id' => $data['company_id'],
            'is_deleted' => true
        ])->get()->first();
        
        if($model) {
            $model = IndividualZoomAccount::find($model->id);
            $model->update([
                'is_deleted' => false,
                'is_active' => false,
                'is_verified' => false
            ]);
            return $model->refresh();
        }
        
        $data['id'] = (new CommonService())->generatePrimaryKey();
        $model = new IndividualZoomAccount($data);
        $model->save();
        return $model->refresh();
    }

    public function getIndividualZoomAccounts($companyId) {
        if($companyId) {
            return IndividualZoomAccount::where('company_id', $companyId)
                ->where('is_deleted', false)
                ->get();
        }
        return [];
    }

    public function getZoomAccountsOnCreateMethod($createMethod, $companyId, $userId) {
        $data = [];
        if($createMethod && ($companyId || $userId)) {
            if($createMethod == Event::STREAMON_CREATE_METHOD || $createMethod == Event::SELF_CREATE_METHOD) {
                $data = User::find($userId, ['company_mapping', 'zoom_integrations']);
            } elseif($createMethod == Event::INDIVIDUAL_ZOOM_CREATE_METHOD) {
                $data = IndividualZoomAccount::where([
                        'company_id' => $companyId,
                        'is_verified' => true
                    ])
                    ->get();
            }
        }
        return $data;
    }

    public function deleteIndividualZoomAccount($id) {
        if($id) {
            $model = IndividualZoomAccount::find($id);
            if($model) {
                $model->update([
                    'is_deleted' => true,
                    'is_active' => false,
                    'is_verified' => false
                ]);
                return $model->refresh();
            }
        }
        return false;
    }

    public function verifyIndividualZoomAccount($id, $status) {
        if($id) {
            $model = IndividualZoomAccount::find($id);
            if($model) {
                $isActive = false;
                $isVerified = false;
                if((int)$status == 1) {
                    $isActive = true;
                    $isVerified = true;
                }
                $model->update([
                    'is_verified' => $isVerified
                ]);
                return $model->refresh();
            }
        }
        return false;
    }
}
?>