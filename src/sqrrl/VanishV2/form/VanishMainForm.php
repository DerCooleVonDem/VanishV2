<?php

namespace sqrrl\VanishV2\form;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\libs\jojoe77777\FormAPI\SimpleForm;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;

/**
 * Class VanishMainForm
 * Main form for the vanish system
 */
class VanishMainForm {
    /** @var VanishV2 */
    private VanishV2 $plugin;

    /** @var VanishManager */
    private VanishManager $vanishManager;

    /** @var ConfigProvider */
    private ConfigProvider $configProvider;

    /**
     * VanishMainForm constructor
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
     * Send the main form to a player
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
                return;
            }

            switch ($data) {
                case 0: // Toggle Vanish
                    if ($this->vanishManager->isVanished($player->getName())) {
                        $this->vanishManager->unvanishPlayer($player);
                    } else {
                        $this->vanishManager->vanishPlayer($player);
                    }
                    break;

                case 1: // Vanish Settings
                    $settingsForm = new VanishSettingsForm($this->plugin, $this->vanishManager, $this->configProvider);
                    $settingsForm->sendTo($player);
                    break;

                case 2: // Vanish List
                    $listForm = new VanishListForm($this->plugin, $this->vanishManager, $this->configProvider);
                    $listForm->sendTo($player);
                    break;

                case 3: // Vanish Stats
                    $statsForm = new VanishStatsForm($this->plugin, $this->vanishManager, $this->configProvider);
                    $statsForm->sendTo($player);
                    break;

                case 4: // Vanish Logs
                    $logsForm = new VanishLogsForm($this->plugin, $this->vanishManager, $this->configProvider);
                    $logsForm->sendTo($player);
                    break;
            }
        });

        $isVanished = $this->vanishManager->isVanished($player->getName());
        $vanishStatus = $isVanished ? TextFormat::GREEN . "Enabled" : TextFormat::RED . "Disabled";
        $toggleText = $isVanished ? TextFormat::RED . "Disable Vanish" : TextFormat::GREEN . "Enable Vanish";

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Main Menu");
        $form->setContent(TextFormat::YELLOW . "Current Vanish Status: " . $vanishStatus . "\n" .
                         TextFormat::GRAY . "Choose an option below:");

        $form->addButton($toggleText);
        $form->addButton(TextFormat::AQUA . "Vanish Settings");
        $form->addButton(TextFormat::YELLOW . "Vanish List");
        $form->addButton(TextFormat::GOLD . "Vanish Stats");
        $form->addButton(TextFormat::LIGHT_PURPLE . "Vanish Logs");

        $player->sendForm($form);
    }
}
