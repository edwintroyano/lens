<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stat($filename)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
