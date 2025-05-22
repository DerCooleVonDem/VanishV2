<?php

namespace sqrrl\VanishV2\provider;

use pocketmine\utils\Config;
use sqrrl\VanishV2\VanishV2;

/**
 * Class DataProvider
 * Manages data storage for the VanishV2 plugin
 */
class DataProvider {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /**
     * DataProvider constructor
     * 
     * @param VanishV2 $plugin
     * @param ConfigProvider $configProvider
     */
    public function __construct(VanishV2 $plugin, ConfigProvider $configProvider) {
        $this->plugin = $plugin;
        $this->configProvider = $configProvider;
    }
    
    /**
     * Load vanished players from storage
     * 
     * @return array
     */
    public function loadVanishedPlayers(): array {
        if ($this->configProvider->getSetting('unvanish_after_restart')) {
            return [];
        }
        
        $filePath = $this->plugin->getDataFolder() . "vanished_players.txt";
        if (!file_exists($filePath)) {
            return [];
        }
        
        $file = new Config($filePath, Config::ENUM);
        $players = $file->getAll(true);
        
        // Clean up the file after loading
        @unlink($filePath);
        
        return $players;
    }
    
    /**
     * Save vanished players to storage
     * 
     * @param array $players
     */
    public function saveVanishedPlayers(array $players): void {
        if ($this->configProvider->getSetting('unvanish_after_restart')) {
            return;
        }
        
        $file = new Config($this->plugin->getDataFolder() . "vanished_players.txt", Config::ENUM);
        $playersStr = implode("\n", $players);
        $file->set($playersStr);
        $file->save();
    }
    
    /**
     * Load player settings from storage
     * 
     * @return array
     */
    public function loadPlayerSettings(): array {
        $filePath = $this->plugin->getDataFolder() . "player_settings.json";
        if (!file_exists($filePath)) {
            return [];
        }
        
        $file = new Config($filePath, Config::JSON);
        return $file->getAll();
    }
    
    /**
     * Save player settings to storage
     * 
     * @param array $settings
     */
    public function savePlayerSettings(array $settings): void {
        $file = new Config($this->plugin->getDataFolder() . "player_settings.json", Config::JSON);
        $file->setAll($settings);
        $file->save();
    }
    
    /**
     * Load vanish modes from storage
     * 
     * @return array
     */
    public function loadVanishModes(): array {
        $filePath = $this->plugin->getDataFolder() . "modes.json";
        if (!file_exists($filePath)) {
            return [];
        }
        
        $file = new Config($filePath, Config::JSON);
        return $file->getAll();
    }
    
    /**
     * Save vanish modes to storage
     * 
     * @param array $modes
     */
    public function saveVanishModes(array $modes): void {
        $file = new Config($this->plugin->getDataFolder() . "modes.json", Config::JSON);
        $file->setAll($modes);
        $file->save();
    }
    
    /**
     * Load vanish groups from storage
     * 
     * @return array
     */
    public function loadVanishGroups(): array {
        $filePath = $this->plugin->getDataFolder() . "groups.json";
        if (!file_exists($filePath)) {
            return [];
        }
        
        $file = new Config($filePath, Config::JSON);
        return $file->getAll();
    }
    
    /**
     * Save vanish groups to storage
     * 
     * @param array $groups
     */
    public function saveVanishGroups(array $groups): void {
        $file = new Config($this->plugin->getDataFolder() . "groups.json", Config::JSON);
        $file->setAll($groups);
        $file->save();
    }
}
