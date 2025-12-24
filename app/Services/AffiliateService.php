<?php

namespace App\Services;

use App\Models\User;
use App\Models\AffiliateReferral;
use App\Models\AffiliateLinkClick;
use App\Models\ReferralSetting;

class AffiliateService
{
    public static function processReferral($newUserId, $referralCode = null)
    {
        if (!$referralCode) {
            return;
        }

        $referrer = User::where('referral_code', $referralCode)->first();
        if (!$referrer) {
            return;
        }

        $setting = ReferralSetting::first();
        if (!$setting || !$setting->is_enable) {
            return;
        }

        // Create level 1 referral
        AffiliateReferral::create([
            'affiliate_id' => $referrer->id,
            'referred_user_id' => $newUserId,
            'level' => 1,
            'commission_amount' => 0, // Will be calculated on payment
            'status' => 'registered',
            'created_by' => $referrer->id,
        ]);

        // Check for level 2 referrer
        if ($referrer->used_referral_code) {
            $level2Referrer = User::where('referral_code', $referrer->used_referral_code)->first();
            if ($level2Referrer) {
                AffiliateReferral::create([
                    'affiliate_id' => $level2Referrer->id,
                    'referred_user_id' => $newUserId,
                    'level' => 2,
                    'commission_amount' => 0, // Will be calculated on payment
                    'status' => 'registered',
                    'created_by' => $level2Referrer->id,
                ]);
            }
        }
    }

    public static function getAffiliateClicks($affiliateId, $startDate = null, $endDate = null)
    {
        $query = AffiliateLinkClick::where('affiliate_id', $affiliateId);

        if ($startDate) {
            $query->where('clicked_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('clicked_at', '<=', $endDate);
        }

        return $query->get();
    }

    public static function getClickStatistics($affiliateId)
    {
        $clicks = AffiliateLinkClick::where('affiliate_id', $affiliateId)->get();

        return [
            'total_clicks' => $clicks->count(),
            'unique_clicks' => $clicks->where('is_unique', true)->count(),
            'traffic_sources' => $clicks->groupBy('traffic_source')->map->count(),
            'utm_sources' => $clicks->whereNotNull('utm_source')->groupBy('utm_source')->map->count(),
            'recent_clicks' => $clicks->sortByDesc('clicked_at')->take(10),
            'clicks_today' => $clicks->where('clicked_at', '>=', now()->startOfDay())->count(),
            'clicks_this_week' => $clicks->where('clicked_at', '>=', now()->startOfWeek())->count(),
            'clicks_this_month' => $clicks->where('clicked_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    public static function getTrafficSourceBreakdown($affiliateId)
    {
        return AffiliateLinkClick::where('affiliate_id', $affiliateId)
            ->selectRaw('traffic_source, COUNT(*) as count')
            ->groupBy('traffic_source')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'traffic_source');
    }

    public static function getTopUTMSources($affiliateId, $limit = 10)
    {
        return AffiliateLinkClick::where('affiliate_id', $affiliateId)
            ->whereNotNull('utm_source')
            ->selectRaw('utm_source, COUNT(*) as count')
            ->groupBy('utm_source')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->pluck('count', 'utm_source');
    }

    public static function generateAffiliateLink($affiliateId, $baseUrl = null, $utmParams = [])
    {
        $affiliate = User::find($affiliateId);
        if (!$affiliate) {
            return null;
        }

        $baseUrl = $baseUrl ?: url('/register');
        $params = ['ref' => $affiliate->referral_code];

        // Add UTM parameters if provided
        if (!empty($utmParams)) {
            $params = array_merge($params, $utmParams);
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    public static function processPaymentCommission($userId, $paymentAmount, $paymentId = null)
    {
        $setting = ReferralSetting::first();
        if (!$setting || !$setting->is_enable) {
            return;
        }

        // Find level 1 referrals for this user
        $level1Referrals = AffiliateReferral::where('referred_user_id', $userId)
            ->where('level', 1)
            ->where('status', 'registered')
            ->get();

        foreach ($level1Referrals as $referral) {
            $commission = ($paymentAmount * $setting->level1_percentage) / 100;
            $referral->commission_amount = $commission;
            $referral->status = 'paid';
            $referral->payment_id = $paymentId;
            $referral->save();

            // Update affiliate's commission_amount
            $affiliate = User::find($referral->affiliate_id);
            if ($affiliate) {
                $affiliate->commission_amount += $commission;
                $affiliate->save();
            }
        }

        // Find level 2 referrals
        $level2Referrals = AffiliateReferral::where('referred_user_id', $userId)
            ->where('level', 2)
            ->where('status', 'registered')
            ->get();

        foreach ($level2Referrals as $referral) {
            $commission = ($paymentAmount * $setting->level2_percentage) / 100;
            $referral->commission_amount = $commission;
            $referral->status = 'paid';
            $referral->payment_id = $paymentId;
            $referral->save();

            // Update affiliate's commission_amount
            $affiliate = User::find($referral->affiliate_id);
            if ($affiliate) {
                $affiliate->commission_amount += $commission;
                $affiliate->save();
            }
        }
    }
}
