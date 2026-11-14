<?php

/*
|--------------------------------------------------------------------------
| Validation message overrides
|--------------------------------------------------------------------------
| Merged on top of Laravel's built-in English messages, so only the
| entries below are customised. Rules live in App\Support\InputRules.
*/

$nameMsg  = 'The :attribute may only contain letters, spaces, periods, apostrophes and hyphens.';
$phoneMsg = 'Enter a valid phone number (digits only, optionally starting with +), e.g. 09171234567.';
$refMsg   = 'The :attribute may only contain letters, numbers, dashes, slashes and spaces.';
$timeMsg  = 'Enter a valid time, e.g. 08:30 or 2:00 PM.';

return [
    'not_regex' => 'The :attribute must not contain HTML tags.',

    'custom' => [
        'name'             => ['regex' => $nameMsg],
        'fullname'         => ['regex' => $nameMsg],
        'tenant_name'      => ['regex' => $nameMsg],
        'emergency_name'   => ['regex' => $nameMsg],
        'assigned_staff'   => ['regex' => $nameMsg],

        'phone'            => ['regex' => $phoneMsg],
        'contact_number'   => ['regex' => $phoneMsg],
        'emergency_number' => ['regex' => $phoneMsg],

        'reference_no'     => ['regex' => $refMsg],
        'reference_number' => ['regex' => $refMsg],

        'check_in_time'    => ['regex' => $timeMsg],
        'check_out_time'   => ['regex' => $timeMsg],

        'code'             => ['digits' => 'The verification code must be exactly 6 digits.'],
    ],

    'attributes' => [
        'fullname'         => 'full name',
        'contact_number'   => 'contact number',
        'emergency_name'   => 'emergency contact name',
        'emergency_number' => 'emergency contact number',
        'reference_no'     => 'reference number',
        'reference_number' => 'reference number',
        'admin_notes'      => 'admin notes',
        'request_details'  => 'request details',
        'companion_names'  => 'companion names',
        'current_address'  => 'current address',
        'attachments.*'    => 'attachment',
    ],
];
