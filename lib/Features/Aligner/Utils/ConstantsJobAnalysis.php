<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 09/11/2018
 * Time: 13:04
 */

namespace Features\Aligner\Utils;

class ConstantsJobAnalysis
{
    const ALIGN_PHASE_0 = 'not_started';
    const ALIGN_PHASE_1 = 'started';
    const ALIGN_PHASE_2 = 'segments_created';
    const ALIGN_PHASE_3 = 'fetching';
    const ALIGN_PHASE_4 = 'translating';
    const ALIGN_PHASE_5 = 'aligning';
    const ALIGN_PHASE_6 = 'merging';
    const ALIGN_PHASE_7 = 'complete';
    const ALIGN_PHASE_8 = 'limit_exceeded';

}