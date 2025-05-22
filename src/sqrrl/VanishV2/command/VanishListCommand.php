<?php

namespace sqrrl\VanishV2\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\form\VanishListForm;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;

/**
 * Class VanishListCommand
 * Handles the vanish list command
 */
class VanishListCommand extends Command {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /**
     * VanishListCommand constructor
     * 
     * @param VanishV2 $plugin
     * @param VanishManager $vanishManager
     * @param ConfigProvider $configProvider
     */
    public function __construct(VanishV2 $plugin, VanishManager $vanishManager, ConfigProvider $configProvider) {
        parent::__construct("vanishlist", "List all vanished players", "/vanishlist", ["vlist"]);
        $this->setPermission("vanish.list");
        
        $this->plugin = $plugin;
        $this->vanishManager = $vanishManager;
        $this->configProvider = $configProvider;
    }
    
    /**
     * Execute the command
     * 
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }
        
        $vanishedPlayers = $this->vanishManager->getVanishedPlayers();
        $count = count($vanishedPlayers);
        
        if ($count === 0) {
            $sender->sendMessage(VanishV2::PREFIX . TextFormat::YELLOW . "There are no vanished players.");
            return true;
        }
        
        if ($sender instanceof Player) {
            // Show form for players
            $form = new VanishListForm($this->plugin, $this->vanishManager, $this->configProvider);
            $form->sendTo($sender);
        } else {
            // Show text list for console
            $sender->sendMessage(VanishV2::PREFIX . TextFormat::YELLOW . "Vanished Players (" . $count . "):");
            
            foreach ($vanishedPlayers as $playerName) {
                $mode = $this->vanishManager->getPlayerMode($playerName);
                $group = $this->vanishManager->getPlayerGroup($playerName);
                
                $sender->sendMessage(TextFormat::YELLOW . "- " . TextFormat::AQUA . $playerName . 
                                    TextFormat::GRAY . " (Mode: " . TextFormat::YELLOW . $mode . 
                                    TextFormat::GRAY . ", Group: " . TextFormat::YELLOW . $group . 
                                    TextFormat::GRAY . ")");
            }
        }
        
        return true;
    }
}
