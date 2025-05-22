<?php

namespace sqrrl\VanishV2\form;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\libs\jojoe77777\FormAPI\SimpleForm;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;

/**
 * Class VanishListForm
 * Shows a list of vanished players
 */
class VanishListForm {
    /** @var VanishV2 */
    private VanishV2 $plugin;

    /** @var VanishManager */
    private VanishManager $vanishManager;

    /** @var ConfigProvider */
    private ConfigProvider $configProvider;

    /**
     * VanishListForm constructor
     *
     * @param VanishV2 $plugin
     * @param VanishManager $vanishManager
     * @param ConfigProvider $configProvider
     */
    public function __construct(VanishV2 $plugin, VanishManager $vanishManager, ConfigProvider $configProvider) {
        $this->plugin = $plugin;
        $this->vanishManager = $vanishManager;
        $this->configProvider = $configProvider;
    }

    /**
     * Send the list form to a player
     *
     * @param Player|string $player
     */
    public function sendTo($player): void {
        // Make sure we have a Player object
        if (!($player instanceof Player)) {
            $playerObj = $this->plugin->getServer()->getPlayerByPrefix($player);
            if ($playerObj === null) {
                return; // Can't proceed without a valid player
            }
            $player = $playerObj;
        }
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null) {
                // Player closed the form
                $mainForm = new VanishMainForm($this->plugin, $this->vanishManager, $this->configProvider);
                $mainForm->sendTo($player);
                return;
            }

            if ($data === 0) {
                // Back button
                $mainForm = new VanishMainForm($this->plugin, $this->vanishManager, $this->configProvider);
                $mainForm->sendTo($player);
                return;
            }

            // Get the selected player
            $vanishedPlayers = $this->vanishManager->getVanishedPlayers();
            $selectedIndex = $data - 1;

            if (!isset(array_values($vanishedPlayers)[$selectedIndex])) {
                return;
            }

            $selectedPlayerName = array_values($vanishedPlayers)[$selectedIndex];
            $selectedPlayer = $this->plugin->getServer()->getPlayerByPrefix($selectedPlayerName);

            if ($selectedPlayer === null) {
                $player->sendMessage(VanishV2::PREFIX . TextFormat::RED . "Player not found or offline.");
                return;
            }

            // Show player actions form
            $this->showPlayerActionsForm($player, $selectedPlayer);
        });

        $vanishedPlayers = $this->vanishManager->getVanishedPlayers();
        $count = count($vanishedPlayers);

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Vanished Players");
        $form->setContent(TextFormat::YELLOW . "There are currently " . TextFormat::GREEN . $count .
                         TextFormat::YELLOW . " vanished players.\n" .
                         TextFormat::GRAY . "Select a player to view actions:");

        $form->addButton(TextFormat::RED . "Back");

        foreach ($vanishedPlayers as $playerName) {
            $player = $this->plugin->getServer()->getPlayerByPrefix($playerName);

            if ($player !== null) {
                $mode = $this->vanishManager->getPlayerMode($playerName);
                $group = $this->vanishManager->getPlayerGroup($playerName);

                $buttonText = TextFormat::AQUA . $playerName . "\n" .
                             TextFormat::GRAY . "Mode: " . TextFormat::YELLOW . $mode .
                             TextFormat::GRAY . " | Group: " . TextFormat::YELLOW . $group;

                $form->addButton($buttonText);
            }
        }

        $player->sendForm($form);
    }

    /**
     * Show actions form for a specific player
     *
     * @param Player $player
     * @param Player $target
     */
    private function showPlayerActionsForm(Player $player, Player $target): void {
        $form = new SimpleForm(function(Player $player, ?int $data) use ($target) {
            if ($data === null) {
                // Player closed the form
                $this->sendTo($player);
                return;
            }

            switch ($data) {
                case 0: // Back
                    $this->sendTo($player);
                    break;

                case 1: // Unvanish
                    if ($this->vanishManager->isVanished($target->getName())) {
                        $this->vanishManager->unvanishPlayer($target);
                        $player->sendMessage(VanishV2::PREFIX . TextFormat::GREEN . "Unvanished " . $target->getName());
                    }
                    $this->sendTo($player);
                    break;

                case 2: // Teleport
                    $player->teleport($target->getPosition());
                    $player->sendMessage(VanishV2::PREFIX . TextFormat::GREEN . "Teleported to " . $target->getName());
                    break;

                case 3: // View Info
                    $this->showPlayerInfoForm($player, $target);
                    break;
            }
        });

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Player Actions");
        $form->setContent(TextFormat::YELLOW . "Player: " . TextFormat::AQUA . $target->getName() . "\n" .
                         TextFormat::YELLOW . "Mode: " . TextFormat::AQUA . $this->vanishManager->getPlayerMode($target->getName()) . "\n" .
                         TextFormat::YELLOW . "Group: " . TextFormat::AQUA . $this->vanishManager->getPlayerGroup($target->getName()) . "\n\n" .
                         TextFormat::GRAY . "Choose an action:");

        $form->addButton(TextFormat::RED . "Back");
        $form->addButton(TextFormat::RED . "Unvanish Player");
        $form->addButton(TextFormat::GREEN . "Teleport to Player");
        $form->addButton(TextFormat::AQUA . "View Player Info");

        $player->sendForm($form);
    }

    /**
     * Show info form for a specific player
     *
     * @param Player $player
     * @param Player $target
     */
    private function showPlayerInfoForm(Player $player, Player $target): void {
        $form = new SimpleForm(function(Player $player, ?int $data) use ($target) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->showPlayerActionsForm($player, $target);
                return;
            }
        });

        $stats = $this->plugin->getVanishStats()->getPlayerStats($target->getName());
        $vanishTime = isset($stats['time_vanished']) ? $this->plugin->getVanishStats()->formatTime($stats['time_vanished']) : "0s";

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Player Info");
        $form->setContent(
            TextFormat::YELLOW . "Player: " . TextFormat::AQUA . $target->getName() . "\n\n" .
            TextFormat::YELLOW . "Vanish Mode: " . TextFormat::AQUA . $this->vanishManager->getPlayerMode($target->getName()) . "\n" .
            TextFormat::YELLOW . "Vanish Group: " . TextFormat::AQUA . $this->vanishManager->getPlayerGroup($target->getName()) . "\n\n" .
            TextFormat::YELLOW . "Times Vanished: " . TextFormat::AQUA . ($stats['vanish_count'] ?? 0) . "\n" .
            TextFormat::YELLOW . "Times Unvanished: " . TextFormat::AQUA . ($stats['unvanish_count'] ?? 0) . "\n" .
            TextFormat::YELLOW . "Total Time Vanished: " . TextFormat::AQUA . $vanishTime . "\n\n" .
            TextFormat::YELLOW . "Auto-Vanish: " . ($this->vanishManager->hasAutoVanish($target->getName()) ? TextFormat::GREEN . "Enabled" : TextFormat::RED . "Disabled") . "\n" .
            TextFormat::YELLOW . "Last Vanished: " . TextFormat::AQUA . (isset($stats['last_vanish']) ? date('Y-m-d H:i:s', $stats['last_vanish']) : "Never")
        );

        $form->addButton(TextFormat::RED . "Back");

        $player->sendForm($form);
    }
}
