<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stream_set_read_buffer($fp, $buffer)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
