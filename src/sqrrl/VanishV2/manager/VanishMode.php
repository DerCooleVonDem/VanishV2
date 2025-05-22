<?php

namespace sqrrl\VanishV2\manager;

/**
 * Class VanishMode
 * Represents a vanish mode with specific settings
 */
class VanishMode {
    /** @var string */
    private string $id;
    
    /** @var string */
    private string $name;
    
    /** @var string */
    private string $description;
    
    /** @var string */
    private string $permission;
    
    /** @var array */
    private array $settings;
    
    /**
     * VanishMode constructor
     * 
     * @param string $id
     * @param string $name
     * @param string $description
     * @param string $permission
     * @param array $settings
     */
    public function __construct(string $id, string $name, string $description, string $permission, array $settings) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->permission = $permission;
        $this->settings = $settings;
    }
    
    /**
     * Get the mode ID
     * 
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }
    
    /**
     * Get the mode name
     * 
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
    
    /**
     * Get the mode description
     * 
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }
    
    /**
     * Get the permission required for this mode
     * 
     * @return string
     */
    public function getPermission(): string {
        return $this->permission;
    }
    
    /**
     * Get a setting value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Get all settings
     * 
     * @return array
     */
    public function getSettings(): array {
        return $this->settings;
    }
    
    /**
     * Check if a setting is enabled
     * 
     * @param string $key
     * @return bool
     */
    public function isEnabled(string $key): bool {
        return (bool)($this->settings[$key] ?? false);
    }
    
    /**
     * Create a mode from config data
     * 
     * @param string $id
     * @param array $data
     * @return VanishMode
     */
    public static function fromConfig(string $id, array $data): VanishMode {
        return new VanishMode(
            $id,
            $data['name'] ?? $id,
            $data['description'] ?? '',
            $data['permission'] ?? "vanish.mode.$id",
            $data['settings'] ?? []
        );
    }
}
