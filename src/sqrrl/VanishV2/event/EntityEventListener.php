<?php

namespace sqrrl\VanishV2\event;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\player\Player;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\util\VanishUtils;

/**
 * Class EntityEventListener
 * Handles entity-related events
 */
class EntityEventListener implements Listener {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /**
     * EntityEventListener constructor
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
     * Handle item pickup event
     * 
     * @param EntityItemPickupEvent $event
     */
    public function pickUp(EntityItemPickupEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player && $this->vanishManager->isVanished($entity->getName())) {
            $event->cancel();
        }
    }
    
    /**
     * Handle entity damage event
     * 
     * @param EntityDamageEvent $event
     */
    public function onDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player && 
            $this->vanishManager->isVanished($entity->getName()) && 
            $this->configProvider->getSetting('disable_damage')) {
            $event->cancel();
        }
    }
    
    /**
     * Handle entity combustion event
     * 
     * @param EntityCombustEvent $event
     */
    public function onPlayerBurn(EntityCombustEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player && 
            $this->vanishManager->isVanished($entity->getName()) && 
            $this->configProvider->getSetting('disable_damage')) {
            $event->cancel();
        }
    }
    
    /**
     * Handle entity attack event
     * 
     * @param EntityDamageByEntityEvent $event
     */
    public function onAttack(EntityDamageByEntityEvent $event): void {
        $damager = $event->getDamager();
        $player = $event->getEntity();
        
        if (!($damager instanceof Player) || !($player instanceof Player)) {
            return;
        }
        
        if (!VanishUtils::canAttackWhileVanished($damager) && 
            $this->vanishManager->isVanished($damager->getName())) {
            $damager->sendMessage(VanishV2::PREFIX . $this->configProvider->getMessage('hit_no_permission'));
            $event->cancel();
        }
    }
}
