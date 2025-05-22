<?php

namespace sqrrl\VanishV2\manager;

use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\model\VanishMode;
use sqrrl\VanishV2\model\VanishGroup;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\provider\DataProvider;
use sqrrl\VanishV2\util\VanishNotification;
use sqrrl\VanishV2\util\VanishStats;
use sqrrl\VanishV2\util\VanishUtils;

/**
 * Class VanishManager
 * Manages all vanish-related functionality
 */
class VanishManager {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /** @var DataProvider */
    private DataProvider $dataProvider;
    
    /** @var VanishStats */
    private VanishStats $stats;
    
    /** @var VanishNotification */
    private VanishNotification $notification;
    
    /** @var array List of vanished players */
    private array $vanishedPlayers = [];
    
    /** @var array List of online players (excluding vanished) */
    private array $onlinePlayers = [];
    
    /** @var array Player modes */
    private array $playerModes = [];
    
    /** @var array Player groups */
    private array $playerGroups = [];
    
    /** @var array Auto-vanish players */
    private array $autoVanishPlayers = [];
    
    /** @var array Player notification settings */
    private array $playerNotificationSettings = [];
    
    /** @var array Available modes */
    private array $modes = [];
    
    /** @var array Available groups */
    private array $groups = [];
    
    /** @var string Default mode */
    private string $defaultMode = 'default';
    
    /** @var string Default group */
    private string $defaultGroup = 'default';
    
    /**
     * VanishManager constructor
     * 
     * @param VanishV2 $plugin
     * @param ConfigProvider $configProvider
     * @param DataProvider $dataProvider
     */
    public function __construct(VanishV2 $plugin, ConfigProvider $configProvider, DataProvider $dataProvider) {
        $this->plugin = $plugin;
        $this->configProvider = $configProvider;
        $this->dataProvider = $dataProvider;
        
        // Initialize utilities
        $this->stats = new VanishStats($plugin);
        $this->notification = new VanishNotification($plugin, $configProvider);
        
        // Load modes and groups
        $this->loadModes();
        $this->loadGroups();
        
        // Load vanished players from storage
        $this->loadVanishedPlayers();
        
        // Load player settings
        $this->loadPlayerSettings();
    }
    
    /**
     * Load vanish modes from config
     */
    private function loadModes(): void {
        $modesConfig = $this->configProvider->getConfig()->getNested('modes', [
            'default' => [
                'name' => 'Default',
                'description' => 'Standard vanish mode',
                'permission' => 'vanish.mode.default',
                'settings' => [
                    'fly' => true,
                    'night_vision' => true,
                    'disable_damage' => true,
                    'silent_chest' => true,
                    'hunger' => false
                ]
            ],
            'staff' => [
                'name' => 'Staff',
                'description' => 'Staff vanish mode with special features',
                'permission' => 'vanish.mode.staff',
                'settings' => [
                    'fly' => true,
                    'night_vision' => true,
                    'disable_damage' => true,
                    'silent_chest' => true,
                    'hunger' => false,
                    'staff_chat' => true,
                    'see_reports' => true
                ]
            ],
            'spectator' => [
                'name' => 'Spectator',
                'description' => 'Spectator mode with no interaction',
                'permission' => 'vanish.mode.spectator',
                'settings' => [
                    'fly' => true,
                    'night_vision' => true,
                    'disable_damage' => true,
                    'silent_chest' => true,
                    'hunger' => false,
                    'no_interact' => true,
                    'no_place' => true,
                    'no_break' => true
                ]
            ]
        ]);
        
        foreach ($modesConfig as $id => $data) {
            $this->modes[$id] = VanishMode::fromConfig($id, $data);
        }
    }
    
