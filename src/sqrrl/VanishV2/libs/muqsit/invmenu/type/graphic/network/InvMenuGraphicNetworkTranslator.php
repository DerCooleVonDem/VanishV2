<?php

declare(strict_types=1);

namespace sqrrl\VanishV2\libs\muqsit\invmenu\type\graphic\network;

use sqrrl\VanishV2\libs\muqsit\invmenu\session\InvMenuInfo;
use sqrrl\VanishV2\libs\muqsit\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

interface InvMenuGraphicNetworkTranslator{

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void;
}