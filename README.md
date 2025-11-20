# LearnPress Certificates & Subscriptions API

A WordPress plugin that extends LearnPress with REST API endpoints for certificates and Paid Member Subscriptions. Provides API access to certificate data and subscription status checking.

## Features

- **REST API Endpoints**: Complete REST API for certificates and subscription status
- **Certificate Management**: Get user certificates, verify certificates by code
- **Subscription Status Checking**: Check user subscription status via API
- **Paid Member Subscriptions Integration**: Full integration with Paid Member Subscriptions plugin
- **Authentication Support**: Secure API endpoints with proper permission checks

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- LearnPress plugin installed and activated (version 4.0.0 or higher)
- Paid Member Subscriptions plugin (optional, for subscription endpoints)

## Installation

1. Upload the `learnpress-certificates-extension` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure LearnPress is installed and activated
4. Go to LearnPress > Certificate Settings to configure the plugin

## Configuration

### General Settings

Navigate to **LearnPress > Certificate Settings** to configure:

- **Certificate Width**: Set the width of certificate images (default: 1200px)
- **Certificate Height**: Set the height of certificate images (default: 800px)
- **Auto Generate**: Enable automatic certificate generation on course completion

### Course Settings

For each course, you can enable or disable certificate generation:

1. Edit a course in WordPress
2. Look for the "Certificate Settings" meta box in the sidebar
3. Check "Enable certificate for this course" to enable certificates for that course

## Usage

### For Students

1. Complete a course that has certificates enabled
2. Receive an email notification with your certificate
3. View your certificate by clicking "View Certificate" on the course page
4. Access all your certificates from your LearnPress profile under the "Certificates" tab
5. Download or print your certificates

### For Administrators

1. View all certificates: **LearnPress > Certificates**
2. Configure settings: **LearnPress > Certificate Settings**
3. Enable/disable certificates per course: Edit course > Certificate Settings meta box

## Certificate Information

Each certificate includes:
- Student name
- Course title
- Issue date
- Unique certificate code for verification
- Professional design with decorative borders

## File Structure

```
learnpress-certificates-extension/
├── learnpress-certificates-extension.php  # Main plugin file
├── includes/
│   ├── class-lpce-admin.php              # Admin functionality
│   ├── class-lpce-frontend.php           # Frontend functionality
│   ├── class-lpce-certificate-generator.php  # Certificate generation
│   └── class-lpce-database.php           # Database operations
├── assets/
│   ├── css/
│   │   ├── admin.css                     # Admin styles
│   │   └── frontend.css                  # Frontend styles
│   └── js/
│       ├── admin.js                      # Admin scripts
│       └── frontend.js                   # Frontend scripts
└── README.md                             # This file
```

## Database

The plugin creates a custom table `wp_lpce_certificates` to store certificate information:
- Certificate ID
- User ID
- Course ID
- Certificate Code
- Issue Date
- File Path
- Status

## REST API

The plugin provides REST API endpoints following the LearnPress REST API structure. All endpoints are under the `/wp-json/learnpress/v1/` namespace.

### Base URL

All endpoints use the base URL: `https://yoursite.com/wp-json/learnpress/v1/`

### Quick Reference

| Endpoint | Method | Auth Required | Description |
|----------|--------|---------------|-------------|
| `/certificates/my` | GET | Yes | Get current user's certificates |
| `/certificates/code/{code}` | GET | No | Verify certificate by code (public) |
| `/subscriptions/my` | GET | Yes | Get current user's subscription status |
| `/subscriptions/user/{user_id}` | GET | Yes | Get user's subscription status |
| `/subscriptions/plans` | GET | No | Get all subscription plans |

### Authentication

All endpoints (except public certificate verification and subscription plans) require authentication. Use WordPress REST API authentication methods:

#### Application Passwords (Recommended)

1. Go to **Users > Your Profile** in WordPress admin
2. Scroll to "Application Passwords" section
3. Create a new application password
4. Use it in the `Authorization` header:

```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/certificates/my" \
  -u "username:application_password" \
  -H "Content-Type: application/json"
```

Or with Bearer token:

