<?php

namespace sqrrl\VanishV2\task;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\util\VanishUtils;

/**
 * Class VanishTask
 * Handles periodic tasks for vanished players
 */
class VanishTask extends Task {
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;

    /**
     * VanishTask constructor
     * 
     * @param VanishManager $vanishManager
     * @param ConfigProvider $configProvider
     */
    public function __construct(VanishManager $vanishManager, ConfigProvider $configProvider) {
        $this->vanishManager = $vanishManager;
        $this->configProvider = $configProvider;
    }

    /**
     * Task execution method
     */
    public function onRun(): void {
        $server = Server::getInstance();
        $vanishedPlayers = $this->vanishManager->getVanishedPlayers();
        
        // Skip processing if no players are vanished
        if (empty($vanishedPlayers)) {
            return;
        }
        
        $onlinePlayers = $server->getOnlinePlayers();
        $nightVisionEnabled = $this->configProvider->getSetting('night_vision');
        $hudMessage = $this->configProvider->getMessage('hud_message');
        
        foreach ($onlinePlayers as $player) {
            if (!$player->spawned) {
                continue;
            }
            
            $playerName = $player->getName();
            
            // Process vanished players
            if ($this->vanishManager->isVanished($playerName)) {
                // Send tip message
                $player->sendTip($hudMessage);
                
                // Set player as silent
                $player->setSilent(true);
                $player->getXpManager()->setCanAttractXpOrbs(false);
                
                // Apply night vision effect if enabled
                if ($nightVisionEnabled) {
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), null, 0, false));
                }
            }
        }
        
        // Process visibility for all players
        foreach ($onlinePlayers as $player) {
            $canSeeVanished = VanishUtils::canSeeVanished($player);
            
            foreach ($onlinePlayers as $otherPlayer) {
                $otherPlayerName = $otherPlayer->getName();
                
                if ($this->vanishManager->isVanished($otherPlayerName)) {
                    if ($canSeeVanished) {
                        $player->showPlayer($otherPlayer);
                    } else {
                        $player->hidePlayer($otherPlayer);
                        $player->getNetworkSession()->sendDataPacket(
                            PlayerListPacket::remove([
                                PlayerListEntry::createRemovalEntry($otherPlayer->getUniqueId())
                            ])
                        );
                    }
                }
            }
        }
    }
}
