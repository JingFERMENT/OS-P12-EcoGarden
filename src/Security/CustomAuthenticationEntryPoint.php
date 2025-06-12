<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;


class CustomAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'code' => 401,
            'message' => 'Veuillez vous authentifier pour continuer.'
        ], 401);
    }
}