```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/certificates/my" \
  -H "Authorization: Basic $(echo -n 'username:application_password' | base64)" \
  -H "Content-Type: application/json"
```

#### Cookie Authentication (Browser)

For logged-in users in the browser, cookies are automatically sent with requests.

### Subscription Endpoints

The subscription endpoints require the Paid Member Subscriptions plugin to be installed and active. If the plugin is not active, subscription endpoints will return a 503 error.

### Endpoints

#### Get My Certificates

**GET** `/wp-json/learnpress/v1/certificates/my`

Get all certificates for the currently authenticated user. Requires authentication.

**Authentication:** Required (logged-in user)

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/certificates/my" \
  -H "Authorization: Bearer YOUR_APPLICATION_PASSWORD" \
  -H "Content-Type: application/json"
```

**Example Response:**
```json
[
  {
    "id": 123,
    "certificate_code": "LPCE-ABC123XYZ456",
    "file_url": "https://yoursite.com/wp-content/uploads/lpce-certificates/certificate-5-42-1234567890.png",
    "status": "active",
    "user": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "course": {
      "id": 42,
      "title": "Introduction to WordPress",
      "slug": "introduction-to-wordpress",
      "url": "https://yoursite.com/course/introduction-to-wordpress/"
    }
  }
]
```

#### Verify Certificate by Code (Public)

**GET** `/wp-json/learnpress/v1/certificates/code/{code}`

Public endpoint to verify a certificate by its verification code. No authentication required.

**Parameters:**
- `code` (string, required): Certificate verification code (alphanumeric)

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/certificates/code/LPCE-ABC123XYZ456" \
  -H "Content-Type: application/json"
```

**Example Response:**
```json
{
  "id": 123,
  "certificate_code": "LPCE-ABC123XYZ456",
  "file_url": "https://yoursite.com/wp-content/uploads/lpce-certificates/certificate-5-42-1234567890.png",
  "status": "active",
  "user": {
    "id": 5,
    "name": "John Doe",
    "email": ""
  },
  "course": {
    "id": 42,
    "title": "Introduction to WordPress",
    "slug": "introduction-to-wordpress",
    "url": "https://yoursite.com/course/introduction-to-wordpress/"
  }
}
```

**Note:** The email field is intentionally empty in public verification responses for privacy.

#### Get My Subscription Status

**GET** `/wp-json/learnpress/v1/subscriptions/my`

Get subscription status for the currently authenticated user. Requires authentication.

**Authentication:** Required (logged-in user)

**Parameters:**
- None (uses current authenticated user)

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/subscriptions/my" \
  -H "Authorization: Bearer YOUR_APPLICATION_PASSWORD" \
  -H "Content-Type: application/json"
```

**Example Response:**
```json
{
  "user_id": 5,
  "has_subscription": true,
  "has_active_subscription": true,
  "is_member": true,
  "subscriptions": [
    {
      "id": 123,
      "subscription_plan_id": 1,
      "status": "active",
      "start_date": "2024-01-15 10:30:00",
      "expiration_date": "2024-02-15 10:30:00",
      "auto_renew": true,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "description": "Premium subscription plan",
        "price": "29.99",
        "duration": 1
      }
    }
  ],
  "subscription_count": 1
}
```

**Error Response (PMS not active):**
```json
{
  "code": "rest_pms_not_active",
  "message": "Paid Member Subscriptions plugin is not active.",
  "data": {
    "status": 503
  }
}
```

#### Get User Subscription Status

**GET** `/wp-json/learnpress/v1/subscriptions/user/{user_id}`

Get subscription status for a specific user. Users can only check their own subscription unless they're administrators.

**Authentication:** Required (logged-in user)

**Parameters:**
- `user_id` (integer, required): User ID to check

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/subscriptions/user/5" \
  -H "Authorization: Bearer YOUR_APPLICATION_PASSWORD" \
  -H "Content-Type: application/json"
```

**Example Response:**
Same format as "Get My Subscription Status" endpoint.

**Error Response (unauthorized):**
```json
{
  "code": "rest_cannot_access",
  "message": "Sorry, you do not have permission to view this user's subscription status.",
  "data": {
    "status": 401
  }
}
```

#### Get All Subscription Plans

