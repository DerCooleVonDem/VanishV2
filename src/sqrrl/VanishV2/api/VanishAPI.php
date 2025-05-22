<?php

namespace sqrrl\VanishV2\api;

use pocketmine\player\Player;
use sqrrl\VanishV2\manager\VanishManager;

/**
 * Class VanishAPI
 * Public API for the VanishV2 plugin
 */
class VanishAPI {
    /** @var VanishManager */
    private static VanishManager $vanishManager;
    
    /**
     * Initialize the API
     * 
     * @param VanishManager $vanishManager
     */
    public static function init(VanishManager $vanishManager): void {
        self::$vanishManager = $vanishManager;
    }
    
    /**
     * Check if a player is vanished
     * 
     * @param string|Player $player Player name or Player instance
     * @return bool
     */
    public static function isVanished($player): bool {
        $playerName = $player instanceof Player ? $player->getName() : $player;
        return self::$vanishManager->isVanished($playerName);
    }
    
    /**
     * Vanish a player
     * 
     * @param Player $player
     * @return bool
     */
    public static function vanishPlayer(Player $player): bool {
        if (self::isVanished($player)) {
            return false;
        }
        
        self::$vanishManager->vanishPlayer($player);
        return true;
    }
    
    /**
     * Unvanish a player
     * 
     * @param Player $player
     * @return bool
     */
    public static function unvanishPlayer(Player $player): bool {
        if (!self::isVanished($player)) {
            return false;
        }
        
        self::$vanishManager->unvanishPlayer($player);
        return true;
    }
    
    /**
     * Toggle a player's vanish state
     * 
     * @param Player $player
     * @return bool True if player is now vanished, false if unvanished
     */
    public static function toggleVanish(Player $player): bool {
        if (self::isVanished($player)) {
            self::$vanishManager->unvanishPlayer($player);
            return false;
        } else {
            self::$vanishManager->vanishPlayer($player);
            return true;
        }
    }
    
    /**
     * Get all vanished players
     * 
     * @return array
     */
    public static function getVanishedPlayers(): array {
        return self::$vanishManager->getVanishedPlayers();
    }
    
    /**
     * Get the count of vanished players
     * 
     * @return int
     */
    public static function getVanishedCount(): int {
        return count(self::$vanishManager->getVanishedPlayers());
    }
    
    /**
     * Get the count of online players (excluding vanished)
     * 
     * @return int
     */
    public static function getVisiblePlayerCount(): int {
        return count(self::$vanishManager->getOnlinePlayers());
    }
}
