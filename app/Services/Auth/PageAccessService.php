<?php 

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\UserParent;
use App\Models\UserRole;

class PageAccessService{

    public function getNavbarForRole($roles){

        $result = [];

        if(empty($roles)){
            $result[1] = ['id' => 1, 'url' => 'panel', 'label' => 'Dashboard', 'icon' => 'tasks'];
        }else{
            if(in_array(Role::STREAMON_ADMIN, $roles)){

                $result[1] = ['id' => 1, 'url' => 'admins', 'label' => 'Admins', 'icon' => 'users'];
                $result[2] = ['id' => 2, 'url' => 'events', 'label' => 'Events', 'icon' => 'tasks'];
                $result[3] = ['id' => 3, 'url' => 'roles', 'label' => 'Roles', 'icon' => 'user'];
                $result[4] = ['id' => 4, 'url' => 'companies', 'label' => 'Companies', 'icon' => 'building'];
    
            }else if(in_array(Role::STREAMON_OPERATION, $roles)){
    
                $result[1] = ['id' => 1, 'url' => 'admins', 'label' => 'Admins', 'icon' => 'users'];
                $result[2] = ['id' => 2, 'url' => 'events', 'label' => 'Events', 'icon' => 'tasks'];
                $result[3] = ['id' => 3, 'url' => 'roles', 'label' => 'Roles', 'icon' => 'user'];
                $result[4] = ['id' => 4, 'url' => 'companies', 'label' => 'Companies', 'icon' => 'building'];
            
            }else{
    
                $result[1] = ['id' => 1, 'url' => 'panel', 'label' => 'Dashboard', 'icon' => 'tasks'];
            }
        }

        

        return $result;
    }

    public function details($page,$roles){
        $result = [];

        switch($page){
            case 'panel':
                $result = self::getPanelPageAccessDetails($roles);
                break;
            case 'company-list':
                $result = self::getCompanyListPageAccessDetails($roles);
                break;
            case 'role-list':
                $result = self::rolePageAccessDetails($roles);
                break;
            case 'admin-list':
                $result = self::getAdminPageAccessDetails($roles);
                break;
            case 'company-dashboard':
                $result = self::getCompanyDashboardPageAccessDetails($roles);
                break;
            case 'event-start-form':
                $result = self::getStartEventPageAccessDetails($roles);
                break;
            case 'event-page-setup':
                $result = self::getEventPageSetupPageAccessDetails($roles);
                break;
            case 'event-dashboard':
                $result = self::eventDashboardPageAccessDetails($roles);
                break;
            case 'profile':
                $result = self::authProfilePageAccessDetails($roles);
                break;
            case 'event-list':
                $result = self::getEventPageAccessDetails($roles);
                break;   
        }

        return $result;
    }

    public function getPanelPageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles)){
            $pageAccess['show'] = ['panel'];
            $pageAccess['hide'] = ['panel-x'];
        }
        return $pageAccess;
    }

    public function getCompanyListPageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles)){
            $pageAccess['show'] = ['company-list-section','create-company-button'];
            $pageAccess['hide'] = ['company-x'];
        }
        return $pageAccess;
    }

    public function getAdminPageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles)){
            $pageAccess['show'] = ['admin-list-section','add-admin-btn'];
            $pageAccess['hide'] = ['admin-list-x'];
        }
        return $pageAccess;
    }

    public function getCompanyDashboardPageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles)){
            $pageAccess['show'] = ['company-details-page-section','nav-events','nav-setting','nav-admins','nav-department', 'all-counters'];
            $pageAccess['hide'] = ['company-details-x'];
        }else if(in_array(Role::COMPANY_MASTER_ADMIN,$roles)){
            $pageAccess['show'] =  ['company-details-page-section','nav-events','nav-setting','nav-admins','nav-department', 'all-counters'];
            $pageAccess['hide'] = ['company-details-x'];
        }else if(in_array(Role::COMPANY_EMPLOYEE,$roles)){
            $pageAccess['show'] =  ['company-details-page-section'];
            $pageAccess['hide'] = ['company-details-x', 'nav-events', 'add-event-btn', 'event-explore-btn', 'all-counters'];
        }else if(in_array(Role::DEPARTMENT_ADMIN,$roles)){
            $pageAccess['show'] =  ['company-details-page-section'];
            $pageAccess['hide'] = ['company-details-x', 'all-counters'];
        }
        return $pageAccess;
    }
    public function getStartEventPageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles) || in_array(Role::COMPANY_EMPLOYEE,$roles) ||
            in_array(Role::DEPARTMENT_ADMIN,$roles) || in_array(Role::COMPANY_MASTER_ADMIN,$roles)){
            $pageAccess['show'] = ['start-event-page-section'];
            $pageAccess['hide'] = ['start-event-page-section-x'];
        }
        return $pageAccess;
    }
    public function getEventPageSetupPageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles) || in_array(Role::COMPANY_EMPLOYEE,$roles)){
            $pageAccess['show'] = ['event-page-setup-form-section'];
            $pageAccess['hide'] = ['event-page-setup-form-section-x'];
        }
        return $pageAccess;
    }

    public function rolePageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles)){
            $pageAccess['show'] = ['panel-role-content','company-role-content','event-role-content'];
            $pageAccess['hide'] = ['role-list-x'];
        }
        return $pageAccess;
    }

    public function eventDashboardPageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles)){
            $pageAccess['show'] = ['event-details-page-section'];
            $pageAccess['hide'] = ['panel-x'];
        }
        return $pageAccess;
    }
    public function authProfilePageAccessDetails($roles){
        $pageAccess = [];
        if(in_array(Role::STREAMON_ADMIN,$roles)){
            $pageAccess['show'] = ['profile-details-page-section'];
            $pageAccess['hide'] = ['panel-x'];
        }
        return $pageAccess;
    }
    public function getEventPageAccessDetails($roles) {
        $pageAccess = [];
        if(in_array(Role::DEPARTMENT_ADMIN,$roles)){
            $pageAccess['show'] = ['company-details-page-section', 'add-event-btn'];
            $pageAccess['hide'] = ['panel-x', 'details-section'];
        } elseif(in_array(Role::COMPANY_EMPLOYEE,$roles)){
            $pageAccess['show'] = ['company-details-page-section'];
            $pageAccess['hide'] = ['panel-x', 'details-section', 'add-event-btn'];
        }
        return $pageAccess;
    }
}