**GET** `/wp-json/learnpress/v1/subscriptions/plans`

Get all subscription plans/packages. Public endpoint (no authentication required by default).

**Authentication:** Not required (public endpoint)

**Parameters:**
- `only_active` (boolean, optional): Return only active plans (default: false)
- `include` (array, optional): Include specific plan IDs (e.g., `[1, 2, 3]`)
- `exclude` (array, optional): Exclude specific plan IDs (e.g., `[4, 5]`)

**Example Request (all plans):**
```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/subscriptions/plans" \
  -H "Content-Type: application/json"
```

**Example Request (only active plans):**
```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/subscriptions/plans?only_active=true" \
  -H "Content-Type: application/json"
```

**Example Request (specific plans):**
```bash
curl -X GET "https://yoursite.com/wp-json/learnpress/v1/subscriptions/plans?include[]=1&include[]=2" \
  -H "Content-Type: application/json"
```

**Example Response:**
```json
{
  "plans": [
    {
      "id": 1,
      "name": "Premium Plan",
      "description": "Premium subscription plan with full access",
      "price": "29.99",
      "status": "active",
      "duration": 1,
      "duration_unit": "month",
      "user_role": "subscriber",
      "top_parent": 0,
      "sign_up_fee": "0.00",
      "trial_duration": 0,
      "trial_duration_unit": "day",
      "recurring": true,
      "type": "regular",
      "fixed_membership": false,
      "fixed_expiration_date": "",
      "allow_renew": true
    }
  ],
  "total_plans": 1
}
```

**Error Response (PMS not active):**
```json
{
  "code": "rest_pms_not_active",
  "message": "Paid Member Subscriptions plugin is not active.",
  "data": {
    "status": 503
  }
}
```

**Plan Fields:**
Based on the [Paid Member Subscriptions documentation](https://www.cozmoslabs.com/docs/paid-member-subscriptions/developer-knowledge-base/useful-functions/), each plan includes:
- `id`: Plan ID
- `name`: Plan name
- `description`: Plan description
- `price`: Plan price
- `status`: Plan status (active/inactive)
- `duration`: Subscription duration
- `duration_unit`: Duration unit (day, week, month, year)
- `user_role`: WordPress user role assigned to this plan
- `top_parent`: Parent plan ID (for plan groups)
- `sign_up_fee`: One-time sign-up fee
- `trial_duration`: Trial period duration
- `trial_duration_unit`: Trial duration unit
- `recurring`: Whether the plan is recurring
- `type`: Plan type
- `fixed_membership`: Whether it's a fixed membership
- `fixed_expiration_date`: Fixed expiration date (if applicable)
- `allow_renew`: Whether renewals are allowed

### Response Format

#### Certificate Response

Certificate endpoints return JSON responses in the following format:

```json
{
  "id": 123,
  "certificate_code": "LPCE-ABC123XYZ456",
  "file_url": "https://yoursite.com/wp-content/uploads/lpce-certificates/certificate-5-42-1234567890.png",
  "status": "active",
  "user": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "course": {
    "id": 42,
    "title": "Introduction to WordPress",
    "slug": "introduction-to-wordpress",
    "url": "https://yoursite.com/course/introduction-to-wordpress/"
  }
}
```

**Note:** The `/certificates/my` endpoint returns an array of certificate objects, while `/certificates/code/{code}` returns a single certificate object.

#### Subscription Response

Subscription endpoints return JSON responses with the following structure:

```json
{
  "user_id": 5,
  "has_subscription": true,
  "has_active_subscription": true,
  "is_member": true,
  "subscriptions": [...],
  "subscription_count": 1
}
```

#### Error Response Format

All endpoints return errors in the following format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400
  }
}
```

### Common Error Codes

- `rest_cannot_access` (401): Authentication required or insufficient permissions
- `rest_certificate_invalid` (400/404): Invalid certificate code or certificate not found
- `rest_pms_not_active` (503): Paid Member Subscriptions plugin is not active
- `rest_user_invalid` (404): User not found
- `rest_invalid_param` (400): Invalid parameter provided

### Complete Usage Examples

#### Example 1: Get User's Certificates (JavaScript/Fetch)

```javascript
const username = 'your_username';
const password = 'your_application_password';
const base64 = btoa(`${username}:${password}`);

