<?php

namespace sqrrl\VanishV2;

use MohamadRZ4\Placeholder\PlaceholderAPI;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use Ifera\ScoreHud\event\PlayerTagUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use sqrrl\VanishV2\api\VanishAPI;
use sqrrl\VanishV2\api\VanishExpansion;
use sqrrl\VanishV2\command\VanishCommand;
use sqrrl\VanishV2\command\VanishFormCommand;
use sqrrl\VanishV2\command\VanishListCommand;
use sqrrl\VanishV2\command\VanishStatsCommand;
use sqrrl\VanishV2\event\BlockEventListener;
use sqrrl\VanishV2\event\EntityEventListener;
use sqrrl\VanishV2\event\PlayerEventListener;
use sqrrl\VanishV2\event\ScoreHudEventListener;
use sqrrl\VanishV2\event\ServerEventListener;
use sqrrl\VanishV2\libs\muqsit\invmenu\InvMenuHandler;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;
use sqrrl\VanishV2\provider\DataProvider;
use sqrrl\VanishV2\task\VanishTask;
use sqrrl\VanishV2\util\VanishNotification;
use sqrrl\VanishV2\util\VanishStats;

/**
 * Class VanishV2
 * Main plugin class for the VanishV2 plugin
 */
class VanishV2 extends PluginBase {
    /** @var string Plugin prefix for messages */
    public const PREFIX = TextFormat::BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "» " . TextFormat::RESET;
    
    /** @var ConfigProvider */
    private ConfigProvider $configProvider;
    
    /** @var DataProvider */
    private DataProvider $dataProvider;
    
    /** @var VanishManager */
    private VanishManager $vanishManager;
    
    /** @var VanishStats */
    private VanishStats $vanishStats;
    
    /** @var VanishNotification */
    private VanishNotification $vanishNotification;
    
    /** @var array Legacy static arrays for backward compatibility */
    public static array $vanish = [];
    public static array $online = [];

    /**
     * Called when the plugin is enabled
     */
    protected function onEnable(): void {
        // Initialize providers
        $this->configProvider = new ConfigProvider($this);
        $this->dataProvider = new DataProvider($this, $this->configProvider);
        
        // Initialize utilities
        $this->vanishStats = new VanishStats($this);
        $this->vanishNotification = new VanishNotification($this, $this->configProvider);
        
        // Initialize manager
        $this->vanishManager = new VanishManager($this, $this->configProvider, $this->dataProvider);
        
        // Initialize API
        VanishAPI::init($this->vanishManager);
        
        // Register commands
        $this->registerCommands();
        
        // Schedule the repeating task
        $this->getScheduler()->scheduleRepeatingTask(new VanishTask($this->vanishManager, $this->configProvider), 20);
        
        // Register event listeners
        $this->registerEventListeners();
        
        // Initialize libraries
        $this->initLibraries();
        
        // Register PlaceholderAPI expansion if available
        $this->registerPlaceholderExpansion();
        
        // Log startup message
        $this->getLogger()->info(TextFormat::GREEN . "VanishV2 has been enabled!");
    }

    /**
     * Called when the plugin is disabled
     */
    protected function onDisable(): void {
        // Save vanished players
        $this->vanishManager->saveVanishedPlayers();
        
        // Log shutdown message
        $this->getLogger()->info(TextFormat::RED . "VanishV2 has been disabled!");
    }
    
    /**
     * Register all commands
     */
    private function registerCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        
        // Register main vanish command
        $commandMap->register("vanishv2", new VanishCommand($this, $this->vanishManager, $this->configProvider));
        
        // Register form command
        $commandMap->register("vanishv2", new VanishFormCommand($this, $this->vanishManager, $this->configProvider));
        
        // Register list command
        $commandMap->register("vanishv2", new VanishListCommand($this, $this->vanishManager, $this->configProvider));
        
        // Register stats command
        $commandMap->register("vanishv2", new VanishStatsCommand($this, $this->vanishManager, $this->configProvider));
    }
    
    /**
     * Register all event listeners
     */
    private function registerEventListeners(): void {
        $pluginManager = $this->getServer()->getPluginManager();
        
        // Register player events
        $pluginManager->registerEvents(new PlayerEventListener($this, $this->vanishManager, $this->configProvider), $this);
        
        // Register entity events
        $pluginManager->registerEvents(new EntityEventListener($this, $this->vanishManager, $this->configProvider), $this);
        
        // Register block events
        $pluginManager->registerEvents(new BlockEventListener($this, $this->vanishManager, $this->configProvider), $this);
        
        // Register server events
        $pluginManager->registerEvents(new ServerEventListener($this, $this->vanishManager, $this->configProvider), $this);
        
        // Register ScoreHud events if available
        $this->registerScoreHudListener();
    }
    
    /**
     * Register ScoreHud listener if available
     */
    private function registerScoreHudListener(): void {
        if ($this->getServer()->getPluginManager()->getPlugin("ScoreHud")) {
            if (version_compare($this->getServer()->getPluginManager()->getPlugin("ScoreHud")->getDescription()->getVersion(), "6.0.0", ">=")) {
                $this->getServer()->getPluginManager()->registerEvents(
                    new ScoreHudEventListener($this->vanishManager), 
                    $this
                );
            }
        }
    }
    
    /**
     * Register PlaceholderAPI expansion if available
     */
    private function registerPlaceholderExpansion(): void {
        if ($this->getServer()->getPluginManager()->getPlugin("PlaceholderAPI") !== null) {
            PlaceholderAPI::getInstance()->registerExpansion(new VanishExpansion($this->vanishManager));
        }
    }

    /**
     * Initialize required libraries
     */
    private function initLibraries(): void {
        if (class_exists(InvMenuHandler::class)) {
            if (!InvMenuHandler::isRegistered()) {
                InvMenuHandler::register($this);
            }
        } else {
            $this->getLogger()->error("InvMenu virion not found. Download VanishV2 on Poggit or download InvMenu with DEVirion (not recommended)");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    /**
     * Update the player count in ScoreHud
     */
    public function updateHudPlayerCount(): void {
        if (!$this->getServer()->getPluginManager()->getPlugin("ScoreHud")) {
            return;
        }
        
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            if (!$player->isOnline()) {
                continue;
            }
            
            if (!$player->hasPermission("vanish.see")) {
                $ev = new PlayerTagUpdateEvent($player, new ScoreTag("VanishV2.fake_count", 
                    strval(count($this->vanishManager->getOnlinePlayers()))));
            } else {
                $ev = new PlayerTagUpdateEvent($player, new ScoreTag("VanishV2.fake_count", 
                    strval(count($this->getServer()->getOnlinePlayers()))));
            }
            
            $ev->call();
        }
    }
    
    /**
     * Get the VanishManager instance
     * 
     * @return VanishManager
     */
    public function getVanishManager(): VanishManager {
        return $this->vanishManager;
    }
    
    /**
     * Get the ConfigProvider instance
     * 
     * @return ConfigProvider
     */
    public function getConfigProvider(): ConfigProvider {
        return $this->configProvider;
    }
    
    /**
     * Get the DataProvider instance
     * 
     * @return DataProvider
     */
    public function getDataProvider(): DataProvider {
        return $this->dataProvider;
    }
    
    /**
     * Get the VanishStats instance
     * 
     * @return VanishStats
     */
    public function getVanishStats(): VanishStats {
        return $this->vanishStats;
    }
    
    /**
     * Get the VanishNotification instance
     * 
     * @return VanishNotification
     */
    public function getVanishNotification(): VanishNotification {
        return $this->vanishNotification;
    }
}
