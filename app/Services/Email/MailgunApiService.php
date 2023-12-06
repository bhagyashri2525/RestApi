<?php

namespace App\Services\Email;

use App\MailgunApiResponse;
use Mailgun\Mailgun;
use Mailgun\Hydrator\ArrayHydrator;
use Mailgun\HttpClient\HttpClientConfigurator;
use PHPOpenSourceSaver\JWTAuth\Claims\Subject;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionProperty;

class MailgunApiService
{
    public function sendMail($email, $subject, $body, $text = null, $tag = null, $attachments = [], $deliveryTime = null)
    {
        $mgClient = \Mailgun\Mailgun::create(env('MAIL_GUN_API_KEY'), 'https://api.mailgun.net/v3/' . env('MAIL_GUN_DOMAIN') . '/messages');
        $domain = env('MAIL_GUN_DOMAIN');
        $params = [
            'from' => 'Streamon | Hub ' . env('MAIL_GUN_EMAIL'),
            'to' => $email,
            'subject' => $subject,
            'text' => $text,
            'html' => $body,
            'date' => $deliveryTime
        ];

        if (!empty($deliveryTime)) {
            $params['o:deliverytime'] = $deliveryTime;
        }

        if (!empty($tag)) {
            $params['o:tag'] = $tag;
        }

        if (sizeOf($attachments) > 0) {
            foreach ($attachments as $key => $attachment) {
                $params['attachment'][$key] = ['filePath' => $attachment, 'filename' => 'attachment_' . ($key + 1) . '.' . pathinfo($attachment)['extension']];
            }
        }

        $result = $mgClient->messages()->send($domain, $params);

        return $result;
    }

    public function getLogs($date = null)
    {
        if($date){
            $begainDate =  new DateTime($date);
        }else{
            $begainDate =  new DateTime();
            $begainDate->modify("-1 days");
        }
        
        $begainDate = $begainDate->format('D, d M Y H:i:s').' -0000';
        

        $domain = env('MAIL_GUN_DOMAIN');
        $configurator = new HttpClientConfigurator();
        $configurator->setApiKey(env('MAIL_GUN_API_KEY'));
        $configurator->setEndpoint('https://api.mailgun.net/v3/' . env('MAIL_GUN_DOMAIN') . '/events');

        $mgClient = new Mailgun($configurator, new ArrayHydrator());

        $queryString = array(
            //'begin'        => 'Sat, 25 Feb 2023 09:00:00 -0000',
            'begin'        => $begainDate,
            'ascending'    => 'yes',
            'limit'        =>  25,
            'pretty'       => 'yes',
            'event'       => 'delivered'
        );

        $result = $mgClient->events()->get($domain, $queryString);
        if (isset($result['items'])) {
            return ($result['items']);
        }
        return null;
    }

    public function getMailLogs($companyId = NULL, $eventId = NULL, $emailLogId = NULL, $nextPageUrl = NULL, $beginDate = NULL, $endDate = NULL) {

        // if(!$companyId || !$eventId || !$emailLogId) {
        //     return [NULL, NULL, NULL];
        // }

        if($nextPageUrl) {
            // Make a request for the next page
            try {
                $shouldDispatch = true;
                $nextPageResponse = Http::withBasicAuth('api', env('MAIL_GUN_API_KEY'))
                    ->get($nextPageUrl);

                $nextPageContent = $nextPageResponse->body(); // Get the raw JSON response as a string
                $nextPageData = json_decode($nextPageContent, true); // Convert JSON to a PHP array
                
                if ($nextPageData && count($nextPageData['items']) && $nextPageData['paging']['next']) {
                    $nextPageUrl = $nextPageData['paging']['next'];
                } else {
                    $nextPageUrl = NULL;
                    $shouldDispatch = false;
                }
                (new EmailService)->saveMailLog($companyId, $eventId, $emailLogId, $nextPageData['items']);
                return [$companyId, $eventId, $emailLogId, $nextPageUrl, $shouldDispatch];
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $mgClient = Mailgun::create(env('MAIL_GUN_API_KEY'), 'https://api.mailgun.net/v3/');
            $domain = env('MAIL_GUN_DOMAIN');
            
            $beginDate = !empty($beginDate) ? $beginDate : "";
            $endDate = !empty($endDate) ? $endDate : date("Y-m-d", strtotime("now"));
    
            $beginDate = date("r", strtotime($beginDate));
            $endDate = date("r", strtotime(date("Y-m-d 23:59:59", strtotime($endDate))));
            $limit = 10; // Number of logs per request
            $events = []; // Empty array for events
            $tags = !empty($emailLogId) ? [$emailLogId] : []; // Does not support Array
            $recipients = []; // Empty array for recipients
            $messageIds = []; // Empty array for message IDs '20230708085400.f8dad8ab964f6bd1@eventonline.in'
            $params = [
                'begin' => $beginDate,
                'end' => $endDate,
                'ascending' => 'yes',
                'limit' => $limit,
                'pretty' => 'yes',
                'event' => $events,
                'recipient' => $recipients,
                'message-id' => count($messageIds) ? implode(',', $messageIds) : [],
            ];
            try {
                $response = $nextPageUrl = NULL;
                $shouldDispatch = true;
                $log = NULL;
                if($tags) {
                    foreach ($tags as $tag) {
                        $log = [];
                        $params['tags'] = $tag;
                        $response = $mgClient->events()->get($domain, $params);
                        $items = $response->getItems();
                        $result = [];
                        foreach ($items as $event) {
                            $eventArray = [];

                            $reflection = new ReflectionClass($event);

                            foreach ($reflection->getMethods() as $method) {
                                if (str_starts_with($method->name, 'get')) {
                                    $propertyName = lcfirst(substr($method->name, 3));

                                    // Check if the property has a corresponding getter method
                                    if ($reflection->hasProperty($propertyName)) {
                                        $property = $reflection->getProperty($propertyName);
                                        $property->setAccessible(true);
                                        $eventArray[$propertyName] = $property->getValue($event);
                                    }
                                }
                            }

                            $result[] = $eventArray;
                        }
                        (new EmailService)->saveMailLog($companyId, $eventId, $emailLogId, $result);
                    }
                } else {
                    $response = $mgClient->events()->get($domain, $params);
                    $items = $response->getItems();
                        $result = [];
                        foreach ($items as $event) {
                            $eventArray = [];

                            $reflection = new ReflectionClass($event);

                            foreach ($reflection->getMethods() as $method) {
                                if (str_starts_with($method->name, 'get')) {
                                    $propertyName = lcfirst(substr($method->name, 3));

                                    // Check if the property has a corresponding getter method
                                    if ($reflection->hasProperty($propertyName)) {
                                        $property = $reflection->getProperty($propertyName);
                                        $property->setAccessible(true);
                                        $eventArray[$propertyName] = $property->getValue($event);
                                    }
                                }
                            }

                            $result[] = $eventArray;
                        }
                        (new EmailService)->saveMailLog($companyId, $eventId, $emailLogId, $result);
                }

                
                // Use reflection to access the private property
                $reflection = new ReflectionClass($response);
                $paging = $reflection->getProperty('paging');

                // Make the private property accessible
                $paging->setAccessible(true);

                // Get the value of the private property
                $pagingArr = $paging->getValue($response);
                if ($response && count($response->getItems()) && $pagingArr['next']) {
                    $nextPageUrl = $pagingArr['next'];
                } else {
                    $nextPageUrl = NULL;
                    $shouldDispatch = false;
                }
                return [$companyId, $eventId, $emailLogId, $nextPageUrl, $shouldDispatch];
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }
}