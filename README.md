# FlowManager - Technical Documentation

## 📖 About FlowManager

FlowManager is a **modern, enterprise-grade workflow automation and approval management system** built on Laravel 11. It transforms complex, multi-step business processes into streamlined, automated workflows that save time, reduce errors, and provide complete visibility.

### 🎯 Why Use FlowManager?

**For Organizations:**
- ⚡ **Eliminate Bottlenecks** - Automated routing ensures requests never get stuck waiting for manual intervention
- 📊 **Complete Transparency** - Real-time tracking and comprehensive audit trails for every request
- 🔒 **Enterprise Security** - Role-based access control with granular permissions ensures data protection
- 📈 **Scalable Architecture** - Built to handle thousands of requests with async processing and queue management
- 💰 **Cost Effective** - Reduce administrative overhead and streamline approval processes organization-wide

**For Teams:**
- 🚀 **Faster Decisions** - Automated notifications and clear approval hierarchies speed up processing time
- 📱 **Easy to Use** - RESTful API design makes integration simple for any frontend or mobile application
- 🔄 **Flexible Workflows** - Define custom approval chains that match your organization's structure
- 📎 **Document Management** - Integrated S3 storage keeps all supporting documents organized and accessible
- 🕒 **Historical Records** - Complete request history for compliance and reporting

**For Developers:**
- 🛠️ **Modern Tech Stack** - Built on Laravel 11 with PostgreSQL, Redis, and AWS S3
- 🎨 **Clean Architecture** - Service layer pattern with clear separation of concerns
- 📡 **Event-Driven** - Asynchronous processing for emails, uploads, and notifications
- 🔍 **Built-in Monitoring** - Telescope and Horizon provide deep insights into system behavior
- 📚 **Well Documented** - Comprehensive technical documentation and API references

---

## ✨ Key Features

### 🔄 Dynamic Workflow Engine
- **Multi-Step Approval Chains** - Create workflows with unlimited approval stages
- **Smart Routing** - Automatic approver assignment based on organizational hierarchy
- **Fallback Logic** - HR fallback ensures requests never get stuck
- **Conditional Paths** - Route requests based on request type, user role, or custom criteria

### 👥 Hierarchical Approval System
- **Organizational Structure** - Respects direct managers, team managers, and department heads
- **Role-Based Approvals** - Assign approvers by role (Manager, HR, Finance, etc.)
- **Parallel Approvals** - Support for multiple approvers at the same stage (coming soon)
- **Delegation** - Temporary approval delegation when team members are unavailable

### 📝 Dynamic Form Builder
- **JSON Schema Validation** - Define custom form fields for each request type
- **Type Safety** - Built-in validation for dates, numbers, text, and custom types
- **Conditional Fields** - Show/hide fields based on other field values
- **Rich Input Types** - Support for text, dates, numbers, dropdowns, file uploads

### 📁 Intelligent Document Management
- **Two-Phase Upload** - Instant response with background S3 processing
- **Streaming Uploads** - Memory-efficient handling of large files
- **Multiple File Types** - Support for PDFs, images, spreadsheets, and documents
- **Auto Cleanup** - Temporary files automatically removed after S3 upload

### 🔐 Enterprise Security
- **Token Authentication** - Laravel Sanctum for secure API access
- **Role-Based Access** - 5 default roles with customizable permissions
- **Policy-Based Authorization** - Fine-grained control over resource access
- **Audit Trail** - Complete history of who did what and when

### ⚡ Asynchronous Processing
- **Queue-Based Jobs** - Non-blocking operations for emails and uploads
- **Laravel Horizon** - Real-time queue monitoring and management
- **Worker Scaling** - Automatic scaling based on queue depth
- **Failed Job Recovery** - Automatic retry logic with exponential backoff

### 📊 Real-Time Monitoring
- **Laravel Telescope** - Deep insights into requests, queries, and events
- **Performance Metrics** - Track response times and database query performance
- **Error Tracking** - Catch and log exceptions with full stack traces
- **Email Logs** - See exactly what notifications were sent and when

