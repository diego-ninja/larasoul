# Document Verification

This document explains how to use the separate document verification system that stores detailed document information in a dedicated table with a one-to-many relationship from VerificationProfile.

## Overview

The document verification system separates document-specific data from the main verification profile, allowing:
- Multiple documents per verification profile
- Detailed storage of document information from Verisoul API
- Comprehensive document signals and metadata
- Document-specific verification status tracking

## Database Structure

### Tables

#### `verification_profile`
Main verification profile table (document fields removed in migration)

#### `verification_documents` 
Stores individual document verification records:
- **Metadata**: request_id (session_id, project_id, account_id inherited from profile)
- **Verification Decision**: decision, risk_score, risk_flags
- **Document Info**: type, country, state, template_type
- **User Data**: names, dates, ID numbers, address
- **Document Signals**: age, face_match_score, barcode_status, etc.
- **Processing**: status, timestamps, error messages

### Relationships

```php
VerificationProfile hasMany VerificationDocument
VerificationDocument belongsTo VerificationProfile
```

### Design Optimization

To avoid redundancy, the following fields are **NOT duplicated** in the documents table:
- `session_id` - Inherited from VerificationProfile
- `project_id` - Inherited from VerificationProfile  
- `account_id` - Inherited from VerificationProfile

Only `request_id` is stored in documents as it's specific to each API call.

Access these fields via helper methods:
```php
$document->getSessionId();   // From parent profile
$document->getAccountId();   // From parent profile  
$document->getProjectId();   // From parent profile
$document->request_id;       // Directly stored
```

## Models

### VerificationDocument Model

Complete model with:
- **Relationships**: Links to VerificationProfile
- **Casts**: Proper enum casting for all Verisoul enums
- **Accessors**: Helper methods for common operations
- **Scopes**: Query scopes for filtering documents
- **Methods**: Status management and validation

#### Key Methods

```php
// Status checks
$document->isVerified();
$document->isSuspicious();
$document->isExpired();
$document->isAboutToExpire();

// Data access
$document->getFullName();
$document->getAddressDto();
$document->getDocumentSignalsDto();
$document->getDocumentDto();

// Status management
$document->markAsProcessed();
$document->markAsVerified();
$document->markAsFailed($error);
```

#### Query Scopes

```php
// Status filtering
VerificationDocument::verified()->get();
VerificationDocument::suspicious()->get();
VerificationDocument::pending()->get();

// Document filtering
VerificationDocument::withCountry('US')->get();
VerificationDocument::withDocumentType('Driver License')->get();
VerificationDocument::expired()->get();
VerificationDocument::aboutToExpire(30)->get();

// Risk filtering
VerificationDocument::lowRisk()->get();
VerificationDocument::highRisk()->get();
```

### VerificationProfile Updates

Enhanced with document relationship methods:

```php
// Document relationships
$profile->documents(); // HasMany relationship
$profile->hasVerifiedDocuments();
$profile->getLatestVerifiedDocument();
$profile->getVerifiedDocuments();
$profile->getDocumentsByCountry('US');
$profile->getDocumentsByType('Driver License');
```

## Service Layer

### DocumentVerificationService

Comprehensive service for document operations:

#### Store Document from API Response

```php
use Ninja\Larasoul\Services\DocumentVerificationService;

$service = app(DocumentVerificationService::class);

// Store document from Verisoul API response
$document = $service->storeDocumentFromApiResponse($profile, $verifyIdResponse);
```

#### Document Retrieval

```php
// Get latest document
$latest = $service->getLatestDocument($profile);

// Get verified documents
$verified = $service->getVerifiedDocuments($profile);

// Get documents by criteria
$usDocuments = $service->getDocumentsByCountry($profile, 'US');
$licenses = $service->getDocumentsByType($profile, 'Driver License');
$expired = $service->getExpiredDocuments($profile);
$suspicious = $service->getSuspiciousDocuments($profile);
```

