<?php

namespace sqrrl\VanishV2\util;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use sqrrl\VanishV2\VanishV2;

/**
 * Class VanishStats
 * Tracks statistics and logs for vanish usage
 */
class VanishStats {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var Config */
    private Config $statsConfig;
    
    /** @var array */
    private array $stats = [];
    
    /** @var array */
    private array $logs = [];
    
    /** @var int */
    private int $maxLogs;
    
    /**
     * VanishStats constructor
     * 
     * @param VanishV2 $plugin
     * @param int $maxLogs
     */
    public function __construct(VanishV2 $plugin, int $maxLogs = 1000) {
        $this->plugin = $plugin;
        $this->maxLogs = $maxLogs;
        $this->initStats();
        $this->initLogs();
    }
    
    /**
     * Initialize statistics
     */
    private function initStats(): void {
        $statsFile = $this->plugin->getDataFolder() . "stats.json";
        $this->statsConfig = new Config($statsFile, Config::JSON);
        $this->stats = $this->statsConfig->getAll();
        
        // Initialize default stats if not exists
        if (!isset($this->stats['total'])) {
            $this->stats['total'] = [
                'vanish_count' => 0,
                'unvanish_count' => 0,
                'time_vanished' => 0,
                'players' => []
            ];
            $this->saveStats();
        }
    }
    
    /**
     * Initialize logs
     */
    private function initLogs(): void {
        $logsFile = $this->plugin->getDataFolder() . "logs.json";
        $logsConfig = new Config($logsFile, Config::JSON);
        $this->logs = $logsConfig->getAll();
        
        // Trim logs if they exceed the maximum
        if (count($this->logs) > $this->maxLogs) {
            $this->logs = array_slice($this->logs, -$this->maxLogs);
            $this->saveLogs();
        }
    }
    
    /**
     * Save statistics to file
     */
    private function saveStats(): void {
        $this->statsConfig->setAll($this->stats);
        $this->statsConfig->save();
    }
    
    /**
     * Save logs to file
     */
    private function saveLogs(): void {
        $logsFile = $this->plugin->getDataFolder() . "logs.json";
        $logsConfig = new Config($logsFile, Config::JSON);
        $logsConfig->setAll($this->logs);
        $logsConfig->save();
    }
    
    /**
     * Record a player vanishing
     * 
     * @param string $playerName
     * @param string|null $mode
     * @param string|null $group
     */
    public function recordVanish(string $playerName, ?string $mode = null, ?string $group = null): void {
        // Update total stats
        $this->stats['total']['vanish_count']++;
        
        // Initialize player stats if not exists
        if (!isset($this->stats['players'][$playerName])) {
            $this->stats['players'][$playerName] = [
                'vanish_count' => 0,
                'unvanish_count' => 0,
                'time_vanished' => 0,
                'last_vanish' => 0,
                'modes' => [],
                'groups' => []
            ];
        }
        
        // Update player stats
        $this->stats['players'][$playerName]['vanish_count']++;
        $this->stats['players'][$playerName]['last_vanish'] = time();
        
        // Update mode stats
        if ($mode !== null) {
            if (!isset($this->stats['players'][$playerName]['modes'][$mode])) {
                $this->stats['players'][$playerName]['modes'][$mode] = 0;
            }
            $this->stats['players'][$playerName]['modes'][$mode]++;
            
            if (!isset($this->stats['modes'][$mode])) {
                $this->stats['modes'][$mode] = 0;
            }
            $this->stats['modes'][$mode]++;
        }
        
        // Update group stats
        if ($group !== null) {
            if (!isset($this->stats['players'][$playerName]['groups'][$group])) {
                $this->stats['players'][$playerName]['groups'][$group] = 0;
            }
            $this->stats['players'][$playerName]['groups'][$group]++;
            
            if (!isset($this->stats['groups'][$group])) {
                $this->stats['groups'][$group] = 0;
            }
            $this->stats['groups'][$group]++;
        }
        
        // Add log entry
        $this->logAction('vanish', $playerName, null, $mode, $group);
        
        $this->saveStats();
    }
    
    /**
     * Record a player unvanishing
     * 
     * @param string $playerName
     */
    public function recordUnvanish(string $playerName): void {
        // Update total stats
        $this->stats['total']['unvanish_count']++;
        
        // Check if player has stats
        if (!isset($this->stats['players'][$playerName])) {
            return;
        }
        
        // Update player stats
        $this->stats['players'][$playerName]['unvanish_count']++;
        
        // Calculate time vanished
        if ($this->stats['players'][$playerName]['last_vanish'] > 0) {
            $timeVanished = time() - $this->stats['players'][$playerName]['last_vanish'];
            $this->stats['players'][$playerName]['time_vanished'] += $timeVanished;
            $this->stats['total']['time_vanished'] += $timeVanished;
        }
        
        // Add log entry
        $this->logAction('unvanish', $playerName);
        
        $this->saveStats();
    }
    
    /**
     * Log a vanish action
     * 
     * @param string $action
     * @param string $playerName
     * @param string|null $targetName
     * @param string|null $mode
     * @param string|null $group
     * @param array $extra
     */
    public function logAction(string $action, string $playerName, ?string $targetName = null, ?string $mode = null, ?string $group = null, array $extra = []): void {
        $log = [
            'time' => time(),
            'action' => $action,
            'player' => $playerName,
            'target' => $targetName,
            'mode' => $mode,
            'group' => $group,
            'extra' => $extra
        ];
        
        $this->logs[] = $log;
        
        // Trim logs if they exceed the maximum
        if (count($this->logs) > $this->maxLogs) {
            array_shift($this->logs);
        }
        
        $this->saveLogs();
    }
    
