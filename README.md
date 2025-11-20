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
3. Ensure LearnPress is installed and activated (version 4.0.0 or higher)
4. The REST API endpoints will be immediately available at `/wp-json/learnpress/v1/`

## Configuration

This plugin requires no configuration. Once activated, the REST API endpoints are immediately available.

### Requirements

- **LearnPress**: Must be installed and activated (version 4.0.0 or higher)
- **Paid Member Subscriptions**: Optional, but required for subscription status endpoints to work

### Plugin Activation

1. Ensure LearnPress is installed and activated
2. Activate this plugin
3. REST API endpoints will be available at `/wp-json/learnpress/v1/`

If LearnPress is not active, an admin notice will be displayed. If Paid Member Subscriptions is not active, a warning notice will be displayed (subscription endpoints will return 503 errors).

## Usage

This plugin provides REST API endpoints for accessing LearnPress certificates and subscription status. It does not provide a frontend interface or admin pages.

### For Developers

Use the REST API endpoints to:
- Retrieve user certificates programmatically
- Verify certificates by code (public verification)
- Check user subscription status
- Get subscription plan information

See the [REST API](#rest-api) section below for complete endpoint documentation and examples.

### For End Users

End users can access their certificates through:
- LearnPress frontend (if LearnPress provides certificate viewing functionality)
- Third-party applications that consume the REST API endpoints
- Public certificate verification using the certificate code

## Certificate Information

Certificates accessed via the API include:
- Certificate ID
- Certificate verification code
- User information (ID, name, email - email hidden in public verification)
- Course information (ID, title, slug, URL)
- Certificate file URL
- Status

## File Structure

```
learnpress-certificates-extension/
├── learnpress-certificates-extension.php  # Main plugin file
├── includes/
│   ├── class-lpce-database.php           # Database operations
│   └── class-lpce-rest-api.php           # REST API endpoints
├── .gitignore                            # Git ignore rules
└── README.md                             # This file
```

## Database

The plugin uses the WordPress `wp_options` table to store certificate information. Certificates are stored as options with the prefix `user_cert_` followed by the certificate code/hash.

The certificate data structure includes:
- Certificate ID (`cert_id`)
- User ID (`user_id`)
- Course ID (`course_id`)
- Certificate Code (stored as part of the option name)
- File URL (generated from upload directory)
- Status (defaults to 'active')

Certificate files are stored in the WordPress uploads directory under `learn-press-cert/` with the filename format: `{certificate_code}.png`

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

This plugin primarily provides REST API endpoints. The plugin uses WordPress core hooks internally:

- `rest_api_init` - Registers REST API routes
- `plugins_loaded` - Loads plugin textdomain
- `init` - Initializes plugin components
- `admin_notices` - Displays admin notices for missing dependencies

Currently, there are no public hooks or filters exposed for developers. If you need to extend functionality, you can:

1. Extend the `LPCE_REST_API` class to add custom endpoints
2. Extend the `LPCE_Database` class to add custom database queries
3. Use WordPress REST API filters to modify responses

## Customization

### Extending REST API Responses

You can customize API responses by using WordPress filters or by extending the `LPCE_REST_API` class. The response formatting is handled in the `format_certificate_response()` method in `includes/class-lpce-rest-api.php`.

### Database Operations

Database operations are handled by the `LPCE_Database` class in `includes/class-lpce-database.php`. You can extend this class to add custom database queries or modify existing ones.

## Troubleshooting

### API Endpoints Not Available

1. Ensure LearnPress is installed and activated (version 4.0.0 or higher)
2. Verify the plugin is activated in WordPress
3. Check that permalinks are not set to "Plain" (Settings > Permalinks)
4. Test the endpoint: `https://yoursite.com/wp-json/learnpress/v1/certificates/code/TEST` (should return an error, not 404)

### Authentication Issues

1. Verify you're using the correct authentication method (Application Passwords recommended)
2. Check that the user account has proper permissions
3. Ensure the `Authorization` header is correctly formatted
4. For cookie authentication, verify you're logged in to WordPress

### Subscription Endpoints Returning 503

1. Ensure Paid Member Subscriptions plugin is installed and activated
2. Check that the plugin is the correct version compatible with your WordPress installation
3. Verify PMS functions are available by checking for `pms_get_member_subscriptions` function

### Certificate Not Found

1. Verify the certificate code is correct (case-sensitive)
2. Check that the certificate exists in the LearnPress system
3. Verify the certificate data is stored in the WordPress options table with the `user_cert_` prefix
4. Check WordPress error logs for database query errors

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

