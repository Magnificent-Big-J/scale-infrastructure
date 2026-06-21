<?php

namespace App\Contracts;

use App\Models\Deployment;
use App\Models\SupportTicket;

interface IntakeServiceInterface
{
    /**
     * Create a support ticket from an external (token-authenticated) intake
     * request against the given deployment.
     */
    public function createTicket(Deployment $deployment, array $data): SupportTicket;
}
