<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_get_meta_data($fp)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
