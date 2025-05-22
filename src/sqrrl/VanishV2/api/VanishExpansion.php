<?php

namespace sqrrl\VanishV2\api;

use pocketmine\player\Player;
use MohamadRZ4\Placeholder\expansion\PlaceholderExpansion;
use sqrrl\VanishV2\manager\VanishManager;

/**
 * Class VanishExpansion
 * PlaceholderAPI expansion for VanishV2
 */
class VanishExpansion extends PlaceholderExpansion {
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /**
     * VanishExpansion constructor
     * 
     * @param VanishManager $vanishManager
     */
    public function __construct(VanishManager $vanishManager) {
        $this->vanishManager = $vanishManager;
    }
    
    /**
     * Get the identifier for this expansion
     * 
     * @return string
     */
    public function getIdentifier(): string {
        return "vanishv2";
    }

    /**
     * Get the author of this expansion
     * 
     * @return string
     */
    public function getAuthor(): string {
        return "sqrrl";
    }

    /**
     * Get the version of this expansion
     * 
     * @return string
     */
    public function getVersion(): string {
        return "1.0.0";
    }

    /**
     * Handle placeholder requests
     * 
     * @param Player|null $player
     * @param string $params
     * @return string|null
     */
    public function onPlaceholderRequest(?Player $player, string $params): ?string {
        if ($player === null) return null;

        switch($params) {
            case "fake_count":
                return strval(count($this->vanishManager->getOnlinePlayers()));
            case "vanished_count":
                return strval(count($this->vanishManager->getVanishedPlayers()));
            case "is_vanished":
                return $this->vanishManager->isVanished($player->getName()) ? "true" : "false";
            default:
                return null;
        }
    }
}
