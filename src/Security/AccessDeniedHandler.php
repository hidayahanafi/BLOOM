<?php
// src/Security/AccessDeniedHandler.php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Get the referer URL; if not available, fallback to home ('/')
        $referer = $request->headers->get('referer') ?: '/';
        return new RedirectResponse($referer);
    }
}
