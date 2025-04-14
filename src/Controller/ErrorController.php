<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ErrorController extends AbstractController
{
    #[Route("error",  name: "error_page")]
    public function show(Request $request, \Throwable $exception): Response
    {
        $isApiRequest = strpos($request->getRequestUri(), '/api') === 0;
        $isUpload = strpos($request->getRequestUri(), '/uploads') === 0;

        if ($isApiRequest) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage()
                // 'message' => 'The requested resource could not be found.'
            ], $this->getStatusCode($exception));
        }

        // For non-API requests, render the appropriate Twig template based on the exception type
        if (!$isUpload && $exception instanceof NotFoundHttpException) {
            // Render custom 404 error page
            return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [
                'message' => 'Oops! The page you are looking for does not exist.'
            ], new Response('', Response::HTTP_NOT_FOUND));
        }

        // Render a generic error page for other exceptions
        if (!$isUpload) {
            return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [
                'message' => 'An unexpected error occurred. Please try again later.'
            ], new Response('', Response::HTTP_INTERNAL_SERVER_ERROR));
        } else {
            // Default error response for non-API routes
            return new JsonResponse([
                'status' => 'error',
                'message' => 'The requested resource could not be found.'
                // 'message' => 'An unexpected error occurred.'
            ], $this->getStatusCode($exception));
        }
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof NotFoundHttpException) {
            return Response::HTTP_NOT_FOUND;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
