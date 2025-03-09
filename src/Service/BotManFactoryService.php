<?php

namespace App\Service;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Cache\SymfonyCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\BotManFactory;
use Symfony\Contracts\Cache\CacheInterface;

class BotManFactoryService
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function createBotMan(): BotMan
    {
        DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

        $config = []; // Config vide pour WebDriver

        return BotManFactory::create($config, new SymfonyCache($this->cache));
    }
}
