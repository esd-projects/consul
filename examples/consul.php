<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/25
 * Time: 13:43
 */
require __DIR__ . '/../vendor/autoload.php';

use GoSwoole\Consul\ServiceFactory;
use SensioLabs\Consul\ConsulResponse;
use SensioLabs\Consul\Services\KVInterface;
use SensioLabs\Consul\Services\SessionInterface;

$sf = new ServiceFactory(["base_uri" => "http://192.168.1.200:8500"], null);
$kv = $sf->get(KVInterface::class);
$session = $sf->get(SessionInterface::class);

function get($kv, $index)
{
    $response = $kv->get('test_service/leader', ["index" => $index, "wait" => "1m"], 65);
    if ($response instanceof ConsulResponse) {
        $index = $response->getHeaders()["x-consul-index"][0];
        var_dump($index);
        print_r($response->getBody() . "\n");
        get($kv, $index);
    }
}

go(function () use ($session, $kv) {
    $sessionId = $session->create(['LockDelay' => 0, 'Behavior' => 'release', 'Name' => "test_service"])->json()['ID'];
    // Lock a key / value with the current session
    $lockAcquired = $kv->put('test_service/leader', 'a value', ['acquire' => $sessionId])->json();
    //这将返回true或false。如果true，已获取锁定并且本地服务实例现在是领导者。如果false返回，则某个其他节点已获得锁定。
    if (false === $lockAcquired) {
        $session->destroy($sessionId);
        echo "The lock is already acquire by another node.\n";
        exit(1);
    }
});

go(function () use ($kv) {
    Co::Sleep(1);
    get($kv, 0);
});


go(function () use ($session, $kv) {
    Co::Sleep(2);
    $sessionId = $session->create(['LockDelay' => 0, 'Behavior' => 'release', 'Name' => "test_service"])->json()['ID'];
    // Lock a key / value with the current session
    $lockAcquired = $kv->put('test_service/leader', 'a value', ['acquire' => $sessionId])->json();
    //这将返回true或false。如果true，已获取锁定并且本地服务实例现在是领导者。如果false返回，则某个其他节点已获得锁定。
    if (false === $lockAcquired) {
        $session->destroy($sessionId);
        echo "The lock is already acquire by another node.\n";
        exit();
    }
});