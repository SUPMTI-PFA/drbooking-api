<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\User;
use App\Helpers\Helpers;
use App\Service\Notify\FirebaseNotify;
use App\Services\GlobalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GlobalController extends AbstractController
{
    public function __construct(
        private FirebaseNotify $firebaseNotify,
        private EntityManagerInterface $entityManager,

    ) {}

    #[Route('api/notify/send', name: 'send_notify', methods: ['POST'])]
    public function sendNotify(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $returnedData = [
            'title' => 'Notification Test',
            'body' => 'Try notification'
        ];

        $dataPayload = [
            'id' => 0
        ];

        $notificationData = [
            'data' => $returnedData,
            'dataPayload' => $dataPayload
        ];

        $fcmTokens = [$data['token']];

        $this->firebaseNotify->sendNotificationToMultipleDevices($fcmTokens, $notificationData['data'], $notificationData['dataPayload']);

        return new Response('message');
    }

    #[Route('api/notify/config/send', name: 'send_notify_conf', methods: ['POST'])]
    public function sendNotifyConfig(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $returnedData = [
            'title' => 'Notification Test',
            'body' => 'Test Notification'
        ];

        $dataPayload = [
            'id' => 488
        ];

        $notificationData = [
            'data' => $returnedData,
            'dataPayload' => $dataPayload
        ];

        $fcmTokens = [$data['token']];

        $this->firebaseNotify->sendNotificationToMultipleDevices($fcmTokens, $notificationData['data'], $notificationData['dataPayload'], $data['new']);

        return new Response('message');
    }
}
