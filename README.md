# CCAI PHP Examples

Simple examples of using the CloudContactAI PHP library for sending SMS, MMS, and Email messages.

## Setup

1. Install dependencies:
   ```
   composer install
   ```

2. Set your API credentials:
   ```
   export CCAI_CLIENT_ID="your_client_id"
   export CCAI_API_KEY="your_api_key"
   ```

## Usage

### Send SMS
```
php send_sms.php
```

### Send MMS
Place an image file named `imagePHP.jpg` in the root directory, then run:
```
php send_mms.php
```

### Send Email
```
php send_email.php
```

## Notes

- Replace placeholder phone numbers and email addresses with real ones
- For MMS, make sure the image file exists in the specified path