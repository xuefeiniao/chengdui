<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/21
 * Time: 11:15
 */

namespace app\index\controller;
use ltc\CoinClient;
use eth\EthCommon;


class Connectclient
{
    /**
     * LTC
     */
    public function ltc($username, $password, $ip, $port, $timeout = 30, $headers = array(), $suppress_errors = false)
    {
        return new CoinClient($username, $password, $ip, $port, $timeout, $headers, $suppress_errors);;
    }
    /**
     * ETH
     */
    public function eth($ip, $port='80', $version='2.0')
    {
        return new EthCommon($ip, $port, $version);
    }
    /**
     * 生成地址
     */
    public function createAddr($coin_info,$coin_password )
    {
        if(!isset($coin_info["type"])) return false;
        switch (strtolower($coin_info["type"]))
        {
            case "bitcoin":
                return false;
                $client=$this->ltc($coin_info["username"],$coin_info["password"],$coin_info["hostname"],$coin_info["port"]);
                if(empty($client->getblockchaininfo())) return false;
                $addr = $client->getnewaddress($coin_password);
                break;
            case "eth":
                $client=$this->eth($coin_info["hostname"],$coin_info["port"]);
                if(empty($client->web3_clientVersion()))return false;
                $addr = $client->personal_newAccount($coin_password);
                break;
            default:
                return false;
        }
        return $addr;
    }

}