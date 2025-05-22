<?php

namespace sqrrl\VanishV2\event;

use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\player\Player;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\util\VanishUtils;

/**
 * Class ServerEventListener
 * Handles server-related events
 */
class ServerEventListener implements Listener {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /**
     * ServerEventListener constructor
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
     * Handle server query event
     * 
     * @param QueryRegenerateEvent $event
     */
    public function onQuery(QueryRegenerateEvent $event): void {
        $event->getQueryInfo()->setPlayerList($this->vanishManager->getOnlinePlayers());
        
        $vanishedCount = count($this->vanishManager->getVanishedPlayers());
        if ($vanishedCount > 0) {
            $online = $event->getQueryInfo()->getPlayerCount();
            $event->getQueryInfo()->setPlayerCount($online - $vanishedCount);
        }
    }
    
    /**
     * Handle command execution event
     * 
     * @param CommandEvent $event
     */
    public function onCommandExecute(CommandEvent $event): void {
        $sender = $event->getSender();
        if (!$sender instanceof Player) {
            return;
        }
        
        if ($this->configProvider->getSetting('can_send_msg')) {
            return;
        }
        
        $message = $event->getCommand();
        $message = explode(" ", $message);
        $command = array_shift($message);
        
        // Handle private message commands
        if (in_array(strtolower($command), ["tell", "msg", "w"])) {
            $this->handlePrivateMessageCommand($event, $sender, $message);
            return;
        }
        
        // Handle additional commands
        if ($this->configProvider->isAdditionalCommand(strtolower($command))) {
            $this->handleAdditionalCommand($event, $sender, $command, $message);
        }
    }
    
    /**
     * Handle private message commands
     * 
     * @param CommandEvent $event
     * @param Player $sender
     * @param array $message
     */
    private function handlePrivateMessageCommand(CommandEvent $event, Player $sender, array $message): void {
        if (!isset($message[0])) {
            return;
        }
        
        $receiver = $this->plugin->getServer()->getPlayerByPrefix(array_shift($message));
        if ($receiver === null || trim(implode(" ", $message)) === "") {
            return;
        }
        
        if ($this->vanishManager->isVanished($receiver->getName()) && 
            !VanishUtils::canSeeVanished($sender) && 
            $sender !== $receiver) {
            $event->cancel();
            $sender->sendMessage($this->configProvider->getMessage('sender_error'));
            $receiver->sendMessage(VanishV2::PREFIX . $this->configProvider->getMessage('receiver_message', [
                '%sender' => $sender->getName(),
                '%message' => implode(" ", $message)
            ]));
        }
    }
    
    /**
     * Handle additional commands
     * 
     * @param CommandEvent $event
     * @param Player $sender
     * @param string $command
     * @param array $message
     */
    private function handleAdditionalCommand(CommandEvent $event, Player $sender, string $command, array $message): void {
        $additionalCommands = $this->configProvider->getAdditionalCommands();
        if (!array_key_exists(strtolower($command), $additionalCommands)) {
            return;
        }
        
        $receiver = $this->plugin->getServer()->getPlayerByPrefix(array_shift($message));
        if ($receiver === null) {
            return;
        }
        
        if ($this->vanishManager->isVanished($receiver->getName()) && 
            !VanishUtils::canSeeVanished($sender) && 
            $sender !== $receiver) {
            $event->cancel();
            $sender->sendMessage($additionalCommands[$command]["sender-error"]);
            $receiver->sendMessage(VanishV2::PREFIX . str_replace("%sender", $sender->getName(), 
                $additionalCommands[$command]["receiver-message"]));
        }
    }
}