    /**
     * Load vanish groups from config
     */
    private function loadGroups(): void {
        $groupsConfig = $this->configProvider->getConfig()->getNested('groups', [
            'default' => [
                'name' => 'Default',
                'description' => 'Standard vanish group',
                'permission' => 'vanish.group.default',
                'visible_to' => ['default', 'admin', 'mod'],
                'settings' => []
            ],
            'admin' => [
                'name' => 'Admin',
                'description' => 'Admin vanish group',
                'permission' => 'vanish.group.admin',
                'visible_to' => ['admin'],
                'settings' => []
            ],
            'mod' => [
                'name' => 'Moderator',
                'description' => 'Moderator vanish group',
                'permission' => 'vanish.group.mod',
                'visible_to' => ['admin', 'mod'],
                'settings' => []
            ]
        ]);
        
        foreach ($groupsConfig as $id => $data) {
            $this->groups[$id] = VanishGroup::fromConfig($id, $data);
        }
    }
    
    /**
     * Load vanished players from storage
     */
    private function loadVanishedPlayers(): void {
        $players = $this->dataProvider->loadVanishedPlayers();
        foreach ($players as $player) {
            $this->addVanishedPlayer($player);
        }
    }
    
    /**
     * Load player settings from storage
     */
    private function loadPlayerSettings(): void {
        $settings = $this->dataProvider->loadPlayerSettings();
        
        if (isset($settings['modes'])) {
            $this->playerModes = $settings['modes'];
        }
        
        if (isset($settings['groups'])) {
            $this->playerGroups = $settings['groups'];
        }
        
        if (isset($settings['auto_vanish'])) {
            $this->autoVanishPlayers = $settings['auto_vanish'];
        }
        
        if (isset($settings['notifications'])) {
            $this->playerNotificationSettings = $settings['notifications'];
        }
    }
    
    /**
     * Save player settings to storage
     */
    private function savePlayerSettings(): void {
        $settings = [
            'modes' => $this->playerModes,
            'groups' => $this->playerGroups,
            'auto_vanish' => $this->autoVanishPlayers,
            'notifications' => $this->playerNotificationSettings
        ];
        
        $this->dataProvider->savePlayerSettings($settings);
    }
    
    /**
     * Check if a player is vanished
     * 
     * @param string $playerName
     * @return bool
     */
    public function isVanished(string $playerName): bool {
        return in_array($playerName, $this->vanishedPlayers);
    }
    
    /**
     * Get all vanished players
     * 
     * @return array
     */
    public function getVanishedPlayers(): array {
        return $this->vanishedPlayers;
    }
    
    /**
     * Get all online players (excluding vanished)
     * 
     * @return array
     */
    public function getOnlinePlayers(): array {
        return $this->onlinePlayers;
    }
    
    /**
     * Add a player to the vanished list
     * 
     * @param string $playerName
     */
    public function addVanishedPlayer(string $playerName): void {
        if (!$this->isVanished($playerName)) {
            $this->vanishedPlayers[] = $playerName;
            
            // Update legacy static array for backward compatibility
            VanishV2::$vanish[] = $playerName;
            
            // Remove from online players list
            $this->removeOnlinePlayer($playerName);
        }
    }
    
    /**
     * Remove a player from the vanished list
     * 
     * @param string $playerName
     */
    public function removeVanishedPlayer(string $playerName): void {
        $index = array_search($playerName, $this->vanishedPlayers);
        if ($index !== false) {
            unset($this->vanishedPlayers[$index]);
            
            // Update legacy static array for backward compatibility
            $legacyIndex = array_search($playerName, VanishV2::$vanish);
            if ($legacyIndex !== false) {
                unset(VanishV2::$vanish[$legacyIndex]);
            }
            
            // Add to online players list
            $this->addOnlinePlayer($playerName);
        }
    }
    
    /**
     * Add a player to the online list
     * 
     * @param string $playerName
     */
    public function addOnlinePlayer(string $playerName): void {
        if (!$this->isVanished($playerName) && !in_array($playerName, $this->onlinePlayers, true)) {
            $this->onlinePlayers[] = $playerName;
            
            // Update legacy static array for backward compatibility
            VanishV2::$online[] = $playerName;
        }
    }
    
    /**
     * Remove a player from the online list
     * 
     * @param string $playerName
     */
    public function removeOnlinePlayer(string $playerName): void {
        $index = array_search($playerName, $this->onlinePlayers, true);
        if ($index !== false) {
            unset($this->onlinePlayers[$index]);
            
            // Update legacy static array for backward compatibility
            $legacyIndex = array_search($playerName, VanishV2::$online, true);
            if ($legacyIndex !== false) {
                unset(VanishV2::$online[$legacyIndex]);
            }
        }
    }
    
