<?php

namespace App\Support;

/**
 * Guards against Server-Side Request Forgery (SSRF) by rejecting URLs
 * that resolve to private, loopback, or link-local addresses.
 */
class UrlSsrfGuard
{
    /**
     * Assert that the given URL is safe to fetch (publicly routable host).
     *
     * @throws \InvalidArgumentException if the URL scheme is disallowed or the
     *                                   resolved IP falls in a private range.
     */
    public static function assertPublic(string $url): void
    {
        $parsed = parse_url($url);

        if ($parsed === false || empty($parsed['host'])) {
            throw new \InvalidArgumentException('SSRF guard: invalid or missing URL host.');
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException("SSRF guard: disallowed URL scheme '{$scheme}'.");
        }

        $host = strtolower($parsed['host']);

        // Strip IPv6 brackets: [::1] → ::1
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        // Resolve hostname to IP(s).
        $ips = self::resolveHost($host);

        if (empty($ips)) {
            throw new \InvalidArgumentException("SSRF guard: could not resolve host '{$host}'.");
        }

        foreach ($ips as $ip) {
            if (self::isPrivate($ip)) {
                throw new \InvalidArgumentException(
                    "SSRF guard: URL resolves to a private/restricted address ({$ip})."
                );
            }
        }
    }

    /**
     * Returns the IP(s) for a host. If $host is already an IP literal,
     * returns it directly without a DNS lookup.
     *
     * @return string[]
     */
    private static function resolveHost(string $host): array
    {
        // Already a numeric IPv4 or IPv6 literal — no DNS needed.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $result = gethostbynamel($host);

        return $result !== false ? $result : [];
    }

    /**
     * Returns true if the IP falls in a private, loopback, or link-local range.
     */
    private static function isPrivate(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // filter_var with NO_PRIV_RANGE | NO_RES_RANGE covers:
            //   10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16 (private)
            //   127.0.0.0/8 (loopback), 0.0.0.0/8, 169.254.0.0/16 (link-local)
            //   100.64.0.0/10 (shared address space) — NOT covered by the flags, added below
            $isPublic = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if ($isPublic === false) {
                return true;
            }

            // Carrier-grade NAT / shared address space (RFC 6598)
            if (self::ipInCidr($ip, '100.64.0.0', 10)) {
                return true;
            }

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Loopback ::1
            if ($ip === '::1') {
                return true;
            }
            // Link-local fe80::/10
            if (stripos($ip, 'fe80:') === 0) {
                return true;
            }
            // Unique-local fc00::/7 (fc:: and fd::)
            if (stripos($ip, 'fc') === 0 || stripos($ip, 'fd') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether an IPv4 address falls within a CIDR block.
     */
    private static function ipInCidr(string $ip, string $network, int $prefix): bool
    {
        $ipLong   = ip2long($ip);
        $netLong  = ip2long($network);
        $mask     = ~((1 << (32 - $prefix)) - 1);

        return ($ipLong & $mask) === ($netLong & $mask);
    }
}
