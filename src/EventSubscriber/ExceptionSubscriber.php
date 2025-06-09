<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        // Get the exception from the event
        $exception = $event->getThrowable();

        // Check if the exception is an instance of HttpException
        if($exception instanceof HttpException) {
           $data = [
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ];

            $response = new JsonResponse($data);
            $event->setResponse($response);
            
        } else {
            // Handle other types of exceptions
            $data = [
                'status' => 500,
                'message' => $exception->getMessage()
            ];

            $response = new JsonResponse($data, 500);
            $event->setResponse($response); 
        }
       
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onExceptionEvent',
        ];
    }
}
