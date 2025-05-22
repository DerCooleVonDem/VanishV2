<?php

namespace sqrrl\VanishV2\form;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\libs\jojoe77777\FormAPI\SimpleForm;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\util\VanishStats;

/**
 * Class VanishStatsForm
 * Shows vanish statistics
 */
class VanishStatsForm {
    /** @var VanishV2 */
    private VanishV2 $plugin;

    /** @var VanishManager */
    private VanishManager $vanishManager;

    /** @var ConfigProvider */
    private ConfigProvider $configProvider;

    /**
     * VanishStatsForm constructor
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
     * Send the stats form to a player
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

            switch ($data) {
                case 0: // Back
                    $mainForm = new VanishMainForm($this->plugin, $this->vanishManager, $this->configProvider);
                    $mainForm->sendTo($player);
                    break;

                case 1: // Global Stats
                    $this->showGlobalStatsForm($player);
                    break;

                case 2: // Personal Stats
                    $this->showPersonalStatsForm($player);
                    break;

                case 3: // Top Players
                    $this->showTopPlayersForm($player);
                    break;

                case 4: // Mode Stats
                    $this->showModeStatsForm($player);
                    break;

                case 5: // Group Stats
                    $this->showGroupStatsForm($player);
                    break;
            }
        });

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Statistics");
        $form->setContent(TextFormat::YELLOW . "Select a category to view statistics:");

        $form->addButton(TextFormat::RED . "Back");
        $form->addButton(TextFormat::AQUA . "Global Stats");
        $form->addButton(TextFormat::GREEN . "Personal Stats");
        $form->addButton(TextFormat::GOLD . "Top Players");
        $form->addButton(TextFormat::LIGHT_PURPLE . "Mode Stats");
        $form->addButton(TextFormat::YELLOW . "Group Stats");

        $player->sendForm($form);
    }

    /**
     * Show global stats form
     *
     * @param Player $player
     */
    private function showGlobalStatsForm(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->sendTo($player);
                return;
            }
        });

        $stats = $this->plugin->getVanishStats()->getTotalStats();
        $vanishTime = isset($stats['time_vanished']) ? VanishStats::formatTime($stats['time_vanished']) : "0s";

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Global Stats");
        $form->setContent(
            TextFormat::YELLOW . "Total Vanish Count: " . TextFormat::AQUA . ($stats['vanish_count'] ?? 0) . "\n" .
            TextFormat::YELLOW . "Total Unvanish Count: " . TextFormat::AQUA . ($stats['unvanish_count'] ?? 0) . "\n" .
            TextFormat::YELLOW . "Total Time Vanished: " . TextFormat::AQUA . $vanishTime . "\n\n" .
            TextFormat::YELLOW . "Currently Vanished Players: " . TextFormat::AQUA . count($this->vanishManager->getVanishedPlayers()) . "\n" .
            TextFormat::YELLOW . "Total Players Tracked: " . TextFormat::AQUA . count($this->plugin->getVanishStats()->getAllPlayerStats())
        );

        $form->addButton(TextFormat::RED . "Back");

        $player->sendForm($form);
    }

    /**
     * Show personal stats form
     *
     * @param Player $player
     */
    private function showPersonalStatsForm(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->sendTo($player);
                return;
            }
        });

        $stats = $this->plugin->getVanishStats()->getPlayerStats($player->getName());
        $vanishTime = isset($stats['time_vanished']) ? VanishStats::formatTime($stats['time_vanished']) : "0s";

        // Get mode usage
        $modeUsage = "";
        if (isset($stats['modes']) && !empty($stats['modes'])) {
            foreach ($stats['modes'] as $mode => $count) {
                $modeUsage .= TextFormat::YELLOW . "- " . TextFormat::AQUA . $mode . ": " .
                             TextFormat::GREEN . $count . " times\n";
            }
        } else {
            $modeUsage = TextFormat::GRAY . "No mode usage recorded\n";
        }

        // Get group usage
        $groupUsage = "";
        if (isset($stats['groups']) && !empty($stats['groups'])) {
            foreach ($stats['groups'] as $group => $count) {
                $groupUsage .= TextFormat::YELLOW . "- " . TextFormat::AQUA . $group . ": " .
                              TextFormat::GREEN . $count . " times\n";
            }
        } else {
            $groupUsage = TextFormat::GRAY . "No group usage recorded\n";
        }

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Personal Stats");
        $form->setContent(
            TextFormat::YELLOW . "Player: " . TextFormat::AQUA . $player->getName() . "\n\n" .
            TextFormat::YELLOW . "Times Vanished: " . TextFormat::AQUA . ($stats['vanish_count'] ?? 0) . "\n" .
            TextFormat::YELLOW . "Times Unvanished: " . TextFormat::AQUA . ($stats['unvanish_count'] ?? 0) . "\n" .
            TextFormat::YELLOW . "Total Time Vanished: " . TextFormat::AQUA . $vanishTime . "\n" .
            TextFormat::YELLOW . "Last Vanished: " . TextFormat::AQUA . (isset($stats['last_vanish']) ? date('Y-m-d H:i:s', $stats['last_vanish']) : "Never") . "\n\n" .
            TextFormat::YELLOW . "Mode Usage:\n" . $modeUsage . "\n" .
            TextFormat::YELLOW . "Group Usage:\n" . $groupUsage
        );

        $form->addButton(TextFormat::RED . "Back");

        $player->sendForm($form);
    }

    /**
     * Show top players form
     *
     * @param Player $player
     */
    private function showTopPlayersForm(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->sendTo($player);
                return;
            }
        });

        $topVanished = $this->plugin->getVanishStats()->getTopVanishedPlayers(10);
        $topVanishedTime = $this->plugin->getVanishStats()->getTopVanishedTimePlayers(10);

        // Format top vanished players
        $topVanishedText = "";
        $rank = 1;
        foreach ($topVanished as $playerName => $stats) {
            $topVanishedText .= TextFormat::YELLOW . "#" . $rank . " " . TextFormat::AQUA . $playerName . ": " .
                               TextFormat::GREEN . ($stats['vanish_count'] ?? 0) . " times\n";
            $rank++;
        }

        if (empty($topVanishedText)) {
            $topVanishedText = TextFormat::GRAY . "No data available\n";
        }

        // Format top vanished time players
        $topVanishedTimeText = "";
        $rank = 1;
        foreach ($topVanishedTime as $playerName => $stats) {
            $time = isset($stats['time_vanished']) ? VanishStats::formatTime($stats['time_vanished']) : "0s";
            $topVanishedTimeText .= TextFormat::YELLOW . "#" . $rank . " " . TextFormat::AQUA . $playerName . ": " .
                                   TextFormat::GREEN . $time . "\n";
            $rank++;
        }

        if (empty($topVanishedTimeText)) {
            $topVanishedTimeText = TextFormat::GRAY . "No data available\n";
        }

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Top Players");
        $form->setContent(
            TextFormat::GOLD . "Top Players by Vanish Count:\n" . $topVanishedText . "\n" .
            TextFormat::GOLD . "Top Players by Vanish Time:\n" . $topVanishedTimeText
        );

        $form->addButton(TextFormat::RED . "Back");

        $player->sendForm($form);
    }

    /**
     * Show mode stats form
     *
     * @param Player $player
     */
    private function showModeStatsForm(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->sendTo($player);
                return;
            }
        });

        $modeStats = $this->plugin->getVanishStats()->getModeStats();

        // Format mode stats
        $modeStatsText = "";
        if (!empty($modeStats)) {
            arsort($modeStats);
            foreach ($modeStats as $mode => $count) {
                $modeStatsText .= TextFormat::YELLOW . "- " . TextFormat::AQUA . $mode . ": " .
                                 TextFormat::GREEN . $count . " times\n";
            }
        } else {
            $modeStatsText = TextFormat::GRAY . "No mode usage recorded\n";
        }

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Mode Stats");
        $form->setContent(
            TextFormat::GOLD . "Mode Usage Statistics:\n\n" . $modeStatsText
        );

        $form->addButton(TextFormat::RED . "Back");

        $player->sendForm($form);
    }

    /**
     * Show group stats form
     *
     * @param Player $player
     */
    private function showGroupStatsForm(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->sendTo($player);
                return;
            }
        });

        $groupStats = $this->plugin->getVanishStats()->getGroupStats();

        // Format group stats
        $groupStatsText = "";
        if (!empty($groupStats)) {
            arsort($groupStats);
            foreach ($groupStats as $group => $count) {
                $groupStatsText .= TextFormat::YELLOW . "- " . TextFormat::AQUA . $group . ": " .
                                  TextFormat::GREEN . $count . " times\n";
            }
        } else {
            $groupStatsText = TextFormat::GRAY . "No group usage recorded\n";
        }

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Group Stats");
        $form->setContent(
            TextFormat::GOLD . "Group Usage Statistics:\n\n" . $groupStatsText
        );

        $form->addButton(TextFormat::RED . "Back");

        $player->sendForm($form);
    }
}
