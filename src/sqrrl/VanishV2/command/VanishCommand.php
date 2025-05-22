<?php

namespace sqrrl\VanishV2\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;

/**
 * Class VanishCommand
 * Handles the vanish command
 */
class VanishCommand extends Command {
    /** @var VanishV2 */
    private VanishV2 $plugin;

    /** @var VanishManager */
    private VanishManager $vanishManager;

    /** @var ConfigProvider */
    private ConfigProvider $configProvider;

    /**
     * VanishCommand constructor
     *
     * @param VanishV2 $plugin
     * @param VanishManager $vanishManager
     * @param ConfigProvider $configProvider
     */
    public function __construct(VanishV2 $plugin, VanishManager $vanishManager, ConfigProvider $configProvider) {
        parent::__construct("vanish", "Enter vanish mode", "/vanish [player]", ["v"]);
        $this->setPermission("vanish.use");

        $this->plugin = $plugin;
        $this->vanishManager = $vanishManager;
        $this->configProvider = $configProvider;
    }

    /**
     * Execute the command
     *
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        // Check if sender has permission
        if (!$this->testPermission($sender)) {
            $sender->sendMessage(VanishV2::PREFIX . $this->configProvider->getMessage('no_permission'));
            return false;
        }

        // Handle vanishing another player
        if (count($args) === 1) {
            return $this->vanishOtherPlayer($sender, $args[0]);
        }

        // Handle vanishing self
        if (count($args) === 0) {
            return $this->vanishSelf($sender);
        }

        $sender->sendMessage(VanishV2::PREFIX . $this->configProvider->getMessage('invalid_usage'));

        return false;
    }

    /**
     * Handle vanishing self
     *
     * @param CommandSender $sender
     * @return bool
     */
    private function vanishSelf(CommandSender $sender): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(VanishV2::PREFIX . $this->configProvider->getMessage('in_game_only'));
            return false;
        }

        if (!$this->vanishManager->isVanished($sender->getName())) {
            $this->vanishManager->vanishPlayer($sender);
            // Message is sent by VanishManager
        } else {
            $this->vanishManager->unvanishPlayer($sender);
            // Message is sent by VanishManager
        }

        return true;
    }

    /**
     * Handle vanishing another player
     *
     * @param CommandSender $sender
     * @param string $targetName
     * @return bool
     */
    private function vanishOtherPlayer(CommandSender $sender, string $targetName): bool {
        if (!$sender->hasPermission("vanish.use.other")) {
            $sender->sendMessage(VanishV2::PREFIX . $this->configProvider->getMessage('no_permission_other'));
            return false;
        }

        $player = $this->plugin->getServer()->getPlayerByPrefix($targetName);
        if ($player === null) {
            $sender->sendMessage(VanishV2::PREFIX . $this->configProvider->getMessage('player_not_found'));
            return false;
        }

        if (!$this->vanishManager->isVanished($player->getName())) {
            // Use the vanishOtherPlayer method which handles notifications
            $this->vanishManager->vanishOtherPlayer($sender, $player);
        } else {
            // Use the unvanishOtherPlayer method which handles notifications
            $this->vanishManager->unvanishOtherPlayer($sender, $player);
        }

        // Messages are sent by VanishManager

        return true;
    }
}
