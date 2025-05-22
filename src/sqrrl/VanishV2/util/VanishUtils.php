<?php

namespace sqrrl\VanishV2\util;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * Class VanishUtils
 * Utility functions for the VanishV2 plugin
 */
class VanishUtils {
    /**
     * Format a message with replacements
     * 
     * @param string $message
     * @param array $replacements
     * @return string
     */
    public static function formatMessage(string $message, array $replacements = []): string {
        foreach ($replacements as $placeholder => $value) {
            $message = str_replace($placeholder, $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Check if a player has the vanish tag in their nametag
     * 
     * @param Player $player
     * @return bool
     */
    public static function hasVanishTag(Player $player): bool {
        return strpos($player->getNameTag(), "[V]") !== false;
    }
    
    /**
     * Add the vanish tag to a player's nametag
     * 
     * @param Player $player
     */
    public static function addVanishTag(Player $player): void {
        if (!self::hasVanishTag($player)) {
            $player->setNameTag(TextFormat::GOLD . "[V] " . TextFormat::RESET . $player->getNameTag());
        }
    }
    
    /**
     * Remove the vanish tag from a player's nametag
     * 
     * @param Player $player
     */
    public static function removeVanishTag(Player $player): void {
        $player->setNameTag(str_replace(TextFormat::GOLD . "[V] " . TextFormat::RESET, "", $player->getNameTag()));
    }
    
    /**
     * Check if a player can see vanished players
     * 
     * @param Player $player
     * @return bool
     */
    public static function canSeeVanished(Player $player): bool {
        return $player->hasPermission("vanish.see");
    }
    
    /**
     * Check if a player can attack while vanished
     * 
     * @param Player $player
     * @return bool
     */
    public static function canAttackWhileVanished(Player $player): bool {
        return $player->hasPermission("vanish.attack");
    }
    
    /**
     * Check if a player can silently join/leave
     * 
     * @param Player $player
     * @return bool
     */
    public static function canSilentJoinLeave(Player $player): bool {
        return $player->hasPermission("vanish.silent");
    }
}
