<?php

namespace App\Models;

use App\Models\Traits\HasDefaults;
use App\Models\Traits\HasFinancialCalculations;
use App\Models\Traits\HasFormatting;
use App\Models\Traits\HasModuleAccess;
use App\Models\Traits\HasProjectManagement;
use App\Services\PlanService;
use App\Services\DashboardStatsService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Impersonate,
        HasFormatting, HasFinancialCalculations, HasModuleAccess, HasProjectManagement, HasDefaults;

    protected $appends = ['profile'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'accountant_type',
        'storage_limit',
        'avatar',
        'bio',
        'experience_years',
        'certifications',
        'education',
        'languages',
        'specialties',
        'lang',
        'mode',
        'delete_status',
        'plan',
        'email_verified_at',
        'plan_expire_date',
        'requested_plan',
        'is_active',
        'referral_code',
        'used_referral_code',
        'commission_amount',
        'paid_amount',
        'is_enable_login',
        'last_login_at',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'languages' => 'array',
        'specialties' => 'array',
    ];

    public $settings;

    public function getProfileAttribute()
    {
        if (!empty($this->avatar)) {
            // If already a full URL, return as is
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            // Check if file exists in public directory
            if (file_exists(public_path($this->avatar))) {
                return asset($this->avatar);
            }
            // Check if file exists in storage
            elseif (\Storage::exists($this->avatar)) {
                return asset(\Storage::url($this->avatar));
            }
        }
        // Return default avatar
        return asset(\Storage::url('avatar.png'));
    }

    public function authId()
    {
        return $this->id;
    }

    public function creatorId()
    {
        if ($this->type == 'company' || $this->type == 'super admin') {
            return $this->id;
        } else {
            return $this->created_by;
        }
    }

    public function ownerId()
    {
        if ($this->type == 'company' || $this->type == 'super admin') {
            return $this->id;
        } else {
            return $this->created_by;
        }
    }

    public function ownerDetails()
    {
        if ($this->type == 'company' || $this->type == 'super admin') {
            return User::where('id', $this->id)->first();
        } else {
            return User::where('id', $this->created_by)->first();
        }
    }

    public function currentLanguage()
    {
        return $this->lang;
    }

    // Formatting methods - moved to HasFormatting trait
    // Price format, date formats, number formats are now in HasFormatting trait

    // Plan related methods - delegated to PlanService
    public function getPlan()
    {
        return $this->hasOne('App\Models\Plan', 'id', 'plan');
    }

    public function assignPlan($planID, $company_id = 0)
    {
        $planService = new PlanService();
        return $planService->assignPlan($this, $planID, $company_id);
    }

    // Dashboard statistics methods - delegated to DashboardStatsService
    public function countUsers()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countUsers($this);
    }

    public function countCompany()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countCompany($this);
    }

    public function countOrder()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countOrder();
    }

    public function countplan()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countplan();
    }

    public function countPaidCompany()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countPaidCompany($this);
    }

    public function countCustomers()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countCustomers($this);
    }

    public function countVenders()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countVenders($this);
    }

    public function countInvoices()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countInvoices($this);
    }

    public function countBills()
    {
        $statsService = new DashboardStatsService();
        return $statsService->countBills($this);
    }

    public function todayIncome()
    {
        $statsService = new DashboardStatsService();
        return $statsService->todayIncome($this);
    }

    public function todayExpense()
    {
        $statsService = new DashboardStatsService();
        return $statsService->todayExpense($this);
    }

    // Add delegations for the remaining dashboard methods
    public function incomeCurrentMonth()
    {
        $statsService = new DashboardStatsService();
        return $statsService->incomeCurrentMonth($this);
    }

    public function expenseCurrentMonth()
    {
        $statsService = new DashboardStatsService();
        return $statsService->expenseCurrentMonth($this);
    }

    public function incomecat()
    {
        $statsService = new DashboardStatsService();
        return $statsService->incomecat($this);
    }

    public function getInvoiceProductsData($date)
    {
        $statsService = new DashboardStatsService();
        return $statsService->getInvoiceProductsData($this, $date);
    }

    public function getBillProductsData($date)
    {
        $statsService = new DashboardStatsService();
        return $statsService->getBillProductsData($this, $date);
    }

    public function getincExpBarChartData()
    {
        $statsService = new DashboardStatsService();
        return $statsService->getincExpBarChartData($this);
    }

    public function getIncExpLineChartDate()
    {
        $statsService = new DashboardStatsService();
        return $statsService->getIncExpLineChartDate($this);
    }

    public function totalCompanyUser()
    {
        $statsService = new DashboardStatsService();
        return $statsService->totalCompanyUser($this);
    }

    public function totalCompanyCustomer()
    {
        $statsService = new DashboardStatsService();
        return $statsService->totalCompanyCustomer($this);
    }

    public function totalCompanyVender()
    {
        $statsService = new DashboardStatsService();
        return $statsService->totalCompanyVender($this);
    }

    public function planPrice()
    {
        $statsService = new DashboardStatsService();
        return $statsService->planPrice($this);
    }

    public function currentPlan()
    {
        $statsService = new DashboardStatsService();
        return $statsService->currentPlan($this);
    }

    public function invoicesData()
    {
        $statsService = new DashboardStatsService();
        return $statsService->invoicesData($this);
    }

    public function billsData()
    {
        $statsService = new DashboardStatsService();
        return $statsService->billsData($this);
    }

    public function expenseData()
    {
        $statsService = new DashboardStatsService();
        return $statsService->expenseData($this);
    }

    public function weeklyInvoice()
    {
        $statsService = new DashboardStatsService();
        return $statsService->weeklyInvoice($this);
    }

    public function monthlyInvoice()
    {
        $statsService = new DashboardStatsService();
        return $statsService->monthlyInvoice($this);
    }

    public function weeklyBill()
    {
        $statsService = new DashboardStatsService();
        return $statsService->weeklyBill($this);
    }

    public function monthlyBill()
    {
        $statsService = new DashboardStatsService();
        return $statsService->monthlyBill($this);
    }

    /**
     * Set up default email configurations
     */
    public static function defaultEmail()
    {
        // Ensure default email templates exist
        // This method is called during user authentication to ensure email templates are set up
        // Similar to how Utility::addNewAccountData() sets up default account data
    }

    /**
     * Register default data for a user
     */
    public function userDefaultDataRegister($userId)
    {
        // Set up default data for the user
        // This method is called after user creation/authentication to set up default user data
        // Similar to how companies get default chart of accounts
    }

    /**
     * Get the user's plan ID for dashboard display
     */
    public function show_dashboard()
    {
        return $this->plan;
    }
}
