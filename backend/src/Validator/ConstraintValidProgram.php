<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

final class ConstraintValidProgram extends Constraint
{
	public $overlappingSpeechesMessage = 'It\'s rude to talk over other people, where are your manners.'; // I just couldn't resist writing this here since it's not in production


	public function validatedBy()
	{
		return ConstraintValidProgramValidator::class;
	}

	public function getTargets()
	{
		return self::CLASS_CONSTRAINT;
	}
}