    /**
     * Log a player vanishing
     * 
     * @param Player $player
     * @param string|null $mode
     * @param string|null $group
     */
    public function logVanish(Player $player, ?string $mode = null, ?string $group = null): void {
        $this->logAction('vanish', $player->getName(), null, $mode, $group);
    }
    
    /**
     * Log a player unvanishing
     * 
     * @param Player $player
     */
    public function logUnvanish(Player $player): void {
        $this->logAction('unvanish', $player->getName());
    }
    
    /**
     * Log a player vanishing another player
     * 
     * @param CommandSender $sender
     * @param Player $target
     * @param string|null $mode
     * @param string|null $group
     */
    public function logVanishOther(CommandSender $sender, Player $target, ?string $mode = null, ?string $group = null): void {
        $this->logAction('vanish_other', $sender->getName(), $target->getName(), $mode, $group);
    }
    
    /**
     * Log a player unvanishing another player
     * 
     * @param CommandSender $sender
     * @param Player $target
     */
    public function logUnvanishOther(CommandSender $sender, Player $target): void {
        $this->logAction('unvanish_other', $sender->getName(), $target->getName());
    }
    
    /**
     * Log a mode change
     * 
     * @param Player $player
     * @param string $oldMode
     * @param string $newMode
     */
    public function logModeChange(Player $player, string $oldMode, string $newMode): void {
        $this->logAction('mode_change', $player->getName(), null, $newMode, null, ['old_mode' => $oldMode]);
    }
    
    /**
     * Log a group change
     * 
     * @param Player $player
     * @param string $oldGroup
     * @param string $newGroup
     */
    public function logGroupChange(Player $player, string $oldGroup, string $newGroup): void {
        $this->logAction('group_change', $player->getName(), null, null, $newGroup, ['old_group' => $oldGroup]);
    }
    
    /**
     * Get total statistics
     * 
     * @return array
     */
    public function getTotalStats(): array {
        return $this->stats['total'] ?? [];
    }
    
    /**
     * Get player statistics
     * 
     * @param string $playerName
     * @return array
     */
    public function getPlayerStats(string $playerName): array {
        return $this->stats['players'][$playerName] ?? [];
    }
    
    /**
     * Get all player statistics
     * 
     * @return array
     */
    public function getAllPlayerStats(): array {
        return $this->stats['players'] ?? [];
    }
    
    /**
     * Get mode statistics
     * 
     * @return array
     */
    public function getModeStats(): array {
        return $this->stats['modes'] ?? [];
    }
    
    /**
     * Get group statistics
     * 
     * @return array
     */
    public function getGroupStats(): array {
        return $this->stats['groups'] ?? [];
    }
    
    /**
     * Get top vanished players
     * 
     * @param int $limit
     * @return array
     */
    public function getTopVanishedPlayers(int $limit = 10): array {
        $players = $this->stats['players'] ?? [];
        
        // Sort players by vanish count
        uasort($players, function($a, $b) {
            return $b['vanish_count'] <=> $a['vanish_count'];
        });
        
        return array_slice($players, 0, $limit, true);
    }
    
    /**
     * Get top vanished time players
     * 
     * @param int $limit
     * @return array
     */
    public function getTopVanishedTimePlayers(int $limit = 10): array {
        $players = $this->stats['players'] ?? [];
        
        // Sort players by time vanished
        uasort($players, function($a, $b) {
            return $b['time_vanished'] <=> $a['time_vanished'];
        });
        
        return array_slice($players, 0, $limit, true);
    }
    
    /**
     * Get all logs
     * 
     * @return array
     */
    public function getLogs(): array {
        return $this->logs;
    }
    
    /**
     * Get logs for a specific player
     * 
     * @param string $playerName
     * @return array
     */
    public function getPlayerLogs(string $playerName): array {
        return array_filter($this->logs, function($log) use ($playerName) {
            return $log['player'] === $playerName || $log['target'] === $playerName;
        });
    }
    
    /**
     * Get logs for a specific action
     * 
     * @param string $action
     * @return array
     */
    public function getActionLogs(string $action): array {
        return array_filter($this->logs, function($log) use ($action) {
            return $log['action'] === $action;
        });
    }
    
    /**
     * Get recent logs
     * 
     * @param int $count
     * @return array
     */
    public function getRecentLogs(int $count = 10): array {
        return array_slice($this->logs, -$count);
    }
    
    /**
     * Format time in seconds to a readable string
     * 
     * @param int $seconds
     * @return string
     */
    public static function formatTime(int $seconds): string {
        $days = floor($seconds / 86400);
        $seconds %= 86400;
        
        $hours = floor($seconds / 3600);
        $seconds %= 3600;
        
        $minutes = floor($seconds / 60);
        $seconds %= 60;
        
        $result = '';
        
        if ($days > 0) {
            $result .= $days . 'd ';
        }
        
        if ($hours > 0 || $days > 0) {
            $result .= $hours . 'h ';
        }
        
        if ($minutes > 0 || $hours > 0 || $days > 0) {
            $result .= $minutes . 'm ';
        }
        
        $result .= $seconds . 's';
        
        return $result;
    }
}
