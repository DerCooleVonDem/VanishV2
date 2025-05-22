<?php

declare(strict_types=1);

namespace sqrrl\VanishV2\libs\muqsit\invmenu\session;

use sqrrl\VanishV2\libs\muqsit\invmenu\InvMenu;
use sqrrl\VanishV2\libs\muqsit\invmenu\type\graphic\InvMenuGraphic;

final class InvMenuInfo{

	public function __construct(
		readonly public InvMenu $menu,
		readonly public InvMenuGraphic $graphic,
		readonly public ?string $graphic_name
	){}
}