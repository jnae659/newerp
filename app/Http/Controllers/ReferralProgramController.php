<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReferralSetting;
use App\Models\ReferralTransaction;
use App\Models\AffiliateReferral;
use App\Models\User;
use App\Models\TransactionOrder;
use App\Services\AffiliateService;

class ReferralProgramController extends Controller
{
    public function index()
    {
        if (\Auth::user()->type == 'accountant') {
            // Show accountant-specific referral dashboard
            return $this->accountantIndex();
        }

        $setting = ReferralSetting::where('created_by',\Auth::user()->id)->first();
        $payRequests = TransactionOrder::where('status' , 1)->get();

        $transactions = ReferralTransaction::get();

        return view('referral-program.index' , compact('setting' , 'payRequests' , 'transactions'));
    }

    public function accountantIndex()
    {
        $setting = ReferralSetting::where('created_by',1)->first();

        $objUser = \Auth::user();

        $referrals = AffiliateReferral::where('affiliate_id', $objUser->id)->with(['referredUser'])->get();

        $transactionsOrder = TransactionOrder::where('req_user_id',$objUser->id)->get();
        $paidAmount = $transactionsOrder->where('status' , 2)->sum('req_amount');

        $totalCommission = AffiliateReferral::where('affiliate_id', $objUser->id)->sum('commission_amount');
        $pendingCommission = AffiliateReferral::where('affiliate_id', $objUser->id)->where('status', 'registered')->sum('commission_amount');
        $earnedCommission = AffiliateReferral::where('affiliate_id', $objUser->id)->where('status', 'paid')->sum('commission_amount');

        $affiliateLink = url('/register?ref=' . $objUser->referral_code);

        // Get click statistics
        $clickStats = AffiliateService::getClickStatistics($objUser->id);
        $trafficSources = AffiliateService::getTrafficSourceBreakdown($objUser->id);
        $topUTMSources = AffiliateService::getTopUTMSources($objUser->id);

        $paymentRequest = TransactionOrder::where('status' , 1)->where('req_user_id',$objUser->id)->first();

        return view('referral-program.company' , compact(
            'setting',
            'referrals',
            'paidAmount',
            'transactionsOrder',
            'paymentRequest',
            'totalCommission',
            'pendingCommission',
            'earnedCommission',
            'affiliateLink',
            'clickStats',
            'trafficSources',
            'topUTMSources'
        ));
    }

