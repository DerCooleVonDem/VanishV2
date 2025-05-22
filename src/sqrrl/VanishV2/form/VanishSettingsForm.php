<?php

namespace sqrrl\VanishV2\form;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\libs\jojoe77777\FormAPI\CustomForm;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\util\VanishNotification;

/**
 * Class VanishSettingsForm
 * Settings form for the vanish system
 */
class VanishSettingsForm {
    /** @var VanishV2 */
    private VanishV2 $plugin;

    /** @var VanishManager */
    private VanishManager $vanishManager;

    /** @var ConfigProvider */
    private ConfigProvider $configProvider;

    /**
     * VanishSettingsForm constructor
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
     * Send the settings form to a player
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
        $form = new CustomForm(function(Player $player, ?array $data) {
            if ($data === null) {
                // Player closed the form
                $mainForm = new VanishMainForm($this->plugin, $this->vanishManager, $this->configProvider);
                $mainForm->sendTo($player);
                return;
            }

            // Process form data
            $currentMode = $this->vanishManager->getPlayerMode($player->getName());
            $currentGroup = $this->vanishManager->getPlayerGroup($player->getName());

            $selectedMode = $data[1] ?? $currentMode;
            $selectedGroup = $data[2] ?? $currentGroup;

            // Update mode if changed
            if ($selectedMode !== $currentMode) {
                $this->vanishManager->setPlayerMode($player, $selectedMode);

                // Notify the player
                $notification = new VanishNotification($this->plugin, $this->configProvider);
                $notification->notifyModeChange($player, $currentMode, $selectedMode);
            }

            // Update group if changed
            if ($selectedGroup !== $currentGroup) {
                $this->vanishManager->setPlayerGroup($player, $selectedGroup);

                // Notify the player
                $notification = new VanishNotification($this->plugin, $this->configProvider);
                $notification->notifyGroupChange($player, $currentGroup, $selectedGroup);
            }

            // Return to main form
            $mainForm = new VanishMainForm($this->plugin, $this->vanishManager, $this->configProvider);
            $mainForm->sendTo($player);
        });

        $isVanished = $this->vanishManager->isVanished($player->getName());
        $currentMode = $this->vanishManager->getPlayerMode($player->getName());
        $currentGroup = $this->vanishManager->getPlayerGroup($player->getName());

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Settings");

        $form->addLabel(TextFormat::YELLOW . "Configure your vanish settings below.\n" .
                       TextFormat::GRAY . "Current Status: " . ($isVanished ? TextFormat::GREEN . "Vanished" : TextFormat::RED . "Visible"));

        // Add mode dropdown
        $modes = $this->vanishManager->getAvailableModes($player);
        $modeNames = [];
        foreach ($modes as $mode) {
            $modeNames[] = $mode->getName();
        }
        $form->addDropdown(TextFormat::AQUA . "Vanish Mode", $modeNames, array_search($currentMode, array_keys($modes)));

        // Add group dropdown
        $groups = $this->vanishManager->getAvailableGroups($player);
        $groupNames = [];
        foreach ($groups as $group) {
            $groupNames[] = $group->getName();
        }
        $form->addDropdown(TextFormat::YELLOW . "Vanish Group", $groupNames, array_search($currentGroup, array_keys($groups)));

        // Add auto-vanish toggle
        $autoVanish = $this->vanishManager->hasAutoVanish($player->getName());
        $form->addToggle(TextFormat::GREEN . "Auto-Vanish on Join", $autoVanish);

        // Add notification settings
        $notifyStaff = $this->vanishManager->getPlayerNotificationSetting($player->getName(), 'notify_staff');
        $form->addToggle(TextFormat::GOLD . "Notify Staff When Vanishing", $notifyStaff);

        $player->sendForm($form);
    }
}