#### Document Management

```php
// Manual review
$service->markAsManuallyReviewed($document, true, 'Approved after review');

// Get statistics
$stats = $service->getDocumentStats($profile);

// Validation
$issues = $service->validateDocumentConsistency($document);

// Cleanup
$deletedCount = $service->cleanupOldDocuments(90);
```

## Usage Examples

### Basic Document Storage

```php
use Ninja\Larasoul\Services\DocumentVerificationService;
use Ninja\Larasoul\Api\IDCheckClient;

class DocumentController extends Controller
{
    public function verify(Request $request)
    {
        $user = auth()->user();
        $profile = $user->verificationProfile;
        
        // Call Verisoul API
        $client = app(IDCheckClient::class);
        $response = $client->verifyDocument($request->file('document'));
        
        // Store document
        $service = app(DocumentVerificationService::class);
        $document = $service->storeDocumentFromApiResponse($profile, $response);
        
        return response()->json([
            'document_id' => $document->id,
            'decision' => $document->decision,
            'is_verified' => $document->isVerified(),
            'full_name' => $document->getFullName(),
        ]);
    }
}
```

### Document Listing and Filtering

```php
class DocumentsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $profile = $user->verificationProfile;
        
        $query = $profile->documents();
        
        // Apply filters
        if ($request->has('country')) {
            $query->withCountry($request->country);
        }
        
        if ($request->has('type')) {
            $query->withDocumentType($request->type);
        }
        
        if ($request->has('status')) {
            match($request->status) {
                'verified' => $query->verified(),
                'suspicious' => $query->suspicious(),
                'expired' => $query->expired(),
                default => null
            };
        }
        
        $documents = $query->latest()->paginate(10);
        
        return view('documents.index', compact('documents'));
    }
}
```

### Document Dashboard

```php
class DashboardController extends Controller
{
    public function documents()
    {
        $user = auth()->user();
        $profile = $user->verificationProfile;
        $service = app(DocumentVerificationService::class);
        
        return view('dashboard.documents', [
            'stats' => $service->getDocumentStats($profile),
            'latest' => $service->getLatestDocument($profile),
            'verified' => $service->getVerifiedDocuments($profile)->take(5),
            'expiring' => $service->getDocumentsAboutToExpire($profile),
            'suspicious' => $service->getSuspiciousDocuments($profile)->take(3),
        ]);
    }
}
```

### Manual Review Process

```php
class ReviewController extends Controller
{
    public function review(VerificationDocument $document, Request $request)
    {
        $approved = $request->boolean('approved');
        $notes = $request->input('notes');
        
        $service = app(DocumentVerificationService::class);
        $document = $service->markAsManuallyReviewed($document, $approved, $notes);
        
        return response()->json([
            'status' => 'reviewed',
            'approved' => $approved,
            'document' => $document->only(['id', 'decision', 'verified_at']),
        ]);
    }
    
    public function pending()
    {
        $documents = VerificationDocument::suspicious()
            ->with('verificationProfile.user')
            ->latest()
            ->paginate(20);
            
        return view('review.pending', compact('documents'));
    }
}
```

## Data Transfer Objects (DTOs)

The system integrates with existing DTOs:

### Document DTO Usage

```php
$document = VerificationDocument::find(1);

// Get as DTOs
$documentDto = $document->getDocumentDto();
$addressDto = $document->getAddressDto();
$signalsDto = $document->getDocumentSignalsDto();

// Use DTO methods
if ($addressDto->isComplete()) {
    $formatted = $addressDto->getFormattedAddress();
}

if ($signalsDto) {
    $faceScore = $signalsDto->idFaceMatchScore;
}
```

## Factory and Testing

### Factory Usage

