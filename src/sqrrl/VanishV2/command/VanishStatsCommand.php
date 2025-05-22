<?php

namespace sqrrl\VanishV2\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\form\VanishStatsForm;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\util\VanishStats;

/**
 * Class VanishStatsCommand
 * Handles the vanish stats command
 */
class VanishStatsCommand extends Command {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /**
     * VanishStatsCommand constructor
     * 
     * @param VanishV2 $plugin
     * @param VanishManager $vanishManager
     * @param ConfigProvider $configProvider
     */
    public function __construct(VanishV2 $plugin, VanishManager $vanishManager, ConfigProvider $configProvider) {
        parent::__construct("vanishstats", "View vanish statistics", "/vanishstats [player]", ["vstats"]);
        $this->setPermission("vanish.stats");
        
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
        if (!$this->testPermission($sender)) {
            return false;
        }
        
        if ($sender instanceof Player) {
            // Show form for players
            if (count($args) === 0) {
                $form = new VanishStatsForm($this->plugin, $this->vanishManager, $this->configProvider);
                $form->sendTo($sender);
            } else {
                $playerName = $args[0];
                $stats = $this->plugin->getVanishStats()->getPlayerStats($playerName);
                
                if (empty($stats)) {
                    $sender->sendMessage(VanishV2::PREFIX . TextFormat::RED . "No stats found for player " . $playerName);
                    return false;
                }
                
                $this->showPlayerStats($sender, $playerName, $stats);
            }
        } else {
            // Show text stats for console
            if (count($args) === 0) {
                $this->showGlobalStats($sender);
            } else {
                $playerName = $args[0];
                $stats = $this->plugin->getVanishStats()->getPlayerStats($playerName);
                
                if (empty($stats)) {
                    $sender->sendMessage(VanishV2::PREFIX . TextFormat::RED . "No stats found for player " . $playerName);
                    return false;
                }
                
                $this->showPlayerStats($sender, $playerName, $stats);
            }
        }
        
        return true;
    }
    
    /**
     * Show global stats to a sender
     * 
     * @param CommandSender $sender
     */
    private function showGlobalStats(CommandSender $sender): void {
        $stats = $this->plugin->getVanishStats()->getTotalStats();
        $vanishTime = isset($stats['time_vanished']) ? VanishStats::formatTime($stats['time_vanished']) : "0s";
        
        $sender->sendMessage(VanishV2::PREFIX . TextFormat::YELLOW . "Global Vanish Statistics:");
        $sender->sendMessage(TextFormat::YELLOW . "Total Vanish Count: " . TextFormat::AQUA . ($stats['vanish_count'] ?? 0));
        $sender->sendMessage(TextFormat::YELLOW . "Total Unvanish Count: " . TextFormat::AQUA . ($stats['unvanish_count'] ?? 0));
        $sender->sendMessage(TextFormat::YELLOW . "Total Time Vanished: " . TextFormat::AQUA . $vanishTime);
        $sender->sendMessage(TextFormat::YELLOW . "Currently Vanished Players: " . TextFormat::AQUA . count($this->vanishManager->getVanishedPlayers()));
        $sender->sendMessage(TextFormat::YELLOW . "Total Players Tracked: " . TextFormat::AQUA . count($this->plugin->getVanishStats()->getAllPlayerStats()));
        
        // Show top players
        $topPlayers = $this->plugin->getVanishStats()->getTopVanishedPlayers(5);
        if (!empty($topPlayers)) {
            $sender->sendMessage(TextFormat::YELLOW . "Top 5 Players by Vanish Count:");
            $rank = 1;
            foreach ($topPlayers as $playerName => $playerStats) {
                $sender->sendMessage(TextFormat::YELLOW . "#" . $rank . " " . TextFormat::AQUA . $playerName . ": " . 
                                    TextFormat::GREEN . ($playerStats['vanish_count'] ?? 0) . " times");
                $rank++;
            }
        }
    }
    
    /**
     * Show player stats to a sender
     * 
     * @param CommandSender $sender
     * @param string $playerName
     * @param array $stats
     */
    private function showPlayerStats(CommandSender $sender, string $playerName, array $stats): void {
        $vanishTime = isset($stats['time_vanished']) ? VanishStats::formatTime($stats['time_vanished']) : "0s";
        
        $sender->sendMessage(VanishV2::PREFIX . TextFormat::YELLOW . "Vanish Statistics for " . TextFormat::AQUA . $playerName . ":");
        $sender->sendMessage(TextFormat::YELLOW . "Times Vanished: " . TextFormat::AQUA . ($stats['vanish_count'] ?? 0));
        $sender->sendMessage(TextFormat::YELLOW . "Times Unvanished: " . TextFormat::AQUA . ($stats['unvanish_count'] ?? 0));
        $sender->sendMessage(TextFormat::YELLOW . "Total Time Vanished: " . TextFormat::AQUA . $vanishTime);
        $sender->sendMessage(TextFormat::YELLOW . "Last Vanished: " . TextFormat::AQUA . (isset($stats['last_vanish']) ? date('Y-m-d H:i:s', $stats['last_vanish']) : "Never"));
        
        // Show mode usage
        if (isset($stats['modes']) && !empty($stats['modes'])) {
            $sender->sendMessage(TextFormat::YELLOW . "Mode Usage:");
            foreach ($stats['modes'] as $mode => $count) {
                $sender->sendMessage(TextFormat::YELLOW . "- " . TextFormat::AQUA . $mode . ": " . 
                                    TextFormat::GREEN . $count . " times");
            }
        }
        
        // Show group usage
        if (isset($stats['groups']) && !empty($stats['groups'])) {
            $sender->sendMessage(TextFormat::YELLOW . "Group Usage:");
            foreach ($stats['groups'] as $group => $count) {
                $sender->sendMessage(TextFormat::YELLOW . "- " . TextFormat::AQUA . $group . ": " . 
                                    TextFormat::GREEN . $count . " times");
            }
        }
    }
}
