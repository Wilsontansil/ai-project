<?php

namespace App\Services;

use App\Models\Player;
use App\Models\SettingGlobal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class utils {
    public static function generateUniqueCode($agent)
    {
        $code = Str::upper($agent . Str::random(9));

        if (Player::where('playercode', $code)->exists()) {
            return $this->generateUniqueCode($agent);
        }

        return $code;

    }

    static function getIp(){
        foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return server ip when no client ip found
      }

    static function make_comparer() {
        // Normalize criteria up front so that the comparer finds everything tidy
        $criteria = func_get_args();
        foreach ($criteria as $index => $criterion) {
            $criteria[$index] = is_array($criterion)
                ? array_pad($criterion, 3, null)
                : array($criterion, SORT_ASC, null);
        }

        return function($first, $second) use (&$criteria) {
            foreach ($criteria as $criterion) {
                // How will we compare this round?
                list($column, $sortOrder, $projection) = $criterion;
                $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

                // If a projection was defined project the values now
                if ($projection) {
                    $lhs = call_user_func($projection, $first[$column]);
                    $rhs = call_user_func($projection, $second[$column]);
                }
                else {
                    $lhs = $first[$column];
                    $rhs = $second[$column];
                }

                // Do the actual comparison; do not return if equal
                if ($lhs < $rhs) {
                    return -1 * $sortOrder;
                }
                else if ($lhs > $rhs) {
                    return 1 * $sortOrder;
                }
            }

            return 0; // tiebreakers exhausted, so $first == $second
        };
    }

    public static function guid($trim = true)
    {
        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true) {
                return trim(com_create_guid(), '{}');
            } else {
                return com_create_guid();
            }
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((float) microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $lbrace = $trim ? '' : chr(123); // "{"
        $rbrace = $trim ? '' : chr(125); // "}"
        $guidv4 = $lbrace.
        substr($charid, 0, 8).$hyphen.
        substr($charid, 8, 4).$hyphen.
        substr($charid, 12, 4).$hyphen.
        substr($charid, 16, 4).$hyphen.
        substr($charid, 20, 12).$rbrace;

        return $guidv4;
    }

    public static function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public static function getDefaultLanguage()
    {
        $defaultLanguage = Cache::remember('default_language', 3600, function () {
            $lang = SettingGlobal::where('kode', 'default_language')->first();
            return $lang->value ?? 'en';
        });
        return $defaultLanguage;
    }

    public static function removeCacheDefaultLanguage()
    {
        Cache::forget('default_language');
    }

    public static function convertDateTime2DateInt(Carbon $dt) {
        return intval($dt->format('Ymd'));
    }


}
