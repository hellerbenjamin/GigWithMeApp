<?php

namespace App\Support;

/**
 * Production (gigwithme.app) sits behind Cloudflare, so every
 * request reaches the server from one of Cloudflare's addresses. Trusting
 * these (and only these) lets Laravel read the visitor's real IP from
 * X-Forwarded-For, which the login rate limit relies on.
 * Trusting every proxy instead would let anyone who reached the server
 * directly fake their IP. Same list as akmusic's and commandcenter's.
 *
 * From https://www.cloudflare.com/ips/ (checked September 2026; the list
 * changes rarely). Update it here if Cloudflare announces new ranges.
 */
final class Cloudflare
{
    /** @var list<string> */
    public const PROXY_RANGES = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];
}
