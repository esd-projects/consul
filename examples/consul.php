<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/4/25
 * Time: 13:43
 */
require __DIR__ . '/../vendor/autoload.php';

use GoSwoole\Consul\ServiceFactory;
use SensioLabs\Consul\Services\HealthInterface;

go(function (){
    $sf = new ServiceFactory(["base_uri" => "http://192.168.1.200:8500"],null);
    $health = $sf->get(HealthInterface::class);
    print_r($health->checks("callcenter")->getBody());
});
