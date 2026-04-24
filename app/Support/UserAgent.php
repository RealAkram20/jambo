<?php

namespace App\Support;

/**
 * Minimal UA classifier. Hits the common cases so the UI can render
 * "Chrome on Windows" style labels without pulling in a composer
 * dependency. Shared by the admin profile sessions list and the
 * streaming device-limit picker so both surfaces render identical
 * labels for the same UA string.
 */
class UserAgent
{
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;
        $browser = 'Unknown browser';
        $os = 'Unknown OS';
        $icon = 'ph-globe';

        if (preg_match('/Edg\/([\d.]+)/i', $ua))                    { $browser = 'Microsoft Edge'; }
        elseif (preg_match('/OPR\/([\d.]+)/i', $ua))                { $browser = 'Opera'; }
        elseif (preg_match('/Chrome\/([\d.]+)/i', $ua))             { $browser = 'Chrome'; }
        elseif (preg_match('/Firefox\/([\d.]+)/i', $ua))            { $browser = 'Firefox'; }
        elseif (preg_match('/Version\/[\d.]+.*Safari/i', $ua))      { $browser = 'Safari'; }
        elseif (stripos($ua, 'curl') !== false)                     { $browser = 'curl'; }
        elseif (stripos($ua, 'PostmanRuntime') !== false)           { $browser = 'Postman'; }

        // iPhone / iPad UA strings contain "like Mac OS X", so test
        // iOS first otherwise we mis-label them as macOS.
        if (preg_match('/iPhone|iPad|iPod/i', $ua))                 { $os = 'iOS';     $icon = 'ph-device-mobile'; }
        elseif (preg_match('/Android ([\d.]+)/i', $ua))             { $os = 'Android'; $icon = 'ph-device-mobile'; }
        elseif (preg_match('/Windows NT ([\d.]+)/i', $ua))          { $os = 'Windows'; $icon = 'ph-desktop'; }
        elseif (stripos($ua, 'Mac OS X') !== false)                 { $os = 'macOS';   $icon = 'ph-desktop'; }
        elseif (stripos($ua, 'Linux') !== false)                    { $os = 'Linux';   $icon = 'ph-desktop'; }

        return ['browser' => $browser, 'os' => $os, 'icon' => $icon];
    }

    public static function label(?string $ua): string
    {
        $p = self::parse($ua);
        return $p['browser'] . ' on ' . $p['os'];
    }
}
