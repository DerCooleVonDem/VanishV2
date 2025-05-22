<?php

declare(strict_types=1);

namespace sqrrl\VanishV2\libs\muqsit\invmenu\session\network\handler;

use Closure;
use sqrrl\VanishV2\libs\muqsit\invmenu\session\network\NetworkStackLatencyEntry;

interface PlayerNetworkHandler{

	public function createNetworkStackLatencyEntry(Closure $then) : NetworkStackLatencyEntry;
}