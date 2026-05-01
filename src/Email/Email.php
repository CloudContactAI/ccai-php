<?php

/**
 * Email.php - A PHP module for sending email campaigns via CloudContactAI
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Email;

use CloudContactAI\CCAI\CCAI;

/**
 * Email service for sending campaigns through the CloudContactAI platform
 */
class Email
{
    private CCAI $ccai;

    public function __construct(CCAI $ccai)
    {
        $this->ccai = $ccai;
    }

    /**
     * Send an email campaign to multiple recipients
     *
     * @param EmailAccount[] $accounts List of recipient accounts
     * @param string $subject Email subject
     * @param string $htmlContent HTML body of the email
     * @param string $senderEmail Sender email address
     * @param string $replyEmail Reply-to email address
     * @param string $senderName Sender display name
     * @param string|null $title Campaign title (defaults to subject)
     * @return array API response
     */
    public function send(
        array $accounts,
        string $subject,
        string $htmlContent,
        string $senderEmail,
        string $replyEmail,
        string $senderName,
        ?string $title = null
    ): array {
        $payload = [
            'accounts'     => array_map(fn(EmailAccount $a) => $a->toArray(), $accounts),
            'subject'      => $subject,
            'title'        => $title ?? $subject,
            'message'      => $htmlContent,
            'senderEmail'  => $senderEmail,
            'replyEmail'   => $replyEmail,
            'senderName'   => $senderName,
            'campaignType' => 'EMAIL',
            'addToList'    => 'noList',
            'contactInput' => 'accounts',
            'fromType'     => 'single',
            'senders'      => [],
        ];

        return $this->ccai->emailRequest('POST', '/campaigns', $payload);
    }

    /**
     * Send an email campaign with a full campaign configuration array
     *
     * @param array $campaign Campaign configuration
     * @return array API response
     */
    public function sendCampaign(array $campaign): array
    {
        $payload = [
            'accounts'     => $campaign['accounts']     ?? [],
            'subject'      => $campaign['subject']      ?? '',
            'title'        => $campaign['title']        ?? ($campaign['subject'] ?? ''),
            'message'      => $campaign['message']      ?? '',
            'senderEmail'  => $campaign['senderEmail']  ?? '',
            'replyEmail'   => $campaign['replyEmail']   ?? '',
            'senderName'   => $campaign['senderName']   ?? '',
            'campaignType' => $campaign['campaignType'] ?? 'EMAIL',
            'addToList'    => $campaign['addToList']    ?? 'noList',
            'contactInput' => $campaign['contactInput'] ?? 'accounts',
            'fromType'     => $campaign['fromType']     ?? 'single',
            'senders'      => $campaign['senders']      ?? [],
        ];

        return $this->ccai->emailRequest('POST', '/campaigns', $payload);
    }

    /**
     * Send an email to a single recipient
     *
     * @param string $firstName Recipient first name
     * @param string $lastName Recipient last name
     * @param string $email Recipient email address
     * @param string $subject Email subject
     * @param string $htmlContent HTML body of the email
     * @param string $senderEmail Sender email address
     * @param string $replyEmail Reply-to email address
     * @param string $senderName Sender display name
     * @param string|null $title Campaign title (defaults to subject)
     * @return array API response
     */
    public function sendSingle(
        string $firstName,
        string $lastName,
        string $email,
        string $subject,
        string $htmlContent,
        ?string $textContent = null,
        string $senderEmail = 'noreply@cloudcontactai.com',
        string $replyEmail = 'noreply@cloudcontactai.com',
        string $senderName = 'CloudContactAI',
        ?string $title = null
    ): array {
        $payload = [
            'accounts'     => [['firstName' => $firstName, 'lastName' => $lastName, 'email' => $email, 'phone' => '']],
            'subject'      => $subject,
            'title'        => $title ?? $subject,
            'message'      => $htmlContent,
            'senderEmail'  => $senderEmail,
            'replyEmail'   => $replyEmail,
            'senderName'   => $senderName,
            'campaignType' => 'EMAIL',
            'addToList'    => 'noList',
            'contactInput' => 'accounts',
            'fromType'     => 'single',
            'senders'      => [],
        ];

        if ($textContent !== null && $textContent !== '') {
            $payload['textContent'] = $textContent;
        }

        return $this->ccai->emailRequest('POST', '/campaigns', $payload);
    }

}
