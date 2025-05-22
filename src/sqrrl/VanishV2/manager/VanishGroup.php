<?php

namespace sqrrl\VanishV2\manager;

/**
 * Class VanishGroup
 * Represents a vanish group with visibility settings
 */
class VanishGroup {
    /** @var string */
    private string $id;
    
    /** @var string */
    private string $name;
    
    /** @var string */
    private string $description;
    
    /** @var string */
    private string $permission;
    
    /** @var array */
    private array $visibleTo;
    
    /** @var array */
    private array $settings;
    
    /**
     * VanishGroup constructor
     * 
     * @param string $id
     * @param string $name
     * @param string $description
     * @param string $permission
     * @param array $visibleTo
     * @param array $settings
     */
    public function __construct(string $id, string $name, string $description, string $permission, array $visibleTo, array $settings) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->permission = $permission;
        $this->visibleTo = $visibleTo;
        $this->settings = $settings;
    }
    
    /**
     * Get the group ID
     * 
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }
    
    /**
     * Get the group name
     * 
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
    
    /**
     * Get the group description
     * 
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }
    
    /**
     * Get the permission required for this group
     * 
     * @return string
     */
    public function getPermission(): string {
        return $this->permission;
    }
    
    /**
     * Get groups that can see this group
     * 
     * @return array
     */
    public function getVisibleTo(): array {
        return $this->visibleTo;
    }
    
    /**
     * Check if this group is visible to another group
     * 
     * @param string $groupId
     * @return bool
     */
    public function isVisibleTo(string $groupId): bool {
        return in_array($groupId, $this->visibleTo) || in_array('*', $this->visibleTo);
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
     * Create a group from config data
     * 
     * @param string $id
     * @param array $data
     * @return VanishGroup
     */
    public static function fromConfig(string $id, array $data): VanishGroup {
        return new VanishGroup(
            $id,
            $data['name'] ?? $id,
            $data['description'] ?? '',
            $data['permission'] ?? "vanish.group.$id",
            $data['visible_to'] ?? [],
            $data['settings'] ?? []
        );
    }
}
