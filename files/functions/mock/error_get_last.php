<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function error_get_last()
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
