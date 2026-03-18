<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DebugDelaySubscriber implements EventSubscriberInterface
{
    private int $delayMs;

    public function __construct(int $delayMs = 0)
    {
        $this->delayMs = $delayMs;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if ($this->delayMs > 0) {
            usleep($this->delayMs * 1000); // Konwersja ms na mikrosekundy
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}

