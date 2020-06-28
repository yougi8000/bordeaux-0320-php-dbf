<?php


namespace App\Service;

use App\Entity\Call;
use App\Entity\CallProcessing;
use App\Entity\ContactType;
use App\Entity\RecallPeriod;

class CallTreatmentDataMaker
{
    const ICONS_AND_COLORS = [
            ContactType::ABANDON => [
                'class' => 'abandon',
                'icon'  => 'close',
                'color' =>'text-darken-3 grey-text',
                'bgColor' =>'grey darken-3',
                'stepName' => '<i class="material-icons">close</i>'
            ],
            ContactType::CONTACT => [
                'class' => 'contact',
                'icon'  => 'phone_in_talk',
                'color' =>'light-green-text',
                'bgColor' =>'light-green',
                'stepName' => '<i class="material-icons">check</i>'
            ],
            ContactType::NOT_ELIGIBLE =>[
                'class' => '',
                'icon'  => 'call_end',
                'color'  =>'grey-text',
                'bgColor' =>'grey',
                'stepName' => 'NE'
            ],
            ContactType::MSG1 => [
                'class'=> 'message',
                'icon' => 'perm_phone_msg',
                'color'=>'text-darken-1 yellow-text',
                'bgColor' =>'yellow darken-1',
                'stepName' => 'M1'

            ],
            ContactType::MSG2 => [
                'class'=> 'message',
                'icon'=> 'perm_phone_msg',
                'color'=>'text-darken-3 yellow-text',
                'bgColor' =>'yellow darken-3',
                'stepName' => 'M2'
            ],
            ContactType::MSG3 => [
                'class'=> 'message3',
                'icon'=> 'perm_phone_msg',
                'color'=>'text-darken-2 orange-text',
                'bgColor' =>'orange darken-2',
                'stepName' => 'M3'
            ],
        ];

    /**
     * @param CallProcessing $process
     * @return array
     */
    public static function stepMakerForProcess(CallProcessing $process)
    {
        $step = $process->getContactType()->getIdentifier();
        if ($step) {
            return json_decode(json_encode(self::ICONS_AND_COLORS[$step]));
        }
    }


    /**
     * @param Call $call
     * @return array
     */
    public function stepMaker(Call $call)
    {
        $data           = [];
        $treatments     = $call->getCallProcessings();
        $callSteps      = [];

        foreach ($treatments as $step) {
            $callSteps[] = $step->getContactType()->getIdentifier();
        }
        $callStepIdentifier = end($callSteps);

        foreach (self::ICONS_AND_COLORS as $iconAndColor => $values) {
            if ($callStepIdentifier === $iconAndColor) {
                $data = [
                    'class' => $values['class'],
                    'icon'  => $values['icon'],
                    'color' => $values['color']
                ];
            }
        }
        if ($call->getRecallPeriod()->getIdentifier() === RecallPeriod::URGENT) {
            $data = [
                'class' => 'emergency',
                'icon'  => 'notifications_active',
                'color' =>'red-text',
                'pulse' => 'pulse',
            ];
        }

        return $data;
    }

    /**
     * @param Call $call
     * @return mixed
     */
    public function getLastTreatment(Call $call)
    {
        $treatments     = $call->getCallProcessings();
        $callSteps      = [];
        foreach ($treatments as $step) {
            $callSteps[] = $step->getContactType()->getName();
        }
        $lastStepName = end($callSteps);
        if ($call->getRecallPeriod()->getIdentifier() === RecallPeriod::URGENT) {
            $lastStepName = RecallPeriod::URGENT;
        }
        return $lastStepName;
    }
}