    /**
     * Get a player's vanish mode
     * 
     * @param string $playerName
     * @return string
     */
    public function getPlayerMode(string $playerName): string {
        return $this->playerModes[$playerName] ?? $this->defaultMode;
    }
    
    /**
     * Set a player's vanish mode
     * 
     * @param Player $player
     * @param string $mode
     * @return bool
     */
    public function setPlayerMode(Player $player, string $mode): bool {
        if (!isset($this->modes[$mode])) {
            return false;
        }
        
        $modeObj = $this->modes[$mode];
        if (!$player->hasPermission($modeObj->getPermission())) {
            return false;
        }
        
        $oldMode = $this->getPlayerMode($player->getName());
        $this->playerModes[$player->getName()] = $mode;
        $this->savePlayerSettings();
        
        // Log mode change
        $this->stats->logModeChange($player, $oldMode, $mode);
        
        return true;
    }
    
    /**
     * Get a player's vanish group
     * 
     * @param string $playerName
     * @return string
     */
    public function getPlayerGroup(string $playerName): string {
        return $this->playerGroups[$playerName] ?? $this->defaultGroup;
    }
    
    /**
     * Set a player's vanish group
     * 
     * @param Player $player
     * @param string $group
     * @return bool
     */
    public function setPlayerGroup(Player $player, string $group): bool {
        if (!isset($this->groups[$group])) {
            return false;
        }
        
        $groupObj = $this->groups[$group];
        if (!$player->hasPermission($groupObj->getPermission())) {
            return false;
        }
        
        $oldGroup = $this->getPlayerGroup($player->getName());
        $this->playerGroups[$player->getName()] = $group;
        $this->savePlayerSettings();
        
        // Log group change
        $this->stats->logGroupChange($player, $oldGroup, $group);
        
        return true;
    }
    
    /**
     * Check if a player has auto-vanish enabled
     * 
     * @param string $playerName
     * @return bool
     */
    public function hasAutoVanish(string $playerName): bool {
        return in_array($playerName, $this->autoVanishPlayers);
    }
    
    /**
     * Set a player's auto-vanish status
     * 
     * @param string $playerName
     * @param bool $enabled
     */
    public function setAutoVanish(string $playerName, bool $enabled): void {
        if ($enabled) {
            if (!in_array($playerName, $this->autoVanishPlayers)) {
                $this->autoVanishPlayers[] = $playerName;
            }
        } else {
            $index = array_search($playerName, $this->autoVanishPlayers);
            if ($index !== false) {
                unset($this->autoVanishPlayers[$index]);
            }
        }
        
        $this->savePlayerSettings();
    }
    
    /**
     * Get a player's notification setting
     * 
     * @param string $playerName
     * @param string $setting
     * @param bool $default
     * @return bool
     */
    public function getPlayerNotificationSetting(string $playerName, string $setting, bool $default = true): bool {
        return $this->playerNotificationSettings[$playerName][$setting] ?? $default;
    }
    
    /**
     * Set a player's notification setting
     * 
     * @param string $playerName
     * @param string $setting
     * @param bool $value
     */
    public function setPlayerNotificationSetting(string $playerName, string $setting, bool $value): void {
        if (!isset($this->playerNotificationSettings[$playerName])) {
            $this->playerNotificationSettings[$playerName] = [];
        }
        
        $this->playerNotificationSettings[$playerName][$setting] = $value;
        $this->savePlayerSettings();
    }
    
    /**
     * Get available modes for a player
     * 
     * @param Player $player
     * @return array
     */
    public function getAvailableModes(Player $player): array {
        $availableModes = [];
        
        foreach ($this->modes as $id => $mode) {
            if ($player->hasPermission($mode->getPermission())) {
                $availableModes[$id] = $mode;
            }
        }
        
        return $availableModes;
    }
    
