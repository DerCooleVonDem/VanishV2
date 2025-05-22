<?php

namespace sqrrl\VanishV2\event;

use pocketmine\event\Listener;
use Ifera\ScoreHud\event\TagsResolveEvent;
use sqrrl\VanishV2\manager\VanishManager;

/**
 * Class ScoreHudEventListener
 * Handles ScoreHud tag resolution for VanishV2
 */
class ScoreHudEventListener implements Listener {
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /**
     * ScoreHudEventListener constructor
     * 
     * @param VanishManager $vanishManager
     */
    public function __construct(VanishManager $vanishManager) {
        $this->vanishManager = $vanishManager;
    }

    /**
     * Handle tag resolution for ScoreHud
     * 
     * @param TagsResolveEvent $event
     */
    public function onTagResolve(TagsResolveEvent $event): void {
        $tag = $event->getTag();
        $tags = explode(".", $tag->getName(), 2);
        $value = "";

        if ($tags[0] !== "VanishV2" || count($tags) < 2) {
            return;
        }

        switch ($tags[1]) {
            case "fake_count":
                $value = count($this->vanishManager->getOnlinePlayers());
                break;
            case "vanished_count":
                $value = count($this->vanishManager->getVanishedPlayers());
                break;
        }
        
        $tag->setValue(strval($value));
    }
}
