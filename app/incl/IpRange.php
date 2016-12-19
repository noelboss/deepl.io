<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/12/2016
 * Time: 16:29
 */

namespace noelbosscom;


class IpRange
{
    private $mask;

    /** @var  IpAddress */
    private $ip;

    /**
     * IpRange constructor.
     * @param $range
     */
    public function __construct($range)
    {
        if (strpos($range, '/') === false) {
            $this->ip = new IpAddress($range);
            $this->mask = pow(2, $this->ip->getVersion()) * 2;
        } else {
            list($ip, $this->mask) = explode('/', $range, 2);
            $this->ip = new IpAddress($ip);
        }
    }

    /**
     * @param $ip IpAddress
     * @return bool
     */
    public function contains($ip)
    {
        if ($ip->getVersion() !== $this->ip->getVersion()) {
            return false;
        }

        if ($ip->getVersion() == IpAddress::IPv4) {
            return $this->ipv4CIDRCheck((string)$ip, (string)$this->ip, $this->mask);
        } else {
            if ($ip->getVersion() == IpAddress::IPv6) {
                return $this->ipv6CIDRCheck((string)$ip, (string)$this->ip, $this->mask);
            } else {
                throw new \InvalidArgumentException("Unknown ip address version " . $ip->getVersion());
            }
        }
    }

    /**
     * @param $ip string
     * @param $net string
     * @param $mask string
     * @return bool
     *
     * Copied from http://www.phpclasses.org/browse/file/70429.html
     */
    private function ipv6CIDRCheck($ip, $net, $mask)
    {
        $subnet = inet_pton($net);
        $ip = inet_pton($ip);

        // thanks to MW on http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
        $binMask = str_repeat("f", $mask / 4);
        switch ($mask % 4) {
            case 0:
                break;
            case 1:
                $binMask .= "8";
                break;
            case 2:
                $binMask .= "c";
                break;
            case 3:
                $binMask .= "e";
                break;
        }
        $binMask = str_pad($binMask, 32, '0');
        $binMask = pack("H*", $binMask);


        return ($ip & $binMask) == $subnet;
    }

    /**
     * @param $ip string
     * @param $net string
     * @param $mask string
     * @return bool
     */
    private function ipv4CIDRCheck($ip, $net, $mask)
    {
        $ip_net = ip2long($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);

        $ip_ip = ip2long($ip);

        $ip_ip_net = $ip_ip & $ip_mask;


        echo "$ip_ip_net | $ip_net<br>";

        return ($ip_ip_net == $ip_net);
    }


    private function inetToBits($inet)
    {
        $unpacked = unpack('A16', $inet);
        $unpacked = str_split($unpacked[1]);
        $binaryIp = '';
        foreach ($unpacked as $char) {
            $binaryIp .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        return $binaryIp;
    }

}