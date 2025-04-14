<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class CustomEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $data = [
            'code' => 401,
            'message' => 'You are not authorized to access this resource.' 
        ];
        return new Response(json_encode($data), 401, ['Content-Type' => 'application/json']);
    }
}
