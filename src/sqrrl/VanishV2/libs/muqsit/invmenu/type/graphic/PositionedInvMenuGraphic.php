<?php

declare(strict_types=1);

namespace sqrrl\VanishV2\libs\muqsit\invmenu\type\graphic;

use pocketmine\math\Vector3;

interface PositionedInvMenuGraphic extends InvMenuGraphic{

	public function getPosition() : Vector3;
}