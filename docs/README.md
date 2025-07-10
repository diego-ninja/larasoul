# Larasoul Documentation

Larasoul is a Laravel package that provides seamless integration with the Verisoul biometric verification API. It supports identity verification through face matching, ID document verification, and liveness detection.

## Quick Start

1. **Install the package**
   ```bash
   composer require ninja/larasoul
   ```

2. **Publish configuration**
   ```bash
   php artisan vendor:publish --tag=larasoul-config
   ```

3. **Set up environment variables**
   ```env
   VERISOUL_API_KEY=your_api_key_here
   VERISOUL_ENVIRONMENT=sandbox
   VERISOUL_ENABLED=true
   ```

4. **Add trait to User model**
   ```php
   use Ninja\Larasoul\Traits\HasRiskProfile;
   
   class User extends Authenticatable
   {
       use HasRiskProfile;
   }
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

## Documentation

### Core Functionality

- **[Middleware Usage](MIDDLEWARE_USAGE.md)** - Complete guide to verification middleware including helper classes
  - Route protection with verification requirements
  - Risk level enforcement
  - Programmatic middleware configuration
  - Helper classes for dynamic routing

- **[Frontend Integration](FRONTEND_INTEGRATION.md)** - JavaScript SDK integration
  - Automatic script injection
  - Blade directives for manual control
  - Session data handling
  - Security considerations

- **[Session Management](SESSION_MANAGEMENT.md)** - Backend-frontend session synchronization
  - Automatic session ID capture
  - API client integration with session IDs
  - Session storage and retrieval
  - Debugging and troubleshooting

- **[Authentication Guards](AUTHENTICATION_GUARDS.md)** - Specialized authentication guards
  - Verification-aware authentication
  - Risk level enforcement
  - High-security operations
  - API-adaptive authentication

- **[Document Verification](DOCUMENT_VERIFICATION.md)** - Separate document storage and management
  - One-to-many document relationships
  - Detailed document information storage
  - Document verification workflow
  - Document analytics and management

### Key Features

#### Verification Middleware
- **Basic Verification**: Ensure users are verified before accessing resources
- **Document Verification**: Require ID document verification
- **Face Verification**: Require biometric face verification
- **Phone Verification**: Require phone number verification
- **Risk Level Control**: Block users based on risk assessment
- **Verification Levels**: Enforce different verification tiers (basic, standard, premium, high_value)

#### Frontend Integration
- **Automatic Script Injection**: Transparent Verisoul SDK loading
- **Session Synchronization**: Frontend-backend session ID sharing
- **Configurable Loading**: Async/sync script loading options
- **Route Exclusions**: Control where scripts are loaded

#### Session Management
- **Automatic Capture**: JavaScript automatically captures Verisoul session IDs
- **Cache Storage**: Efficient session ID storage with TTL
- **API Integration**: Session IDs automatically included in API calls
- **Cross-Request Persistence**: Session data available across requests

## Architecture Overview

### Core Components

- **API Client Layer** (`src/Api/`): Base clients with retry logic and error handling
- **Contracts** (`src/Contracts/`): Interfaces for API clients and operations
- **DTOs** (`src/DTO/`): Immutable data transfer objects
- **Enums** (`src/Enums/`): Type-safe constants and configurations
- **Middleware** (`src/Http/Middleware/`): Route protection and verification enforcement
- **Services** (`src/Services/`): Business logic and API orchestration
- **Events & Listeners** (`src/Events/`, `src/Listeners/`): Event-driven verification workflow

### Helper Classes

- **MiddlewareConfig**: Automatic middleware registration and groups
- **VerificationMiddleware**: Programmatic middleware building
- **VerificationRoutes**: Route grouping with predefined security levels

## Configuration

The package uses environment variables for configuration:

```env
# API Configuration
VERISOUL_API_KEY=your_api_key
VERISOUL_ENVIRONMENT=sandbox  # or production
VERISOUL_ENABLED=true

# Frontend Integration
VERISOUL_FRONTEND_ENABLED=true
VERISOUL_PROJECT_ID=your_project_id
VERISOUL_AUTO_INJECT=true

# Session Management
VERISOUL_SESSION_CAPTURE_ENABLED=true
VERISOUL_AUTO_SEND_SESSION=true
```

## API Support

### Verification Types
- **Face Match**: 1:1 face verification and liveness detection
- **ID Check**: Document verification with OCR and validation
- **Phone Verification**: Carrier and line type verification
- **Account Management**: User account creation and linking

### API Environments
- **Sandbox**: `https://api.sandbox.verisoul.ai` (for testing)
- **Production**: `https://api.prod.verisoul.ai` (for live use)

## Usage Examples

### Basic Route Protection
```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('require.verification');
```

### Advanced Security
```php
use Ninja\Larasoul\Http\Helpers\VerificationRoutes;

VerificationRoutes::highSecurity(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::resource('transactions', TransactionController::class);
});
```

### Frontend Integration
```html
<!DOCTYPE html>
<html>
<head>
    {!! $verisoulScript !!}
</head>
<body>
    <!-- Your content -->
</body>
</html>
```

### API Usage with Session
```php
$builder = app(VerisoulApiClientBuilder::class);
$client = $builder->createFaceMatchClient();

// Session ID automatically included
$response = $client->verifyFace($request->file('selfie'));
```

## Requirements

- **PHP**: 8.2+
- **Laravel**: 11.0+
- **Extensions**: ext-json, ext-curl

## Support

For questions and issues:

1. Check the relevant documentation above
2. Review the configuration in `config/larasoul.php`
3. Verify environment variables are properly set
4. Ensure migrations have been run

## Security

- Never expose API keys in client-side code
- Use sandbox environment for testing
- All user data is properly escaped before output
- Session data only includes non-sensitive information