    /**
     * Get available groups for a player
     * 
     * @param Player $player
     * @return array
     */
    public function getAvailableGroups(Player $player): array {
        $availableGroups = [];
        
        foreach ($this->groups as $id => $group) {
            if ($player->hasPermission($group->getPermission())) {
                $availableGroups[$id] = $group;
            }
        }
        
        return $availableGroups;
    }
    
    /**
     * Check if a player can see another player based on their groups
     * 
     * @param string $viewerName
     * @param string $targetName
     * @return bool
     */
    public function canSeeVanished(string $viewerName, string $targetName): bool {
        // If the viewer has the vanish.see permission, they can see all vanished players
        $viewer = $this->plugin->getServer()->getPlayerByPrefix($viewerName);
        if ($viewer !== null && $viewer->hasPermission("vanish.see")) {
            return true;
        }
        
        // Check group visibility
        $viewerGroup = $this->getPlayerGroup($viewerName);
        $targetGroup = $this->getPlayerGroup($targetName);
        
        if (isset($this->groups[$targetGroup])) {
            return $this->groups[$targetGroup]->isVisibleTo($viewerGroup);
        }
        
        return false;
    }
    
    /**
     * Vanish a player
     * 
     * @param Player $player
     */
    public function vanishPlayer(Player $player): void {
        $playerName = $player->getName();
        
        if ($this->isVanished($playerName)) {
            return;
        }
        
        $this->addVanishedPlayer($playerName);
        
        // Get mode and group
        $mode = $this->getPlayerMode($playerName);
        $group = $this->getPlayerGroup($playerName);
        
        // Update nametag
        VanishUtils::addVanishTag($player);
        
        // Update HUD
        $this->plugin->updateHudPlayerCount();
        
        // Handle fake leave message
        if ($this->configProvider->getSetting('enable_leave')) {
            $msg = $this->configProvider->getMessage('fake_leave', ['%name' => $playerName]);
            $this->plugin->getServer()->broadcastMessage($msg);
        }
        
        // Handle flight
        if ($this->configProvider->getSetting('enable_fly') || 
            (isset($this->modes[$mode]) && $this->modes[$mode]->isEnabled('fly'))) {
            if ($player->isSurvival()) {
                $player->setFlying(true);
                $player->setAllowFlight(true);
            }
        }
        
        // Notify the player and staff
        $this->notification->notifyVanish($player, $mode, $group);
        
        // Record stats
        $this->stats->recordVanish($playerName, $mode, $group);
    }
    
    /**
     * Unvanish a player
     * 
     * @param Player $player
     */
    public function unvanishPlayer(Player $player): void {
        $playerName = $player->getName();
        
        if (!$this->isVanished($playerName)) {
            return;
        }
        
        $this->removeVanishedPlayer($playerName);
        
        // Update nametag
        VanishUtils::removeVanishTag($player);
        $player->setSilent(false);
        $player->getXpManager()->setCanAttractXpOrbs(true);
        
        // Update HUD
        $this->plugin->updateHudPlayerCount();
        
        // Show player to everyone and update player list
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->showPlayer($player);
            
            // Update player list
            $networkSession = $onlinePlayer->getNetworkSession();
            $networkSession->sendDataPacket(
                PlayerListPacket::add([
                    PlayerListEntry::createAdditionEntry(
                        $player->getUniqueId(),
                        $player->getId(),
                        $player->getDisplayName(),
                        $networkSession->getTypeConverter()->getSkinAdapter()->toSkinData($player->getSkin()),
                        $player->getXuid()
                    )
                ])
            );
        }
        
        // Handle flight
        if ($this->configProvider->getSetting('enable_fly')) {
            if ($player->isSurvival()) {
                $player->setFlying(false);
                $player->setAllowFlight(false);
            }
        }
        
        // Handle night vision
        if ($this->configProvider->getSetting('night_vision')) {
            $player->getEffects()->remove(VanillaEffects::NIGHT_VISION());
        }
        
        // Handle fake join message
        if ($this->configProvider->getSetting('enable_join')) {
            $msg = $this->configProvider->getMessage('fake_join', ['%name' => $playerName]);
            $this->plugin->getServer()->broadcastMessage($msg);
        }
        
        // Notify the player and staff
        $this->notification->notifyUnvanish($player);
        
