<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    private $client;
    private $accountSid;
    private $authToken;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->client = new Client($this->accountSid, $this->authToken);
    }

    /**
     * Get available phone numbers for purchase
     */
    public function getAvailableNumbers($countryCode = 'US', $limit = 10, $areaCode = null)
    {
        try {
            $params = [
                'limit' => $limit,
                'voiceEnabled' => true,
                'smsEnabled' => true
            ];
            
            // Add area code filter if provided
            if ($areaCode) {
                $params['areaCode'] = $areaCode;
            }
            
            $availableNumbers = $this->client->availablePhoneNumbers($countryCode)
                ->local
                ->read($params);

            $formattedNumbers = [];
            foreach ($availableNumbers as $number) {
                $formattedNumbers[] = [
                    'phone_number' => $number->phoneNumber,
                    'locality' => $number->locality ?? null,
                    'region' => $number->region ?? null,
                    'capabilities' => [
                        'voice' => $number->capabilities->voice ?? false,
                        'sms' => $number->capabilities->sms ?? false,
                        'mms' => $number->capabilities->mms ?? false
                    ]
                ];
            }

            Log::info('Twilio Get Available Numbers Success: ' . count($formattedNumbers) . ' numbers found');
            return [
                'success' => true,
                'data' => $formattedNumbers
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio Get Available Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get available numbers from Twilio: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Twilio Get Available Numbers Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service error while getting available numbers'
            ];
        }
    }

    /**
     * Purchase a phone number
     */
    public function purchaseNumber($phoneNumber)
    {
        try {
            // Ensure phone number is in E.164 format
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            
            Log::info('Twilio Purchase Number Attempt: ' . $phoneNumber);
            
            // Check if the number is already owned by this account
            $existingNumbers = $this->client->incomingPhoneNumbers
                ->read(['phoneNumber' => $phoneNumber], 1);
            
            if (!empty($existingNumbers)) {
                Log::info('Twilio Purchase Number: Number already owned by account');
                return [
                    'success' => true,
                    'data' => [
                        'sid' => $existingNumbers[0]->sid,
                        'phone_number' => $existingNumbers[0]->phoneNumber,
                        'friendly_name' => $existingNumbers[0]->friendlyName,
                        'status' => 'active'
                    ],
                    'message' => 'Phone number already owned by this account'
                ];
            }
            
            // Check if the number is available for purchase
            $availableNumbers = $this->client->availablePhoneNumbers('US')
                ->local
                ->read(['phoneNumber' => $phoneNumber], 1);
            
            if (empty($availableNumbers)) {
                Log::warning('Twilio Purchase Number: Number not available - ' . $phoneNumber);
                return [
                    'success' => false,
                    'message' => 'Phone number is not available for purchase. Please select a different number from the available list.'
                ];
            }
            
            // Purchase the phone number
            $incomingPhoneNumber = $this->client->incomingPhoneNumbers
                ->create([
                    'phoneNumber' => $phoneNumber,
                    // 'voiceUrl' => config('app.url') . '/api/twilio/voice',
                    // 'smsUrl' => config('app.url') . '/api/twilio/sms',
                    'voiceMethod' => 'POST',
                    'smsMethod' => 'POST'
                ]);

            Log::info('Twilio Purchase Number Success: ' . $phoneNumber . ' - SID: ' . $incomingPhoneNumber->sid);
            
            return [
                'success' => true,
                'data' => [
                    'sid' => $incomingPhoneNumber->sid,
                    'phone_number' => $incomingPhoneNumber->phoneNumber,
                    'friendly_name' => $incomingPhoneNumber->friendlyName,
                    'status' => $incomingPhoneNumber->status
                ],
                'message' => 'Phone number purchased successfully'
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio Purchase Number Error: ' . $e->getMessage());
            Log::error('Twilio Purchase Number Request Data: ' . json_encode([
                'PhoneNumber' => $phoneNumber,
                'VoiceUrl' => config('app.url') . '/api/twilio/voice',
                'SmsUrl' => config('app.url') . '/api/twilio/sms',
                'VoiceMethod' => 'POST',
                'SmsMethod' => 'POST'
            ]));
            
            $errorMessage = 'Failed to purchase number from Twilio';
            if ($e->getMessage()) {
                $errorMessage = $e->getMessage();
                
                // Provide more specific error messages
                if (str_contains($errorMessage, 'did or area_code is required')) {
                    $errorMessage = 'Phone number is not available for purchase. Please select a different number from the available list.';
                } elseif (str_contains($errorMessage, 'not available')) {
                    $errorMessage = 'Phone number is no longer available. Please select a different number.';
                } elseif (str_contains($errorMessage, 'already owned')) {
                    $errorMessage = 'Phone number is already owned by another account. Please select a different number.';
                }
            }
            
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        } catch (\Exception $e) {
            Log::error('Twilio Purchase Number Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service error while purchasing number'
            ];
        }
    }

    /**
     * Get purchased phone numbers
     */
    public function getPurchasedNumbers($limit = 50)
    {
        try {
            $incomingPhoneNumbers = $this->client->incomingPhoneNumbers
                ->read([], $limit);

            $formattedNumbers = [];
            foreach ($incomingPhoneNumbers as $number) {
                $formattedNumbers[] = [
                    'sid' => $number->sid,
                    'phone_number' => $number->phoneNumber,
                    'friendly_name' => $number->friendlyName,
                    'status' => $number->status,
                    'voice_url' => $number->voiceUrl,
                    'sms_url' => $number->smsUrl
                ];
            }

            Log::info('Twilio Get Purchased Numbers Success: ' . count($formattedNumbers) . ' numbers found');
            return [
                'success' => true,
                'data' => $formattedNumbers
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio Get Purchased Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get purchased numbers from Twilio'
            ];
        } catch (\Exception $e) {
            Log::error('Twilio Get Purchased Numbers Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service error while getting purchased numbers'
            ];
        }
    }

    /**
     * Release a phone number
     */
    public function releaseNumber($phoneNumberSid)
    {
        try {
            Log::info('Twilio Release Number Attempt: ' . $phoneNumberSid);
            
            $this->client->incomingPhoneNumbers($phoneNumberSid)->delete();
            
            Log::info('Twilio Release Number Success: ' . $phoneNumberSid);
            return [
                'success' => true,
                'message' => 'Phone number released successfully'
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio Release Number Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to release number from Twilio: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Twilio Release Number Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service error while releasing number'
            ];
        }
    }

    /**
     * Format phone number to E.164 format
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-digit characters except +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // If it doesn't start with +, assume it's a US number
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+1' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
} 