<?php

namespace App\Service\Notify;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FirebaseNotify
{
    public function __construct(
        private ParameterBagInterface $parameters,
        private Messaging $myProjectMessaging
    ) {
    }

    public function sendNotificationToDevice(array $deviceToken, array $notificationData, array $dataPayload = []): MulticastSendReport
    {
        $notification = Notification::fromArray($notificationData);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($dataPayload);

        return $this->myProjectMessaging->sendMulticast($message, $deviceToken);
    }

    public function sendNotificationToMultipleDevices(array $deviceTokens, array $notificationData, array $dataPayload = [], $new = false): MulticastSendReport
    {
        if ($new) {
            $message = CloudMessage::fromArray([
                'data' => $dataPayload,
                'body' => $notificationData
            ]);
        } else {
            $notification = Notification::fromArray($notificationData);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($dataPayload);
        }

        return $this->myProjectMessaging->sendMulticast($message, $deviceTokens);
    }


    public function sendNotificationToMultipleAndroidDevices(array $deviceTokens, array $notificationData, array $dataPayload = []): MulticastSendReport
    {
        $deviceTokenOne = $this->parameters->get('fcm_token');
        $deviceTokenTwo = $this->parameters->get('fcm_token_mobile');

        // $deviceTokens = [$deviceTokenOne, $deviceTokenTwo];

        $config = AndroidConfig::fromArray([
            'ttl' => '3600s',
            'priority' => 'normal',
            'notification' => [
                'title' => '$GOOG up 1.43% on the day',
                'body' => '$GOOG gained 11.80 points to close at 835.67, up 1.43% on the day.',
                'icon' => 'stock_ticker_update',
                'color' => '#f45342',
                'sound' => 'default',
            ],
        ]);

        $notification = Notification::fromArray($notificationData);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($dataPayload);

        $message = $message->withAndroidConfig($config);
        return $this->myProjectMessaging->sendMulticast($message, $deviceTokens);
    }
}
