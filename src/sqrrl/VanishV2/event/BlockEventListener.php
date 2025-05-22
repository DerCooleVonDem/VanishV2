<?php

namespace sqrrl\VanishV2\event;

use pocketmine\block\Chest;
use pocketmine\block\inventory\DoubleChestInventory;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\world\WorldSoundEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\libs\muqsit\invmenu\InvMenu;
use sqrrl\VanishV2\libs\muqsit\invmenu\type\InvMenuTypeIds;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;

/**
 * Class BlockEventListener
 * Handles block-related events
 */
class BlockEventListener implements Listener {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /** @var array */
    private static array $silentBlocks = [];
    
    /**
     * BlockEventListener constructor
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
     * Handle player interaction event
     * 
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        
        if (!$this->vanishManager->isVanished($player->getName()) || 
            !$this->configProvider->getSetting('silent_chest')) {
            return;
        }
        
        $block = $event->getBlock();
        if (!($block instanceof Chest)) {
            return;
        }
        
        $action = $event->getAction();
        if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK && !$player->isSneaking()) {
            $event->cancel();
            
            $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
            $name = $block->getName();
            $inv = $tile->getInventory();
            $content = $inv->getContents();
            
            if ($content !== null) {
                // Create appropriate menu type
                $menu = $inv instanceof DoubleChestInventory 
                    ? InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST)
                    : InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
                
                $menu->getInventory()->setContents($content);
                $menu->setListener(InvMenu::readonly());
                $menu->setName($name);
                $menu->send($player);
            } else {
                $player->sendMessage(VanishV2::PREFIX . $this->configProvider->getMessage('chest_empty'));
            }
        } else {
            $event->cancel();
        }
    }
    
    /**
     * Handle block interaction for silent breaking
     * 
     * @param PlayerInteractEvent $event
     * @priority LOWEST
     */
    public function onBlockInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if ($this->vanishManager->isVanished($player->getName()) && 
            $event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
            $position = $event->getBlock()->getPosition();
            $delay = round($event->getBlock()->getBreakInfo()->getBreakTime($player->getInventory()->getItemInHand())) * 20;
            
            self::$silentBlocks[] = $position;
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($position): void {
                $index = array_search($position, self::$silentBlocks);
                if ($index !== false) {
                    unset(self::$silentBlocks[$index]);
                }
            }), $delay);
        }
    }
    
    /**
     * Handle world sound event for silent breaking
     * 
     * @param WorldSoundEvent $event
     * @priority HIGHEST
     */
    public function onWorldSoundBroadcast(WorldSoundEvent $event): void {
        foreach (self::$silentBlocks as $silentBlock) {
            if ($event->getPosition()->equals($silentBlock)) {
                $event->cancel();
            }
        }
    }
    
    /**
     * Handle block break event
     * 
     * @param BlockBreakEvent $event
     * @priority HIGHEST
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
        
        $player = $event->getPlayer();
        if (!$this->vanishManager->isVanished($player->getName())) {
            return;
        }
        
        $block = $event->getBlock();
        $event->cancel();
        $player->getWorld()->setBlock($block->getPosition(), VanillaBlocks::AIR());
        
        if ($player->isSurvival(true)) {
            $drops = $event->getDrops();
            $xpDrop = $event->getXpDropAmount();
            $player->getXpManager()->addXp($xpDrop);
            
            foreach ($drops as $drop) {
                if ($player->getInventory()->canAddItem($drop)) {
                    $player->getInventory()->addItem($drop);
                } else {
                    $player->getWorld()->dropItem($event->getBlock()->getPosition()->add(0.5, 0.5, 0.5), $drop);
                }
            }
            
            $item = $player->getInventory()->getItemInHand();
            $returnedItems = [];
            $item->onDestroyBlock($block, $returnedItems);
            $player->getInventory()->setItemInHand($item);
        }
    }
    
    /**
     * Handle block place event
     * 
     * @param BlockPlaceEvent $event
     * @priority HIGHEST
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
        
        $player = $event->getPlayer();
        if (!$this->vanishManager->isVanished($player->getName())) {
            return;
        }
        
        $event->cancel();
        $event->getTransaction()->apply();
        
        if ($player->isSurvival(true)) {
            $player->getInventory()->removeItem($event->getItem()->setCount(1));
        }
    }
}