    public function store(Request $request)
    {

        $validator = \Validator::make(
            $request->all(), [

                               'level1_percentage' => 'required',
                               'level2_percentage' => 'required',
                               'min_payout' => 'required',
                               'guideline' => 'required',
                           ]
        );

        if($validator->fails())
        {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        if($request->has('is_enable') && $request->is_enable == 'on')
        {
            $is_enable = 1;
        }
        else
        {
            $is_enable = 0;
        }

        $setting = ReferralSetting::where('created_by' , \Auth::user()->id)->first();

        if($setting == null)
        {
            $setting = new ReferralSetting();
        }
        $setting->level1_percentage = $request->level1_percentage;
        $setting->level2_percentage = $request->level2_percentage;
        $setting->min_payout = $request->min_payout;
        $setting->is_enable  = $is_enable;
        $setting->guideline = $request->guideline;
        $setting->created_by = \Auth::user()->creatorId();
        $setting->save();

        return redirect()->route('referral-program.index')->with('success', __('Referral Program Setting successfully Updated.'));

    }

    public function companyIndex()
    {
        $setting = ReferralSetting::where('created_by',1)->first();

        $objUser = \Auth::user();

        $referrals = AffiliateReferral::where('affiliate_id', $objUser->id)->with(['referredUser'])->get();

        $transactionsOrder = TransactionOrder::where('req_user_id',$objUser->id)->get();
        $paidAmount = $transactionsOrder->where('status' , 2)->sum('req_amount');

        $totalCommission = AffiliateReferral::where('affiliate_id', $objUser->id)->sum('commission_amount');
        $pendingCommission = AffiliateReferral::where('affiliate_id', $objUser->id)->where('status', 'registered')->sum('commission_amount');
        $earnedCommission = AffiliateReferral::where('affiliate_id', $objUser->id)->where('status', 'paid')->sum('commission_amount');

        $affiliateLink = url('/register?ref=' . $objUser->referral_code);

        // Get click statistics
        $clickStats = AffiliateService::getClickStatistics($objUser->id);
        $trafficSources = AffiliateService::getTrafficSourceBreakdown($objUser->id);
        $topUTMSources = AffiliateService::getTopUTMSources($objUser->id);

        $paymentRequest = TransactionOrder::where('status' , 1)->where('req_user_id',$objUser->id)->first();

        return view('referral-program.company' , compact(
            'setting',
            'referrals',
            'paidAmount',
            'transactionsOrder',
            'paymentRequest',
            'totalCommission',
            'pendingCommission',
            'earnedCommission',
            'affiliateLink',
            'clickStats',
            'trafficSources',
            'topUTMSources'
        ));
    }

    public function requestedAmountSent($id)
    {
        try{
            $id  = \Illuminate\Support\Facades\Crypt::decrypt($id);
        } catch (\Exception $e){
            return redirect()->back()->with('error', __('Something went wrong.'));
        }
        $paidAmount = TransactionOrder::where('req_user_id',\Auth::user()->id)->where('status' , 2)->sum('req_amount');
        $user = User::find(\Auth::user()->id);

        $netAmount = $user->commission_amount - $paidAmount;

        return view('referral-program.request_amount' , compact('id' , 'netAmount'));
    }

    public function requestCancel($id)
    {
        $transaction = TransactionOrder::where('req_user_id',$id)->orderBy('id','desc')->first();
        $transaction->delete();

        $redirectRoute = \Auth::user()->type == 'accountant' ? 'referral-program.index' : 'referral-program.company';
        return redirect()->route($redirectRoute)->with('success', __('Request Cancel Successfully.'));
    }

    public function requestedAmountStore(Request $request , $id)
    {
        $order = new TransactionOrder();
        $order->req_amount =  $request->request_amount;
        $order->req_user_id = \Auth::user()->id;
        $order->status = 1;
        $order->date = date('Y-m-d');
        $order->save();

        $redirectRoute = \Auth::user()->type == 'accountant' ? 'referral-program.index' : 'referral-program.company';
        return redirect()->route($redirectRoute)->with('success', __('Request Send Successfully.'));
    }

    public function requestedAmount($id , $status)
    {

        $setting = ReferralSetting::where('created_by',1)->first();

        $transaction = TransactionOrder::find($id);

        $paidAmount = TransactionOrder::where('req_user_id',$transaction->req_user_id)->where('status' , 2)->sum('req_amount');
        $user = User::find($transaction->req_user_id);

        $netAmount = $user->commission_amount - $paidAmount;

        $minAmount = isset($setting) ? $setting->min_payout : 0;
        if($status == 0)
        {
            $transaction->status = 0;

            $transaction->save();

            return redirect()->route('referral-program.index')->with('error', __('Request Rejected Successfully.'));
        }
        elseif($transaction->req_amount > $netAmount)
        {
            $transaction->status = 0;

            $transaction->save();

            return redirect()->route('referral-program.index')->with('error', __('This request cannot be accepted because it exceeds the commission amount.'));
        }
        elseif($transaction->req_amount < $minAmount)
        {
            $transaction->status = 0;

            $transaction->save();
            return redirect()->route('referral-program.index')->with('error', __('This request cannot be accepted because it less than the threshold amount.'));
        }
        else
        {
            $transaction->status = 2;

            $transaction->save();
            return redirect()->route('referral-program.index')->with('success', __('Request Aceepted Successfully.'));
        }
    }
}
