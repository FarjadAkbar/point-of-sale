<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Waiting = 'waiting';
    case Booked = 'booked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