```php
use Ninja\Larasoul\Models\VerificationDocument;

// Create verified document
$verified = VerificationDocument::factory()->verified()->create();

// Create expired document
$expired = VerificationDocument::factory()->expired()->create();

// Create specific document types
$license = VerificationDocument::factory()->driverLicense()->create();
$passport = VerificationDocument::factory()->passport()->create();

// Create for specific country
$usDocument = VerificationDocument::factory()->forCountry('US')->create();
```

### Testing Examples

```php
class DocumentVerificationTest extends TestCase
{
    public function test_can_store_document_from_api_response()
    {
        $profile = VerificationProfile::factory()->create();
        $response = new VerifyIdResponse(/* ... */);
        
        $service = app(DocumentVerificationService::class);
        $document = $service->storeDocumentFromApiResponse($profile, $response);
        
        $this->assertInstanceOf(VerificationDocument::class, $document);
        $this->assertEquals($profile->id, $document->verification_profile_id);
    }
    
    public function test_document_verification_updates_profile()
    {
        $profile = VerificationProfile::factory()->create();
        $document = VerificationDocument::factory()
            ->verified()
            ->create(['verification_profile_id' => $profile->id]);
            
        $this->assertTrue($profile->fresh()->hasVerifiedDocuments());
    }
    
    public function test_document_scopes_work_correctly()
    {
        VerificationDocument::factory()->verified()->create();
        VerificationDocument::factory()->suspicious()->create();
        VerificationDocument::factory()->expired()->create();
        
        $this->assertCount(1, VerificationDocument::verified()->get());
        $this->assertCount(1, VerificationDocument::suspicious()->get());
        $this->assertCount(1, VerificationDocument::expired()->get());
    }
}
```

## Migration Guide

### From Old Structure

If migrating from the old structure where document data was stored in the `verification_profile` table:

1. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Migrate Existing Data** (if needed):
   ```php
   // Migration script to move existing document data
   VerificationProfile::whereNotNull('document_data')->chunk(100, function ($profiles) {
       foreach ($profiles as $profile) {
           $profile->documents()->create([
               'document_type' => $profile->document_type,
               'document_country_code' => $profile->document_country_code,
               'document_state' => $profile->document_state,
               // ... map other fields
               'verified_at' => $profile->document_verified_at,
           ]);
       }
   });
   ```

3. **Update Code**:
   - Replace direct document field access with document relationships
   - Update verification checks to use new methods
   - Modify API response handling to use DocumentVerificationService

## Performance Considerations

### Indexing
The migration includes optimized indexes for:
- Document lookups by profile
- Country/state filtering
- Risk score queries
- Date-based queries
- Name searches

### Eager Loading
```php
// Load documents with profile
$profiles = VerificationProfile::with('documents')->get();

// Load specific document relationships
$documents = VerificationDocument::with('verificationProfile.user')->get();
```

### Pagination
```php
// Paginate documents efficiently
$documents = $profile->documents()
    ->verified()
    ->latest()
    ->paginate(20);
```

## Security Considerations

### Data Access Control
```php
// Ensure users can only access their documents
class DocumentPolicy
{
    public function view(User $user, VerificationDocument $document)
    {
        return $user->id === $document->verificationProfile->user_id;
    }
}
```

### PII Handling
- Document data contains sensitive PII
- Implement proper data retention policies
- Consider data anonymization for analytics
- Ensure GDPR compliance for data deletion

## Monitoring and Analytics

### Document Statistics

```php
class DocumentAnalytics
{
    public function getGlobalStats()
    {
        return [
            'total_documents' => VerificationDocument::count(),
            'verified_rate' => VerificationDocument::verified()->count() / VerificationDocument::count(),
            'country_breakdown' => VerificationDocument::groupBy('document_country_code')
                ->selectRaw('document_country_code, count(*) as count')
                ->get(),
            'type_breakdown' => VerificationDocument::groupBy('document_type')
                ->selectRaw('document_type, count(*) as count')
                ->get(),
        ];
    }
}
```

This document verification system provides a robust, scalable foundation for handling multiple documents per user with comprehensive tracking and management capabilities.