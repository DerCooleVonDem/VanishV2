<?php

declare(strict_types=1);

namespace sqrrl\VanishV2\libs\muqsit\invmenu\type;

use sqrrl\VanishV2\libs\muqsit\invmenu\InvMenu;
use sqrrl\VanishV2\libs\muqsit\invmenu\type\graphic\InvMenuGraphic;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

interface InvMenuType{

	public function createGraphic(InvMenu $menu, Player $player) : ?InvMenuGraphic;

	public function createInventory() : Inventory;
}