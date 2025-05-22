<?php

namespace sqrrl\VanishV2\event;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\scheduler\ClosureTask;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\util\VanishUtils;

/**
 * Class PlayerEventListener
 * Handles player-related events
 */
class PlayerEventListener implements Listener {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /**
     * PlayerEventListener constructor
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
     * Handle player join event
     * 
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        
        if (!$this->vanishManager->isVanished($playerName)) {
            $this->vanishManager->addOnlinePlayer($playerName);
            $this->plugin->updateHudPlayerCount();
        }
    }
    
    /**
     * Set nametag for vanished players
     * 
     * @param PlayerJoinEvent $event
     * @priority HIGHEST
     */
    public function setNametag(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        if ($this->vanishManager->isVanished($player->getName())) {
            VanishUtils::addVanishTag($player);
        }
    }
    
    /**
     * Handle silent join
     * 
     * @param PlayerJoinEvent $event
     * @priority HIGHEST
     */
    public function silentJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $silentJoinConfig = $this->configProvider->getSilentJoinLeave();
        
        if (VanishUtils::canSilentJoinLeave($player) && $silentJoinConfig['join']) {
            $vanishedOnly = $silentJoinConfig['vanished-only'];
            
            if (!$vanishedOnly || ($vanishedOnly && $this->vanishManager->isVanished($player->getName()))) {
                $event->setJoinMessage("");
            }
        }
    }
    
    /**
     * Handle player quit event
     * 
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        
        // Handle vanished player quitting
        if ($this->vanishManager->isVanished($name)) {
            if ($this->configProvider->getSetting('unvanish_after_leaving')) {
                $this->vanishManager->removeVanishedPlayer($name);
            }
        }
        
        // Update online players list
        if (in_array($name, $this->vanishManager->getOnlinePlayers(), true)) {
            $this->vanishManager->removeOnlinePlayer($name);
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function(): void {
                $this->plugin->updateHudPlayerCount();
            }), 20);
        }
    }
    
    /**
     * Handle silent leave
     * 
     * @param PlayerQuitEvent $event
     * @priority HIGHEST
     */
    public function silentLeave(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $silentLeaveConfig = $this->configProvider->getSilentJoinLeave();
        
        if (VanishUtils::canSilentJoinLeave($player) && $silentLeaveConfig['leave']) {
            $vanishedOnly = $silentLeaveConfig['vanished-only'];
            
            if (!$vanishedOnly || ($vanishedOnly && $this->vanishManager->isVanished($player->getName()))) {
                $event->setQuitMessage("");
            }
        }
    }
    
    /**
     * Handle player exhaustion event
     * 
     * @param PlayerExhaustEvent $event
     */
    public function onExhaust(PlayerExhaustEvent $event): void {
        $player = $event->getPlayer();
        if ($this->vanishManager->isVanished($player->getName()) && 
            !$this->configProvider->getSetting('hunger')) {
            $event->cancel();
        }
    }
}