        // Record stats
        $this->stats->recordUnvanish($playerName);
    }
    
    /**
     * Vanish another player
     * 
     * @param CommandSender $sender
     * @param Player $target
     */
    public function vanishOtherPlayer(CommandSender $sender, Player $target): void {
        $targetName = $target->getName();
        
        if ($this->isVanished($targetName)) {
            return;
        }
        
        $this->addVanishedPlayer($targetName);
        
        // Get mode and group
        $mode = $this->getPlayerMode($targetName);
        $group = $this->getPlayerGroup($targetName);
        
        // Update nametag
        VanishUtils::addVanishTag($target);
        
        // Update HUD
        $this->plugin->updateHudPlayerCount();
        
        // Handle fake leave message
        if ($this->configProvider->getSetting('enable_leave')) {
            $msg = $this->configProvider->getMessage('fake_leave', ['%name' => $targetName]);
            $this->plugin->getServer()->broadcastMessage($msg);
        }
        
        // Handle flight
        if ($this->configProvider->getSetting('enable_fly') || 
            (isset($this->modes[$mode]) && $this->modes[$mode]->isEnabled('fly'))) {
            if ($target->isSurvival()) {
                $target->setFlying(true);
                $target->setAllowFlight(true);
            }
        }
        
        // Notify the player, target, and staff
        $this->notification->notifyVanishOther($sender, $target, $mode, $group);
        
        // Record stats and log
        $this->stats->logVanishOther($sender, $target, $mode, $group);
        $this->stats->recordVanish($targetName, $mode, $group);
    }
    
    /**
     * Unvanish another player
     * 
     * @param CommandSender $sender
     * @param Player $target
     */
    public function unvanishOtherPlayer(CommandSender $sender, Player $target): void {
        $targetName = $target->getName();
        
        if (!$this->isVanished($targetName)) {
            return;
        }
        
        $this->removeVanishedPlayer($targetName);
        
        // Update nametag
        VanishUtils::removeVanishTag($target);
        $target->setSilent(false);
        $target->getXpManager()->setCanAttractXpOrbs(true);
        
        // Update HUD
        $this->plugin->updateHudPlayerCount();
        
        // Show player to everyone and update player list
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->showPlayer($target);
            
            // Update player list
            $networkSession = $onlinePlayer->getNetworkSession();
            $networkSession->sendDataPacket(
                PlayerListPacket::add([
                    PlayerListEntry::createAdditionEntry(
                        $target->getUniqueId(),
                        $target->getId(),
                        $target->getDisplayName(),
                        $networkSession->getTypeConverter()->getSkinAdapter()->toSkinData($target->getSkin()),
                        $target->getXuid()
                    )
                ])
            );
        }
        
        // Handle flight
        if ($this->configProvider->getSetting('enable_fly')) {
            if ($target->isSurvival()) {
                $target->setFlying(false);
                $target->setAllowFlight(false);
            }
        }
        
        // Handle night vision
        if ($this->configProvider->getSetting('night_vision')) {
            $target->getEffects()->remove(VanillaEffects::NIGHT_VISION());
        }
        
        // Handle fake join message
        if ($this->configProvider->getSetting('enable_join')) {
            $msg = $this->configProvider->getMessage('fake_join', ['%name' => $targetName]);
            $this->plugin->getServer()->broadcastMessage($msg);
        }
        
        // Notify the player, target, and staff
        $this->notification->notifyUnvanishOther($sender, $target);
        
        // Record stats and log
        $this->stats->logUnvanishOther($sender, $target);
        $this->stats->recordUnvanish($targetName);
    }
    
    /**
     * Save vanished players to storage
     */
    public function saveVanishedPlayers(): void {
        $this->dataProvider->saveVanishedPlayers($this->vanishedPlayers);
    }
    
    /**
     * Get the VanishStats instance
     * 
     * @return VanishStats
     */
    public function getStats(): VanishStats {
        return $this->stats;
    }
    
    /**
     * Get the VanishNotification instance
     * 
     * @return VanishNotification
     */
    public function getNotification(): VanishNotification {
        return $this->notification;
    }
}
