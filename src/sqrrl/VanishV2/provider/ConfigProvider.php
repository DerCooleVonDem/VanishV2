<?php

namespace sqrrl\VanishV2\provider;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\VanishV2;

/**
 * Class ConfigProvider
 * Manages configuration for the VanishV2 plugin
 */
class ConfigProvider {
    /** @var VanishV2 */
    private VanishV2 $plugin;
    
    /** @var Config */
    private Config $config;
    
    /** @var array */
    private array $messages = [];
    
    /** @var array */
    private array $settings = [];
    
    /** @var array */
    private array $silentJoinLeave = [];
    
    /** @var array */
    private array $additionalCommands = [];
    
    /**
     * ConfigProvider constructor
     * 
     * @param VanishV2 $plugin
     */
    public function __construct(VanishV2 $plugin) {
        $this->plugin = $plugin;
        $this->initConfig();
        $this->loadConfig();
    }
    
    /**
     * Initialize the configuration
     */
    private function initConfig(): void {
        @mkdir($this->plugin->getDataFolder());
        $this->plugin->saveDefaultConfig();
        
        // Check if config needs updating
        if ($this->plugin->getConfig()->get("config-version") < 8 || $this->plugin->getConfig()->get("config-version") === null) {
            $this->plugin->getLogger()->notice("Updating your config...");
            rename($this->plugin->getDataFolder() . "config.yml", $this->plugin->getDataFolder() . "config.yml.old");
            $this->plugin->saveDefaultConfig();
            $this->plugin->getConfig()->reload();
            $this->plugin->getLogger()->notice("Config updated!");
        }
        
        $this->config = $this->plugin->getConfig();
    }
    
    /**
     * Load configuration values into memory
     */
    private function loadConfig(): void {
        // Load messages
        $this->messages = [
            'vanish' => $this->config->get("vanish-message", "§aYou are now vanished."),
            'unvanish' => $this->config->get("unvanish-message", "§cYou are no longer vanished!"),
            'vanish_other' => $this->config->get("vanish-other", "§aVanished %name"),
            'unvanish_other' => $this->config->get("unvanish-other", "§cUnvanished %name"),
            'vanished_other' => $this->config->get("vanished-other", "§aYou are now vanished."),
            'unvanished_other' => $this->config->get("unvanished-other", "§cYou are no longer vanished!"),
            'hit_no_permission' => $this->config->get("hit-no-permission", "§cYou do not have permission to hit other players while vanished"),
            'hud_message' => $this->config->get("hud-message", "§aYou are currently vanished"),
            'fake_leave' => $this->config->get("FakeLeave-message", "§e%name left the game"),
            'fake_join' => $this->config->get("FakeJoin-message", "§e%name joined the game"),
            'vanish_notify' => $this->config->get("vanish", "§7§o[%name: Vanished]"),
            'unvanish_notify' => $this->config->get("unvanish", "§7§o[%name: Unvanished]"),
            'player_not_found' => TextFormat::RED . "Player not found",
            'no_permission_other' => TextFormat::RED . "You do not have permission to vanish other players",
            'in_game_only' => TextFormat::RED . "Use this command In-Game",
            'chest_empty' => TextFormat::RED . "This chest is empty"
        ];
        
        // Load settings
        $this->settings = [
            'enable_leave' => (bool)$this->config->get("enable-leave", false),
            'enable_join' => (bool)$this->config->get("enable-join", false),
            'unvanish_after_leaving' => (bool)$this->config->get("unvanish-after-leaving", false),
            'unvanish_after_restart' => (bool)$this->config->get("unvanish-after-restart", false),
            'enable_fly' => (bool)$this->config->get("enable-fly", true),
            'disable_damage' => (bool)$this->config->get("disable-damage", true),
            'silent_chest' => (bool)$this->config->get("silent-chest", true),
            'hunger' => (bool)$this->config->get("hunger", false),
            'night_vision' => (bool)$this->config->get("night-vision", true),
            'can_send_msg' => (bool)$this->config->get("can-send-msg", false)
        ];
        
        // Load silent join/leave settings
        $this->silentJoinLeave = $this->config->get("silent-join-leave", [
            'join' => true,
            'leave' => true,
            'vanished-only' => true
        ]);
        
        // Load additional commands
        $this->additionalCommands = $this->config->get("additional-commands", []);
    }
    
    /**
     * Get a message from the config
     * 
     * @param string $key
     * @param array $replacements Key-value pairs for replacements
     * @return string
     */
    public function getMessage(string $key, array $replacements = []): string {
        $message = $this->messages[$key] ?? "Message not found: $key";
        
        foreach ($replacements as $placeholder => $value) {
            $message = str_replace($placeholder, $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Get a setting from the config
     * 
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $key) {
        return $this->settings[$key] ?? null;
    }
    
    /**
     * Get silent join/leave settings
     * 
     * @return array
     */
    public function getSilentJoinLeave(): array {
        return $this->silentJoinLeave;
    }
    
    /**
     * Get additional commands configuration
     * 
     * @return array
     */
    public function getAdditionalCommands(): array {
        return $this->additionalCommands;
    }
    
    /**
     * Check if a command is in the additional commands list
     * 
     * @param string $command
     * @return bool
     */
    public function isAdditionalCommand(string $command): bool {
        return array_key_exists(strtolower($command), $this->additionalCommands);
    }
    
    /**
     * Get the raw config object
     * 
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }
}
