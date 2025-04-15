<?php

namespace App\Service\Notify;

use App\Enum\NotifyStatus;
use App\Service\Notify\FirebaseNotify;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotifyService
{
    private LoggerInterface $logger;
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FirebaseNotify $notify,
        LoggerInterface $logger,
    ) {
        $this->logger = $logger;
    }

    //% ---------------------------------
    //% New Trip Notification
    //% ---------------------------------
    public function sendNewTripNotification(mixed $trip, ?int $excluded = null): void
    {

        $fcmTokens = [];
        $notificationData = $this->prepareNotificationData($trip);

        $this->notify->sendNotificationToMultipleDevices($fcmTokens, $notificationData['data'], $notificationData['dataPayload']);
    }

    private function prepareNotificationData(mixed $trip, NotifyStatus $status = NotifyStatus::NEW): array
    {
        $dataPayload = [
            'id' => $trip->getId(),
            'status' => $trip->getStatus()->value
        ];

        switch ($status) {
            case NotifyStatus::NEW:
                $notifBody = 'from ' . $trip->getStartAddress() . ' to ' . $trip->getEndAddress();
                $returnedData = [
                    'title' => 'New Trip',
                    'body' => $notifBody
                ];
                break;
            default:
                //    
        }

        return [
            'data' => $returnedData,
            'dataPayload' => $dataPayload
        ];
    }
}