### 📱 RESTful API
- **Clean Endpoints** - Intuitive, resource-based API design
- **Pagination Support** - Efficient data retrieval with configurable page sizes (15-100 items)
- **Eager Loading** - Optimized queries minimize N+1 problems
- **Standard Responses** - Consistent JSON format across all endpoints

### 📧 Smart Notifications
- **Email Alerts** - Automatic notifications for request creation, approvals, and rejections
- **Customizable Templates** - Blade-based email templates for branding
- **Queue Processing** - Non-blocking email delivery via background jobs
- **Multiple Drivers** - Support for SMTP, AWS SES, and other email providers

---

## Table of Contents

1. [Introduction](#introduction)
2. [System Architecture](#system-architecture)
3. [Database Schema](#database-schema)
4. [Core Features](#core-features)
5. [API Endpoints](#api-endpoints)
6. [Event-Driven Architecture](#event-driven-architecture)
7. [Background Job Processing](#background-job-processing)
8. [Monitoring & Observability](#monitoring--observability)
9. [Security & Authorization](#security--authorization)
10. [Configuration](#configuration)

---

## 1. Introduction

FlowManager is a **Laravel-based workflow automation and approval management system** designed to handle complex, multi-step business processes. It enables organizations to define dynamic approval hierarchies and automate request processing with built-in support for asynchronous operations, scalable document storage, and comprehensive monitoring.

### Tech Stack

-   **Framework**: Laravel 11.x
-   **Database**: PostgreSQL (Primary) / MySQL (Supported)
-   **Cache & Queue**: Redis
-   **Storage**: AWS S3
-   **Monitoring**: Laravel Telescope, Laravel Horizon
-   **Authentication**: Laravel Sanctum + Spatie Laravel Permission

---

## 2. System Architecture

### 2.1 Core Components

```
┌─────────────────────────────────────────────────────────────┐
│                      API Layer (Routes)                      │
│         /api/auth | /api/requests | /api/approvals          │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                   Controllers Layer                          │
│  AuthController | RequestController | ApprovalController    │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                    Service Layer                             │
│   WorkflowEngine | RequestService | ApprovalService         │
└────────────────────┬────────────────────────────────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
┌───────▼──────┐ ┌──▼──────┐ ┌──▼────────────┐
│   Models     │ │  Events  │ │   Jobs/Queue  │
│              │ │          │ │               │
│ User         │ │ Request  │ │ S3 Upload     │
│ Request      │ │ Created  │ │ Email Send    │
│ Workflow     │ │ Approved │ │               │
└──────────────┘ └──────────┘ └───────────────┘
```

### 2.2 Request Flow

1.  **User submits request** → `RequestController@store`
2.  **WorkflowEngine initiates** → Creates `Request` + `RequestApproval`
3.  **Event fired** → `RequestCreated` event
4.  **Listeners execute** (Asynchronously):
    -   `SendRequestCreatedNotification` (Email to approvers)
    -   `UploadDocumentsToS3` (If documents attached)
5.  **Approver reviews** → `ApprovalController@processApproval`
6.  **WorkflowEngine transitions** → Approves/Rejects + Fires events
7.  **Process complete** → Final status set (`approved` / `rejected`)

---

## 3. Database Schema

### 3.1 Core Tables

#### `users`

| Column       | Type         | Description                   |
| ------------ | ------------ | ----------------------------- |
| id           | bigint       | Primary key                   |
| name         | varchar(255) | User's full name              |
| email        | varchar(255) | Unique email                  |
| password     | varchar(255) | Hashed password               |
| team_id      | bigint (FK)  | Assigned team                 |
| manager_id   | bigint (FK)  | Direct manager (self-ref)     |
| created_at   | timestamp    |                               |
| updated_at   | timestamp    |                               |

**Relationships:**
-   `belongsTo(Team)`
-   `belongsTo(User, 'manager_id')` (Manager)
-   `hasMany(Request)`

---

#### `teams`

| Column      | Type         | Description        |
| ----------- | ------------ | ------------------ |
| id          | bigint       | Primary key        |
| name        | varchar(255) | Team name          |
| manager_id  | bigint (FK)  | Team manager (User)|
| created_at  | timestamp    |                    |
| updated_at  | timestamp    |                    |

**Relationships:**
-   `belongsTo(User, 'manager_id')`
-   `hasMany(User)`

---

#### `request_types`

| Column      | Type         | Description                        |
| ----------- | ------------ | ---------------------------------- |
| id          | bigint       | Primary key                        |
| name        | varchar(255) | e.g., "Leave Request"              |
| description | text         | Detailed description               |
| form_schema | json         | JSON schema for dynamic validation |
| created_at  | timestamp    |                                    |
| updated_at  | timestamp    |                                    |

**Relationships:**
-   `hasOne(Workflow)`
-   `hasMany(Request)`

---

#### `workflows`

| Column          | Type         | Description                    |
| --------------- | ------------ | ------------------------------ |
| id              | bigint       | Primary key                    |
| name            | varchar(255) | Workflow name                  |
| request_type_id | bigint (FK)  | Associated request type        |
| created_at      | timestamp    |                                |
| updated_at      | timestamp    |                                |

**Relationships:**
-   `belongsTo(RequestType)`
-   `hasMany(WorkflowStep)`

---

#### `workflow_steps`

| Column       | Type    | Description                              |
| ------------ | ------- | ---------------------------------------- |
| id           | bigint  | Primary key                              |
| workflow_id  | bigint (FK) | Parent workflow                       |
| role_id      | bigint (FK) | Required role for this step           |
| step_order   | int     | Sequential order (1, 2, 3...)            |
| created_at   | timestamp |                                        |
| updated_at   | timestamp |                                        |

**Relationships:**
-   `belongsTo(Workflow)`
-   `belongsTo(Role)`

---

#### `requests`

| Column            | Type         | Description                     |
| ----------------- | ------------ | ------------------------------- |
| id                | bigint       | Primary key                     |
| user_id           | bigint (FK)  | Requester                       |
| request_type_id   | bigint (FK)  | Type of request                 |
| status            | varchar(50)  | `pending`, `approved`, `rejected`|
| current_step_order| int (nullable)| Current workflow step          |
| payload           | json         | Dynamic form data               |
| created_at        | timestamp    |                                 |
| updated_at        | timestamp    |                                 |

**Relationships:**
-   `belongsTo(User)`
-   `belongsTo(RequestType)`
-   `hasMany(RequestApproval)`
-   `hasMany(RequestDocument)`

---

#### `request_approvals`

| Column            | Type         | Description                          |
| ----------------- | ------------ | ------------------------------------ |
| id                | bigint       | Primary key                          |
| request_id        | bigint (FK)  | Associated request                   |
| workflow_step_id  | bigint (FK)  | Workflow step being approved         |
| approver_id       | bigint (FK, nullable) | Who approved (NULL if pending) |
| status            | varchar(50)  | `pending`, `approved`, `rejected`    |
| comments          | text (nullable)| Approval notes                      |
| approved_at       | timestamp (nullable) |                              |
| created_at        | timestamp    |                                      |
| updated_at        | timestamp    |                                      |

**Relationships:**
-   `belongsTo(Request)`
-   `belongsTo(WorkflowStep, 'workflow_step_id')`
-   `belongsTo(User, 'approver_id')`

---

#### `request_documents`

| Column      | Type         | Description                |
| ----------- | ------------ | -------------------------- |
| id          | bigint       | Primary key                |
| request_id  | bigint (FK)  | Associated request         |
| file_path   | varchar(500) | S3 object path             |
| file_name   | varchar(255) | Original filename          |
| file_type   | varchar(100) | MIME type                  |
| file_size   | bigint       | Size in bytes              |
| created_at  | timestamp    |                            |
| updated_at  | timestamp    |                            |

**Relationships:**
-   `belongsTo(Request)`

---

### 3.2 Permissions & Roles

FlowManager uses **Spatie Laravel Permission** for role-based access control.

#### Default Roles

1.  **employee** - Can submit requests, view own requests
2.  **manager** - Can approve manager-level requests
3.  **hr** - Can approve HR-level requests, manage teams
4.  **finance** - Can approve finance-level requests
5.  **admin** - Full system access

---

## 4. Core Features

### 4.1 Dynamic Workflow Engine

The **WorkflowEngine** service (`App\Services\WorkflowEngine`) orchestrates the entire approval process.

#### Key Methods

-   **`initiateRequest(User, RequestType, payload)`**
    -   Creates the Request
    -   Identifies the first workflow step
    -   Creates initial `RequestApproval` record
    -   Fires `RequestCreated` event
    -   **Fallback Logic**: If step requires `manager` but user has no manager, assigns to HR


-   **`processApproval(Request, User, action, comments)`**
    -   Validates user authorization
    -   Updates approval record
    -   Transitions workflow:
        -   **Rejection**: Terminates workflow, fires `RequestRejected`
        -   **Approval**: Moves to next step or completes, fires `RequestApproved`

---

### 4.2 Hierarchical Approval System

The system supports **organizational hierarchy**:

1.  **Direct Manager**: User's immediate superior (`users.manager_id`)
2.  **Team Manager**: Manager of the user's team (`teams.manager_id`)
3.  **HR Fallback**: If no manager exists, assigns to HR role

**Example Workflow:**
```
Employee → Manager → HR → Finance → Complete
```

---

### 4.3 Dynamic Form Validation

Request types support **JSON Schema** for custom form fields.

#### Example `form_schema`:
```json
{
  "fields": [
    {"name": "start_date", "type": "date", "required": true},
    {"name": "end_date", "type": "date", "required": true},
    {"name": "reason", "type": "text", "required": true, "max_length": 500}
  ]
}
```

The `RequestController` validates incoming `payload` against this schema using a custom validator.

---

### 4.4 Document Management

-   **Immediate Upload**: Files are stored locally to `storage/app/temp-uploads` during request creation
-   **Async S3 Transfer**: `DocumentsUploaded` event triggers queued upload to S3
-   **Streaming**: Uses `Storage::writeStream()` for memory-efficient uploads
-   **Cleanup**: Local files are deleted after successful S3 upload

---

## 5. API Endpoints

### 5.1 Authentication

| Method | Endpoint           | Description            |
| ------ | ------------------ | ---------------------- |
| POST   | `/api/auth/register` | Create new user      |
| POST   | `/api/auth/login`    | Login & get token    |
| GET    | `/api/auth/me`       | Get current user     |
| POST   | `/api/auth/logout`   | Invalidate token     |

---

### 5.2 Requests

| Method | Endpoint              | Description                    |
| ------ | --------------------- | ------------------------------ |
| GET    | `/api/requests`       | List user's requests           |
| POST   | `/api/requests`       | Create a new request           |
| GET    | `/api/requests/{id}`  | Get request details            |

**POST `/api/requests` Payload:**
```json
{
  "request_type_id": 1,
  "payload": {
    "start_date": "2026-02-20",
    "end_date": "2026-02-25",
    "reason": "Vacation"
  },
  "documents": [<file>, <file>]  // Optional multipart files
}
```

---

### 5.3 Approvals

| Method | Endpoint                        | Description                |
| ------ | ------------------------------- | -------------------------- |
| GET    | `/api/approvals`                | Pending approvals for user |
| POST   | `/api/requests/{id}/action`     | Approve or reject          |

**POST `/api/requests/{id}/action` Payload:**
```json
{
  "action": "approve",  // or "reject"
  "comments": "Approved for requested dates"
}
```

---

### 5.4 Admin Endpoints

| Method | Endpoint              | Description                   |
| ------ | --------------------- | ----------------------------- |
| GET    | `/api/request-types`  | List all request types        |
| POST   | `/api/request-types`  | Create request type           |
| PUT    | `/api/request-types/{id}` | Update request type       |
| DELETE | `/api/request-types/{id}` | Delete request type       |
| GET    | `/api/workflows`      | List workflows                |
| POST   | `/api/workflows`      | Create workflow               |

*(Similar CRUD for Teams, Users, etc.)*

---

## 6. Event-Driven Architecture

FlowManager uses Laravel's event system to decouple business logic from side effects.

### 6.1 Events

#### `RequestCreated`

**Fired When:** A new request is submitted

**Payload:**
-   `Request $request`
-   `RequestApproval $approval`

**Listeners:**
-   `SendRequestCreatedNotification` - Emails approvers

---

#### `RequestApproved`

**Fired When:** A workflow step is approved

**Payload:**
-   `Request $request`
-   `RequestApproval $approval`

**Listeners:**
-   `SendRequestApprovedNotification` - Emails requester

---

#### `RequestRejected`

**Fired When:** A request is rejected

**Payload:**
-   `Request $request`
-   `RequestApproval $approval`

**Listeners:**
-   `SendRequestRejectedNotification` - Emails requester

---

#### `DocumentsUploaded`

**Fired When:** Files are stored locally

**Payload:**
-   `Request $request`
-   `array $files` (metadata: tmp_path, original_name, mime_type, size)

**Listeners:**
-   `UploadDocumentsToS3` - Transfers to S3 asynchronously

---

### 6.2 Listeners (Queued)

All listeners implement `ShouldQueue`, meaning they are processed asynchronously by Horizon workers.

**Configuration:** `config/queue.php`
```php
'default' => env('QUEUE_CONNECTION', 'redis'),
```

---

## 7. Background Job Processing

### 7.1 Laravel Horizon

FlowManager uses **Laravel Horizon** for Redis queue management.

-   **Route**: `/horizon`
-   **Features**:
    -   Real-time job monitoring
    -   Failed job management
    -   Metrics and throughput graphs
    -   Worker supervision

**Configuration:** `config/horizon.php`

```php
'environments' => [
    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'maxProcesses' => 3,
        ],
    ],
],
```

---

### 7.2 Queue Workers

**Start Horizon:**
```bash
php artisan horizon
```

**Monitor Status:**
```bash
php artisan horizon:status
php artisan horizon:list
```

**Production Deployment:**
Use Supervisor to keep Horizon running:
```ini
[program:horizon]
process_name=%(program_name)s
command=php /path/to/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/horizon.log
stopwaitsecs=3600
```

---

## 8. Monitoring & Observability

### 8.1 Laravel Telescope

-   **Route**: `/telescope`
-   **Features**:
    -   HTTP requests tracking
    -   Database query logs
    -   Exception tracking
    -   Email logs
    -   Cache operations
    -   Event monitoring
    -   Job performance

**Access Control:** Only users with `admin` role can access (configured in `TelescopeServiceProvider`).

---

### 8.2 Logging

-   **Channel**: Stack (single)
-   **Path**: `storage/logs/laravel.log`
-   **Email Driver**: `log` (development) / `smtp` (production)

**Key Logged Events:**
-   Document upload start/completion
-   Event firing
-   Email dispatch
-   Workflow transitions

---

## 9. Security & Authorization

### 9.1 Authentication

Uses **Laravel Sanctum** for stateless API token authentication.

**Token Generation:**
```php
$token = $user->createToken('api-token')->plainTextToken;
```

---

### 9.2 Authorization

#### Policy-Based

-   **RequestPolicy**: Ensures users can only view/update their own requests
-   **ApprovalPolicy**: Validates approver has the required role

#### Permission-Based

Uses Spatie Laravel Permission:
```php
$user->hasRole('manager');
$user->can('approve-requests');
```

---

### 9.3 Middleware

-   `auth:sanctum` - Protects all API routes
-   Custom role checks in controllers

---

## 10. Configuration

### 10.1 Environment Variables

#### Database
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=flow_manager
DB_USERNAME=hashir
DB_PASSWORD=
```

#### Redis
```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis
```

#### AWS S3
```env
AWS_ACCESS_KEY_ID=your-key-id
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=flowmanager-uploads
FILESYSTEM_DISK=s3
```

#### Mail
```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
```

---

### 10.2 Key Configuration Files

-   **Queue**: `config/queue.php`
-   **Horizon**: `config/horizon.php`
-   **Filesystems**: `config/filesystems.php`
-   **Mail**: `config/mail.php`
-   **Permissions**: `config/permission.php`

---

## Conclusion

FlowManager is a production-ready, scalable workflow automation system that leverages Laravel's ecosystem to provide:

✅ **Flexible Approval Workflows**  
✅ **Asynchronous Job Processing**  
✅ **Scalable Document Storage**  
✅ **Comprehensive Monitoring**  
✅ **Role-Based Security**

For user documentation, API examples, and deployment guides, refer to the project's README.md.
