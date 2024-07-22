<?php

declare(strict_types=1);

namespace App\Validator;

use App\Document\Event;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ConstraintValidProgramValidator extends ConstraintValidator
{
	public function __construct()
	{
	}

	public function validate($value, Constraint $constraint)
	{
		if (!$constraint instanceof ConstraintValidProgram) {
			throw new UnexpectedTypeException($constraint, ConstraintValidProgram::class);
		}

		if (!$value instanceof Event) {
			throw new UnexpectedValueException($value, Event::class);
		}

		$speeches = $value->getSpeeches();
		// This algorithm does a check-loop(foreach) inside a simple for loop:
		// The endTimes array is initialized with the first speech endTime since we need at least 2 speeches to compare.
		// It loops over each speech and checks for overlaps between the speech startTime with a building array of endTimes. 
		// If none are found, the endTime of the speech is added to the endTimes array.
		// This approach minimizes the number of steps for going through the whole dataset versus just checking every start and end time for each speech.

        $endTimes = [$speeches[0]->getEndTime()];
        for ($i = 1; $i < count($speeches); $i++) {
            $currentSpeech = $speeches[$i];
            $startTime = $currentSpeech->getStartTime();

            foreach ($endTimes as $PriorEndTime) {
                if ($PriorEndTime > $startTime) { // Speech overlap condition
                    $this->context
                        ->buildViolation($constraint->overlappingSpeechesMessage)
                        ->addViolation();
                    return;
                }
            }
            $endTimes[] = $currentSpeech->getEndTime(); // Add currentEndTime to array
        }
		$this->context
			->buildViolation($constraint->overlappingSpeechesMessage)
			->addViolation();
	}
}
