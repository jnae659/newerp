<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\AffiliateLinkClick;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateClick
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for referral code in multiple places:
        // 1. Query parameters (e.g., ?ref=123456)
        // 2. Route parameters (e.g., /register/123456)
        $referralCode = $request->query('ref') ??
                       $request->query('referral_code') ??
                       $request->route('ref_id') ??
                       $request->route('ref');

        // Debug logging
        \Log::info('Affiliate Click Tracking Debug:', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'query_ref' => $request->query('ref'),
            'query_referral_code' => $request->query('referral_code'),
            'route_ref_id' => $request->route('ref_id'),
            'route_ref' => $request->route('ref'),
            'detected_code' => $referralCode,
        ]);

        if ($referralCode) {
            $affiliate = User::where('referral_code', $referralCode)->first();

            \Log::info('Affiliate Lookup Result:', [
                'referral_code' => $referralCode,
                'affiliate_found' => $affiliate ? true : false,
                'affiliate_id' => $affiliate ? $affiliate->id : null,
                'affiliate_name' => $affiliate ? $affiliate->name : null,
            ]);

            if ($affiliate) {
                // Track the click asynchronously to avoid slowing down the response
                try {
                    $click = AffiliateLinkClick::trackClick($affiliate->id, $referralCode, $request);
                    \Log::info('Click Tracked Successfully:', [
                        'click_id' => $click->id,
                        'affiliate_id' => $affiliate->id,
                        'referral_code' => $referralCode,
                    ]);
                } catch (\Exception $e) {
                    // Log error but don't break the user experience
                    \Log::error('Failed to track affiliate click: ' . $e->getMessage());
                }
            }
        }

        return $next($request);
    }
}
