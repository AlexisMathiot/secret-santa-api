<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class EventCreateDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    public string $title;
}
