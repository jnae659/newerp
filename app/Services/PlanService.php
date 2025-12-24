<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vender;
use Carbon\Carbon;

class PlanService
{
    public function assignPlan($user, $planID, $company_id = 0)
    {
        $plan = Plan::find($planID);
        if ($plan) {
            $user->plan = $plan->id;
            if ($user->trial_expire_date != null) {
                $user->trial_expire_date = null;
            }

            if ($plan->duration == 'month') {
                $user->plan_expire_date = Carbon::now()->addMonths(1)->isoFormat('YYYY-MM-DD');
            } elseif ($plan->duration == 'year') {
                $user->plan_expire_date = Carbon::now()->addYears(1)->isoFormat('YYYY-MM-DD');
            } else {
                $user->plan_expire_date = null;
            }
            $user->save();

            if ($company_id != 0) {
                $user_id = $company_id;
            } else {
                $user_id = $user->creatorId();
            }

            $users = User::where('created_by', '=', $user_id)->where('type', '!=', 'super admin')->where('type', '!=', 'company')->where('type', '!=', 'client')->get();
            $clients = User::where('created_by', '=', $user_id)->where('type', 'client')->get();
            $customers = Customer::where('created_by', '=', $user_id)->get();
            $venders = Vender::where('created_by', '=', $user_id)->get();

            if ($plan->max_users == -1) {
                foreach ($users as $userItem) {
                    $userItem->is_active = 1;
                    $userItem->save();
                }
            } else {
                $userCount = 0;
                foreach ($users as $userItem) {
                    $userCount++;
                    if ($userCount <= $plan->max_users) {
                        $userItem->is_active = 1;
                        $userItem->save();
                    } else {
                        $userItem->is_active = 0;
                        $userItem->save();
                    }
                }
            }

            if ($plan->max_clients == -1) {
                foreach ($clients as $client) {
                    $client->is_active = 1;
                    $client->save();
                }
            } else {
                $clientCount = 0;
                foreach ($clients as $client) {
                    $clientCount++;
                    if ($clientCount <= $plan->max_clients) {
                        $client->is_active = 1;
                        $client->save();
                    } else {
                        $client->is_active = 0;
                        $client->save();
                    }
                }
            }

            if ($plan->max_customers == -1) {
                foreach ($customers as $customer) {
                    $customer->is_active = 1;
                    $customer->save();
                }
            } else {
                $customerCount = 0;
                foreach ($customers as $customer) {
                    $customerCount++;
                    if ($customerCount <= $plan->max_customers) {
                        $customer->is_active = 1;
                        $customer->save();
                    } else {
                        $customer->is_active = 0;
                        $customer->save();
                    }
                }
            }

            if ($plan->max_venders == -1) {
                foreach ($venders as $vender) {
                    $vender->is_active = 1;
                    $vender->save();
                }
            } else {
                $venderCount = 0;
                foreach ($venders as $vender) {
                    $venderCount++;
                    if ($venderCount <= $plan->max_venders) {
                        $vender->is_active = 1;
                        $vender->save();
                    } else {
                        $vender->is_active = 0;
                        $vender->save();
                    }
                }
            }

            return ['is_success' => true];
        } else {
            return [
                'is_success' => false,
                'error' => 'Plan is deleted.',
            ];
        }
    }
}
