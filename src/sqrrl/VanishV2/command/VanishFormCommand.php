<?php

namespace sqrrl\VanishV2\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\form\VanishMainForm;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;

/**
 * Class VanishFormCommand
 * Handles the vanish form command
 */
class VanishFormCommand extends Command {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /**
     * VanishFormCommand constructor
     * 
     * @param VanishV2 $plugin
     * @param VanishManager $vanishManager
     * @param ConfigProvider $configProvider
     */
    public function __construct(VanishV2 $plugin, VanishManager $vanishManager, ConfigProvider $configProvider) {
        parent::__construct("vanishform", "Open the vanish form", "/vanishform", ["vf"]);
        $this->setPermission("vanish.use");
        
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
        
        if (!$sender instanceof Player) {
            $sender->sendMessage(VanishV2::PREFIX . TextFormat::RED . "This command can only be used in-game.");
            return false;
        }
        
        $form = new VanishMainForm($this->plugin, $this->vanishManager, $this->configProvider);
        $form->sendTo($sender);
        
        return true;
    }
}
