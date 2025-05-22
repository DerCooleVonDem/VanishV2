<?php

namespace sqrrl\VanishV2\form;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use sqrrl\VanishV2\libs\jojoe77777\FormAPI\CustomForm;
use sqrrl\VanishV2\libs\jojoe77777\FormAPI\SimpleForm;
use sqrrl\VanishV2\VanishV2;
use sqrrl\VanishV2\manager\VanishManager;
use sqrrl\VanishV2\provider\ConfigProvider;

/**
 * Class VanishLogsForm
 * Shows vanish logs
 */
class VanishLogsForm {
    /** @var VanishV2 */
    private VanishV2 $plugin;

    /** @var VanishManager */
    private VanishManager $vanishManager;

    /** @var ConfigProvider */
    private ConfigProvider $configProvider;

    /**
     * VanishLogsForm constructor
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
     * Send the logs form to a player
     *
     * @param Player|string $player
     */
    public function sendTo($player): void {
        // Make sure we have a Player object
        if (!($player instanceof Player)) {
            $playerObj = $this->plugin->getServer()->getPlayerByPrefix($player);
            if ($playerObj === null) {
                return; // Can't proceed without a valid player
            }
            $player = $playerObj;
        }
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null) {
                // Player closed the form
                $mainForm = new VanishMainForm($this->plugin, $this->vanishManager, $this->configProvider);
                $mainForm->sendTo($player);
                return;
            }

