<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/12/2016
 * Time: 16:29
 */

namespace noelbosscom;


class IpAddress
{
    const IPv4 = 4;
    const IPv6 = 6;

    private $ip;
    private $version;

    /**
     * IpAddress constructor.
     * @param $ip
     */
    public function __construct($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $this->version = self::IPv4;
        } else {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                $this->version = self::IPv6;
            } else {
                throw new \InvalidArgumentException("$ip not a valid ip address");
            }
        }

        $this->ip = $ip;
    }

    public function __toString()
    {
        return $this->ip;
    }

    public function getVersion()
    {
        return $this->version;
    }
}