fetch('https://yoursite.com/wp-json/learnpress/v1/certificates/my', {
  method: 'GET',
  headers: {
    'Authorization': `Basic ${base64}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  console.log('Certificates:', data);
  data.forEach(cert => {
    console.log(`Certificate ${cert.id}: ${cert.course.title}`);
  });
})
.catch(error => console.error('Error:', error));
```

#### Example 2: Verify Certificate (Public - No Auth)

```javascript
const certificateCode = 'LPCE-ABC123XYZ456';

fetch(`https://yoursite.com/wp-json/learnpress/v1/certificates/code/${certificateCode}`, {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.code) {
    console.error('Error:', data.message);
  } else {
    console.log('Certificate verified:', data);
    console.log(`Issued to: ${data.user.name}`);
    console.log(`Course: ${data.course.title}`);
  }
})
.catch(error => console.error('Error:', error));
```

#### Example 3: Check Subscription Status (PHP)

```php
$username = 'your_username';
$password = 'your_application_password';
$url = 'https://yoursite.com/wp-json/learnpress/v1/subscriptions/my';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    if ($data['has_active_subscription']) {
        echo "User has active subscription\n";
        foreach ($data['subscriptions'] as $subscription) {
            echo "Plan: {$subscription['plan']['name']}\n";
            echo "Status: {$subscription['status']}\n";
        }
    } else {
        echo "User does not have an active subscription\n";
    }
} else {
    $error = json_decode($response, true);
    echo "Error: {$error['message']}\n";
}
```

#### Example 4: Get All Subscription Plans (Python)

```python
import requests

url = 'https://yoursite.com/wp-json/learnpress/v1/subscriptions/plans'
params = {'only_active': 'true'}

response = requests.get(url, params=params)

if response.status_code == 200:
    data = response.json()
    print(f"Total plans: {data['total_plans']}")
    for plan in data['plans']:
        print(f"Plan: {plan['name']} - ${plan['price']}")
        print(f"  Duration: {plan['duration']} {plan['duration_unit']}")
        print(f"  Status: {plan['status']}")
else:
    error = response.json()
    print(f"Error: {error['message']}")
```

## Hooks & Filters

### Actions

- `learn_press_user_course_finished` - Triggered when a user completes a course
- `lpce_certificate_generated` - Triggered after a certificate is generated

### Filters

- `lpce_certificate_options` - Filter certificate generation options
- `lpce_certificate_email_subject` - Filter certificate email subject
- `lpce_certificate_email_message` - Filter certificate email message

## Customization

### Custom Certificate Templates

You can customize the certificate appearance by modifying the `create_certificate_image()` method in `class-lpce-certificate-generator.php`.

### Styling

Customize the appearance by:
- Editing `assets/css/frontend.css` for frontend styles
- Editing `assets/css/admin.css` for admin styles

## Troubleshooting

### Certificates Not Generating

1. Ensure LearnPress is installed and activated
2. Check that GD Library is enabled in PHP
3. Verify that certificates are enabled for the specific course
4. Check WordPress uploads directory permissions

### Certificate Images Not Displaying

1. Check file permissions on the uploads directory
2. Verify the certificate file was created in `wp-content/uploads/lpce-certificates/`
3. Check for PHP errors in the error log

## Support

For support, feature requests, or bug reports, please visit the plugin repository.

## Changelog

### 4.1.0
- Added subscription status API endpoints
- Integration with Paid Member Subscriptions plugin
- New endpoints:
  - Get my subscription status (`/subscriptions/my`)
  - Get user subscription status (`/subscriptions/user/{user_id}`)
  - Get all subscription plans (`/subscriptions/plans`)
- Plugin renamed to "LearnPress Certificates & Subscriptions API"
- Added PMS plugin check with admin notice
- Full support for all PMS subscription plan fields

### 4.0.0
- REST API endpoints for certificate management
  - Get my certificates (`/certificates/my`)
  - Public certificate verification by code (`/certificates/code/{code}`)

## License

GPL v2 or later

## Credits

Developed for LearnPress LMS integration.

