<?php

declare(strict_types=1);

namespace DavidGlitch04\ChestKits\Economy;

use Closure;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use onebone\economyapi\EconomyAPI;
use DavidGlitch04\ChestKits\ChestKits;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

/**
 * Class EconomyManager
 * @package DavidGlitch04\ChestKits\Economy
 */
class EconomyManager{
    /** @var Plugin|null $eco */
    private ?Plugin $eco;
    /** @var ChestKits $plugin */
    private ChestKits $plugin;

    /**
     * EconomyManager constructor.
     * @param ChestKits $plugin
     */
    public function __construct(ChestKits $plugin){
        $this->plugin = $plugin;
        $manager = $plugin->getServer()->getPluginManager();
        $this->eco = $manager->getPlugin("EconomyAPI") ?? $manager->getPlugin("BedrockEconomy") ?? null;
        unset($manager);
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getMoney(Player $player, Closure $callback): void {
        switch ($this->eco->getName()) {
            case "EconomyAPI":
                $balance = $this->eco->myMoney($player->getName());
                assert(is_float($balance));
                $this->plugin->getConfig()->set("balance", $balance);
                $this->plugin->saveConfig();
                $callback($player, $balance);
                break;
            case "BedrockEconomy":
                $this->eco->getAPI()->getPlayerBalance($player->getName(), ClosureContext::create(static function (?int $balance) use ($player, $callback) : void {
                    $balance = $balance ?? 0;
                    $callback($player, $balance);
                    $this->plugin->getConfig()->set("balance", $balance);
                    $this->plugin->saveConfig();
                }));
                break;
            default:
                $this->eco->getAPI()->getPlayerBalance($player->getName(), ClosureContext::create(static function (?int $balance) use ($player, $callback) : void {
                    $balance = $balance ?? 0;
                    $callback($player, $balance);
                    $this->plugin->getConfig()->set("balance", $balance);
                    $this->plugin->saveConfig();
                })
        }
    }

    public function reduceMoney(Player $player, int $amount, Closure $callback) {
        if ($this->eco == null) {
            $this->plugin->getLogger()->warning("You don't have an Economy plugin");
            return true;
        }
        switch ($this->eco->getName()) {
            case "EconomyAPI":
                $success = $this->eco->reduceMoney($player->getName(), $amount) === EconomyAPI::RET_SUCCESS;
                if ($success) {
                    $balance = $this->eco->myMoney($player->getName());
                    $this->plugin->getConfig()->set("balance", $balance);
                    $this->plugin->saveConfig();
                }
                $callback($success);
                break;
            case "BedrockEconomy":
                $this->eco->getAPI()->subtractFromPlayerBalance($player->getName(), (int)ceil($amount), ClosureContext::create(static function (bool $success) use ($callback) : void {
                    if ($success) {
                        $callback($success);
                    } else {
                        $callback($success);
                    }
                    $balance = $this->eco->getAPI()->getPlayerBalance($player->getName());
                    $this->plugin->getConfig()->set("balance", $balance);
                    $this->plugin->saveConfig();
                }));
                break;
        }
    }
}