            switch ($data) {
                case 0: // Back
                    $mainForm = new VanishMainForm($this->plugin, $this->vanishManager, $this->configProvider);
                    $mainForm->sendTo($player);
                    break;

                case 1: // Recent Logs
                    // Make sure we have a Player object
                    if ($player instanceof Player) {
                        $this->showRecentLogsForm($player);
                    } else {
                        // Try to get the player by name if it's a string
                        $playerObj = $this->plugin->getServer()->getPlayerByPrefix($player);
                        if ($playerObj !== null) {
                            $this->showRecentLogsForm($playerObj);
                        }
                    }
                    break;

                case 2: // Search Logs
                    // Make sure we have a Player object
                    if ($player instanceof Player) {
                        $this->showSearchLogsForm($player);
                    } else {
                        // Try to get the player by name if it's a string
                        $playerObj = $this->plugin->getServer()->getPlayerByPrefix($player);
                        if ($playerObj !== null) {
                            $this->showSearchLogsForm($playerObj);
                        }
                    }
                    break;

                case 3: // Player Logs
                    // Make sure we have a Player object
                    if ($player instanceof Player) {
                        $this->showPlayerLogsForm($player);
                    } else {
                        // Try to get the player by name if it's a string
                        $playerObj = $this->plugin->getServer()->getPlayerByPrefix($player);
                        if ($playerObj !== null) {
                            $this->showPlayerLogsForm($playerObj);
                        }
                    }
                    break;

                case 4: // Action Logs
                    // Make sure we have a Player object
                    if ($player instanceof Player) {
                        $this->showActionLogsForm($player);
                    } else {
                        // Try to get the player by name if it's a string
                        $playerObj = $this->plugin->getServer()->getPlayerByPrefix($player);
                        if ($playerObj !== null) {
                            $this->showActionLogsForm($playerObj);
                        }
                    }
                    break;
            }
        });

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Logs");
        $form->setContent(TextFormat::YELLOW . "Select a category to view logs:");

        $form->addButton(TextFormat::RED . "Back");
        $form->addButton(TextFormat::AQUA . "Recent Logs");
        $form->addButton(TextFormat::GREEN . "Search Logs");
        $form->addButton(TextFormat::GOLD . "Player Logs");
        $form->addButton(TextFormat::LIGHT_PURPLE . "Action Logs");

        $player->sendForm($form);
    }

    /**
     * Show recent logs form
     *
     * @param Player $player
     */
    private function showRecentLogsForm(Player $pPlayer): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->sendTo($player);
                return;
            }
        });

        $recentLogs = $this->plugin->getVanishStats()->getRecentLogs(20);
        $recentLogs = array_reverse($recentLogs);

        // Format logs
        $logsText = "";
        foreach ($recentLogs as $log) {
            $time = date('Y-m-d H:i:s', $log['time']);
            $action = $this->formatAction($log['action']);
            $player = $log['player'];
            $target = $log['target'] ?? '';
            $mode = $log['mode'] ?? '';
            $group = $log['group'] ?? '';

            $logsText .= TextFormat::GRAY . "[" . $time . "] " .
                        TextFormat::YELLOW . $player . " " .
                        TextFormat::AQUA . $action;

            if (!empty($target)) {
                $logsText .= " " . TextFormat::YELLOW . $target;
            }

            if (!empty($mode)) {
                $logsText .= TextFormat::GRAY . " (Mode: " . TextFormat::AQUA . $mode . TextFormat::GRAY . ")";
            }

            if (!empty($group)) {
                $logsText .= TextFormat::GRAY . " (Group: " . TextFormat::YELLOW . $group . TextFormat::GRAY . ")";
            }

            $logsText .= "\n";
        }

        if (empty($logsText)) {
            $logsText = TextFormat::GRAY . "No logs available";
        }

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Recent Logs");
        $form->setContent(
            TextFormat::GOLD . "Recent Logs (newest first):\n\n" . $logsText
        );

        $form->addButton(TextFormat::RED . "Back");

        $pPlayer->sendForm($form);
    }

    /**
     * Show search logs form
     *
     * @param Player $player
     */
    private function showSearchLogsForm(Player $player): void {
        $form = new CustomForm(function(Player $player, ?array $data) {
            if ($data === null) {
                // Player closed the form
                $this->sendTo($player);
                return;
            }

            $searchTerm = $data[1] ?? '';

            if (empty($searchTerm)) {
                $player->sendMessage(VanishV2::PREFIX . TextFormat::RED . "Please enter a search term.");
                $this->showSearchLogsForm($player);
                return;
            }

            $this->showSearchResultsForm($player, $searchTerm);
        });

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Search Logs");
        $form->addLabel(TextFormat::YELLOW . "Enter a search term to find in the logs.\n" .
                       TextFormat::GRAY . "This will search player names, actions, modes, and groups.");
        $form->addInput(TextFormat::AQUA . "Search Term", "Enter search term...");

        $player->sendForm($form);
    }

    /**
     * Show search results form
     *
     * @param Player $player
     * @param string $searchTerm
     */
    private function showSearchResultsForm(Player $pPlayer, string $searchTerm): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->showSearchLogsForm($player);
                return;
            }
        });

        $logs = $this->plugin->getVanishStats()->getLogs();

        // Filter logs by search term
        $filteredLogs = array_filter($logs, function($log) use ($searchTerm) {
            return (
                stripos($log['player'], $searchTerm) !== false ||
                (isset($log['target']) && stripos($log['target'], $searchTerm) !== false) ||
                stripos($log['action'], $searchTerm) !== false ||
                (isset($log['mode']) && stripos($log['mode'], $searchTerm) !== false) ||
                (isset($log['group']) && stripos($log['group'], $searchTerm) !== false)
            );
        });

        $filteredLogs = array_reverse($filteredLogs);

        // Format logs
        $logsText = "";
        foreach ($filteredLogs as $log) {
            $time = date('Y-m-d H:i:s', $log['time']);
            $action = $this->formatAction($log['action']);
            $player = $log['player'];
            $target = $log['target'] ?? '';
            $mode = $log['mode'] ?? '';
            $group = $log['group'] ?? '';

            $logsText .= TextFormat::GRAY . "[" . $time . "] " .
                        TextFormat::YELLOW . $player . " " .
                        TextFormat::AQUA . $action;

            if (!empty($target)) {
                $logsText .= " " . TextFormat::YELLOW . $target;
            }

            if (!empty($mode)) {
                $logsText .= TextFormat::GRAY . " (Mode: " . TextFormat::AQUA . $mode . TextFormat::GRAY . ")";
            }

            if (!empty($group)) {
                $logsText .= TextFormat::GRAY . " (Group: " . TextFormat::YELLOW . $group . TextFormat::GRAY . ")";
            }

            $logsText .= "\n";
        }

        if (empty($logsText)) {
            $logsText = TextFormat::GRAY . "No logs found matching '" . $searchTerm . "'";
        }

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Search Results");
        $form->setContent(
            TextFormat::GOLD . "Search Results for '" . $searchTerm . "':\n\n" . $logsText
        );

        $form->addButton(TextFormat::RED . "Back");

        $pPlayer->sendForm($form);
    }

    /**
     * Show player logs form
     *
     * @param Player $player
     */
    private function showPlayerLogsForm(Player $player): void {
        $form = new CustomForm(function(Player $player, ?array $data) {
            if ($data === null) {
                // Player closed the form
                $this->sendTo($player);
                return;
            }

            $playerName = $data[1] ?? '';

            if (empty($playerName)) {
                $player->sendMessage(VanishV2::PREFIX . TextFormat::RED . "Please enter a player name.");
                $this->showPlayerLogsForm($player);
                return;
            }

            $this->showPlayerLogsResultsForm($player, $playerName);
        });

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Player Logs");
        $form->addLabel(TextFormat::YELLOW . "Enter a player name to view their logs.\n" .
                       TextFormat::GRAY . "This will show all logs related to the player.");
        $form->addInput(TextFormat::AQUA . "Player Name", "Enter player name...");

        $player->sendForm($form);
    }

    /**
     * Show player logs results form
     *
     * @param Player $player
     * @param string $playerName
     */
    private function showPlayerLogsResultsForm(Player $player, string $playerName): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->showPlayerLogsForm($player);
                return;
            }
        });

        $playerLogs = $this->plugin->getVanishStats()->getPlayerLogs($playerName);
        $playerLogs = array_reverse($playerLogs);

        // Format logs
        $logsText = "";
        foreach ($playerLogs as $log) {
            $time = date('Y-m-d H:i:s', $log['time']);
            $action = $this->formatAction($log['action']);
            $logPlayer = $log['player'];
            $target = $log['target'] ?? '';
            $mode = $log['mode'] ?? '';
            $group = $log['group'] ?? '';

            $logsText .= TextFormat::GRAY . "[" . $time . "] ";

            if ($logPlayer === $playerName) {
                $logsText .= TextFormat::YELLOW . $logPlayer . " " . TextFormat::AQUA . $action;

                if (!empty($target)) {
                    $logsText .= " " . TextFormat::YELLOW . $target;
                }
            } else {
                $logsText .= TextFormat::YELLOW . $logPlayer . " " . TextFormat::AQUA . $action . " " .
                            TextFormat::YELLOW . $playerName;
            }

            if (!empty($mode)) {
                $logsText .= TextFormat::GRAY . " (Mode: " . TextFormat::AQUA . $mode . TextFormat::GRAY . ")";
            }

            if (!empty($group)) {
                $logsText .= TextFormat::GRAY . " (Group: " . TextFormat::YELLOW . $group . TextFormat::GRAY . ")";
            }

            $logsText .= "\n";
        }

        if (empty($logsText)) {
            $logsText = TextFormat::GRAY . "No logs found for player '" . $playerName . "'";
        }

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Player Logs");
        $form->setContent(
            TextFormat::GOLD . "Logs for Player '" . $playerName . "':\n\n" . $logsText
        );

        $form->addButton(TextFormat::RED . "Back");

        $player->sendForm($form);
    }

    /**
     * Show action logs form
     *
     * @param Player $player
     */
    private function showActionLogsForm(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form
                $this->sendTo($player);
                return;
            }

            $actions = ['vanish', 'unvanish', 'vanish_other', 'unvanish_other', 'mode_change', 'group_change'];
            $selectedAction = $actions[$data - 1] ?? null;

            if ($selectedAction !== null) {
                $this->showActionLogsResultsForm($player, $selectedAction);
            }
        });

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Action Logs");
        $form->setContent(TextFormat::YELLOW . "Select an action to view logs for:");

        $form->addButton(TextFormat::RED . "Back");
        $form->addButton(TextFormat::GREEN . "Vanish");
        $form->addButton(TextFormat::RED . "Unvanish");
        $form->addButton(TextFormat::GREEN . "Vanish Other");
        $form->addButton(TextFormat::RED . "Unvanish Other");
        $form->addButton(TextFormat::AQUA . "Mode Change");
        $form->addButton(TextFormat::YELLOW . "Group Change");

        $player->sendForm($form);
    }

    /**
     * Show action logs results form
     *
     * @param Player $player
     * @param string $action
     */
    private function showActionLogsResultsForm(Player $player, string $action): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null || $data === 0) {
                // Player closed the form or clicked back
                $this->showActionLogsForm($player);
                return;
            }
        });

        $actionLogs = $this->plugin->getVanishStats()->getActionLogs($action);
        $actionLogs = array_reverse($actionLogs);

        // Format logs
        $logsText = "";
        foreach ($actionLogs as $log) {
            $time = date('Y-m-d H:i:s', $log['time']);
            $formattedAction = $this->formatAction($log['action']);
            $logPlayer = $log['player'];
            $target = $log['target'] ?? '';
            $mode = $log['mode'] ?? '';
            $group = $log['group'] ?? '';

            $logsText .= TextFormat::GRAY . "[" . $time . "] " .
                        TextFormat::YELLOW . $logPlayer . " " .
                        TextFormat::AQUA . $formattedAction;

            if (!empty($target)) {
                $logsText .= " " . TextFormat::YELLOW . $target;
            }

            if (!empty($mode)) {
                $logsText .= TextFormat::GRAY . " (Mode: " . TextFormat::AQUA . $mode . TextFormat::GRAY . ")";
            }

            if (!empty($group)) {
                $logsText .= TextFormat::GRAY . " (Group: " . TextFormat::YELLOW . $group . TextFormat::GRAY . ")";
            }

            $logsText .= "\n";
        }

        if (empty($logsText)) {
            $logsText = TextFormat::GRAY . "No logs found for action '" . $this->formatAction($action) . "'";
        }

        $form->setTitle(TextFormat::DARK_BLUE . "VanishV2 " . TextFormat::DARK_GRAY . "Action Logs");
        $form->setContent(
            TextFormat::GOLD . "Logs for Action '" . $this->formatAction($action) . "':\n\n" . $logsText
        );

        $form->addButton(TextFormat::RED . "Back");

        $player->sendForm($form);
    }

    /**
     * Format action for display
     *
     * @param string $action
     * @return string
     */
    private function formatAction(string $action): string {
        switch ($action) {
            case 'vanish':
                return "vanished";
            case 'unvanish':
                return "unvanished";
            case 'vanish_other':
                return "vanished";
            case 'unvanish_other':
                return "unvanished";
            case 'mode_change':
                return "changed mode to";
            case 'group_change':
                return "changed group to";
            default:
                return $action;
        }
    }
}
