<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateLinkClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'referral_code',
        'ip_address',
        'user_agent',
        'referrer_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'traffic_source',
        'is_unique',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'is_unique' => 'boolean',
    ];

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    public static function determineTrafficSource($referrer, $utmSource = null)
    {
        // If UTM source is provided, use it
        if ($utmSource) {
            return match(strtolower($utmSource)) {
                'facebook', 'twitter', 'instagram', 'linkedin', 'pinterest', 'tiktok' => 'social',
                'google', 'bing', 'yahoo', 'duckduckgo' => 'search',
                'email', 'newsletter' => 'email',
                default => 'campaign'
            };
        }

        // Determine from referrer
        if (!$referrer) {
            return 'direct';
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        // Social media
        if (preg_match('/(facebook|twitter|instagram|linkedin|pinterest|tiktok|snapchat|reddit)\.com/i', $host)) {
            return 'social';
        }

        // Search engines
        if (preg_match('/(google|bing|yahoo|duckduckgo|baidu|yandex)\./i', $host)) {
            return 'organic';
        }

        // Email
        if (preg_match('/(mail\.|gmail\.|outlook\.|yahoo\.mail)/i', $host)) {
            return 'email';
        }

        // Referral
        return 'referral';
    }

    public static function trackClick($affiliateId, $referralCode, $request)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $referrer = $request->header('referer');

        // Check if this is a unique click (first click from this IP for this affiliate)
        $existingClick = self::where('affiliate_id', $affiliateId)
            ->where('ip_address', $ip)
            ->where('is_unique', true)
            ->exists();

        $isUnique = !$existingClick;

        // Extract UTM parameters
        $utmParams = [
            'source' => $request->query('utm_source'),
            'medium' => $request->query('utm_medium'),
            'campaign' => $request->query('utm_campaign'),
            'term' => $request->query('utm_term'),
            'content' => $request->query('utm_content'),
        ];

        $trafficSource = self::determineTrafficSource($referrer, $utmParams['source']);

        return self::create([
            'affiliate_id' => $affiliateId,
            'referral_code' => $referralCode,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'referrer_url' => $referrer,
            'utm_source' => $utmParams['source'],
            'utm_medium' => $utmParams['medium'],
            'utm_campaign' => $utmParams['campaign'],
            'utm_term' => $utmParams['term'],
            'utm_content' => $utmParams['content'],
            'traffic_source' => $trafficSource,
            'is_unique' => $isUnique,
            'clicked_at' => now(),
        ]);
    }
}
