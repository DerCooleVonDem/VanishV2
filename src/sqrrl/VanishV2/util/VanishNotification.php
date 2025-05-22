<?php

namespace sqrrl\VanishV2\util;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\provider\ConfigProvider;

/**
 * Class VanishNotification
 * Handles notifications for vanish-related actions
 */
class VanishNotification {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /**
     * VanishNotification constructor
     * 
     * @param VanishV2 $plugin
     * @param ConfigProvider $configProvider
     */
    public function __construct(VanishV2 $plugin, ConfigProvider $configProvider) {
        $this->plugin = $plugin;
        $this->configProvider = $configProvider;
    }
    
    /**
     * Send a notification to a player
     * 
     * @param Player $player
     * @param string $message
     * @param string $type
     */
    public function sendToPlayer(Player $player, string $message, string $type = 'message'): void {
        switch ($type) {
            case 'tip':
                $player->sendTip($message);
                break;
            case 'popup':
                $player->sendPopup($message);
                break;
            case 'title':
                $player->sendTitle($message);
                break;
            case 'actionbar':
                $player->sendActionBarMessage($message);
                break;
            case 'message':
            default:
                $player->sendMessage(VanishV2::PREFIX . $message);
                break;
        }
    }
    
    /**
     * Send a notification to all staff members
     * 
     * @param string $message
     * @param string $type
     * @param Player|null $exclude
     */
    public function sendToStaff(string $message, string $type = 'message', ?Player $exclude = null): void {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if ($player === $exclude) {
                continue;
            }
            
            if ($player->hasPermission('vanish.see')) {
                $this->sendToPlayer($player, $message, $type);
            }
        }
    }
    
    /**
     * Notify when a player vanishes
     * 
     * @param Player $player
     * @param string|null $mode
     * @param string|null $group
     */
    public function notifyVanish(Player $player, ?string $mode = null, ?string $group = null): void {
        // Notify the player
        $message = $this->configProvider->getMessage('vanish');
        $this->sendToPlayer($player, $message);
        
        // Notify staff
        $staffMessage = $this->configProvider->getMessage('vanish_notify', ['%name' => $player->getName()]);
        
        // Add mode and group info if available
        if ($mode !== null) {
            $staffMessage .= TextFormat::GRAY . " (Mode: " . TextFormat::AQUA . $mode . TextFormat::GRAY . ")";
        }
        
        if ($group !== null) {
            $staffMessage .= TextFormat::GRAY . " (Group: " . TextFormat::YELLOW . $group . TextFormat::GRAY . ")";
        }
        
        $this->sendToStaff($staffMessage, 'message', $player);
    }
    
    /**
     * Notify when a player unvanishes
     * 
     * @param Player $player
     */
    public function notifyUnvanish(Player $player): void {
        // Notify the player
        $message = $this->configProvider->getMessage('unvanish');
        $this->sendToPlayer($player, $message);
        
        // Notify staff
        $staffMessage = $this->configProvider->getMessage('unvanish_notify', ['%name' => $player->getName()]);
        $this->sendToStaff($staffMessage, 'message', $player);
    }
    
    /**
     * Notify when a player vanishes another player
     * 
     * @param Player $player
     * @param Player $target
     * @param string|null $mode
     * @param string|null $group
     */
    public function notifyVanishOther(Player $player, Player $target, ?string $mode = null, ?string $group = null): void {
        // Notify the player who performed the action
        $message = $this->configProvider->getMessage('vanish_other', ['%name' => $target->getName()]);
        $this->sendToPlayer($player, $message);
        
        // Notify the target
        $targetMessage = $this->configProvider->getMessage('vanished_other', ['%other-name' => $player->getName()]);
        $this->sendToPlayer($target, $targetMessage);
        
        // Notify staff
        $staffMessage = TextFormat::GRAY . $player->getName() . " vanished " . $target->getName();
        
        // Add mode and group info if available
        if ($mode !== null) {
            $staffMessage .= TextFormat::GRAY . " (Mode: " . TextFormat::AQUA . $mode . TextFormat::GRAY . ")";
        }
        
        if ($group !== null) {
            $staffMessage .= TextFormat::GRAY . " (Group: " . TextFormat::YELLOW . $group . TextFormat::GRAY . ")";
        }
        
        $this->sendToStaff($staffMessage, 'message', $player);
    }
    
    /**
     * Notify when a player unvanishes another player
     * 
     * @param Player $player
     * @param Player $target
     */
    public function notifyUnvanishOther(Player $player, Player $target): void {
        // Notify the player who performed the action
        $message = $this->configProvider->getMessage('unvanish_other', ['%name' => $target->getName()]);
        $this->sendToPlayer($player, $message);
        
        // Notify the target
        $targetMessage = $this->configProvider->getMessage('unvanished_other', ['%other-name' => $player->getName()]);
        $this->sendToPlayer($target, $targetMessage);
        
        // Notify staff
        $staffMessage = TextFormat::GRAY . $player->getName() . " unvanished " . $target->getName();
        $this->sendToStaff($staffMessage, 'message', $player);
    }
    
    /**
     * Notify when a player changes vanish mode
     * 
     * @param Player $player
     * @param string $oldMode
     * @param string $newMode
     */
    public function notifyModeChange(Player $player, string $oldMode, string $newMode): void {
        // Notify the player
        $message = TextFormat::GREEN . "Your vanish mode has been changed from " . 
                  TextFormat::YELLOW . $oldMode . TextFormat::GREEN . " to " . 
                  TextFormat::YELLOW . $newMode;
        $this->sendToPlayer($player, $message);
        
        // Notify staff
        $staffMessage = TextFormat::GRAY . $player->getName() . " changed vanish mode from " . 
                       TextFormat::YELLOW . $oldMode . TextFormat::GRAY . " to " . 
                       TextFormat::YELLOW . $newMode;
        $this->sendToStaff($staffMessage, 'message', $player);
    }
    
    /**
     * Notify when a player changes vanish group
     * 
     * @param Player $player
     * @param string $oldGroup
     * @param string $newGroup
     */
    public function notifyGroupChange(Player $player, string $oldGroup, string $newGroup): void {
        // Notify the player
        $message = TextFormat::GREEN . "Your vanish group has been changed from " . 
                  TextFormat::YELLOW . $oldGroup . TextFormat::GREEN . " to " . 
                  TextFormat::YELLOW . $newGroup;
        $this->sendToPlayer($player, $message);
        
        // Notify staff
        $staffMessage = TextFormat::GRAY . $player->getName() . " changed vanish group from " . 
                       TextFormat::YELLOW . $oldGroup . TextFormat::GRAY . " to " . 
                       TextFormat::YELLOW . $newGroup;
        $this->sendToStaff($staffMessage, 'message', $player);
    }
}
