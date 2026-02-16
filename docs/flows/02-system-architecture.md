# System Architecture Flow - FlowManager

Comprehensive overview of system architecture, component interactions, and technical flow patterns.

---

## Table of Contents

1. [High-Level Architecture](#1-high-level-architecture)
2. [Application Layers](#2-application-layers)
3. [Request Processing Flow](#3-request-processing-flow)
4. [Event-Driven Architecture](#4-event-driven-architecture)
5. [Background Job Processing](#5-background-job-processing)
6. [Database Transaction Flow](#6-database-transaction-flow)

---

## 1. High-Level Architecture

###  System Overview

```mermaid
graph TB
    subgraph "Client Layer"
        API[API Clients]
        WEB[Web Interface]
    end
    
    subgraph "Application Layer"
        Routes[API Routes]
        Middleware[Middleware Stack]
        Controllers[Controllers]
        Resources[API Resources]
    end
    
    subgraph "Business Logic Layer"
        Services[Services]
        WE[WorkflowEngine]
        RS[RequestService]
        AS[ApprovalService]
    end
    
    subgraph "Data Layer"
        Models[Eloquent Models]
        Policies[Authorization Policies]
    end
    
    subgraph "Event System"
        Events[Events]
        Listeners[Queued Listeners]
    end
    
    subgraph "Infrastructure"
        DB[(PostgreSQL)]
        Redis[(Redis Cache/Queue)]
        S3[(AWS S3)]
        Mail[SMTP Server]
    end
    
    subgraph "Monitoring"
        Horizon[Laravel Horizon]
        Telescope[Laravel Telescope]
    end
    
    API --> Routes
    WEB --> Routes
    Routes --> Middleware
    Middleware --> Controllers
    Controllers --> Services
    Controllers --> Resources
    
    Services --> WE
    Services --> RS
    Services --> AS
    
    WE --> Models
    RS --> Models
    AS --> Models
    
    Models --> DB
    Models --> Policies
    
    Services -.->|Fire| Events
    Events -.->|Trigger| Listeners
    
    Listeners --> Mail
    Listeners --> S3
    Listeners --> Redis
    
    Models <--> Redis
    
    Horizon -.->|Monitor| Redis
    Telescope -.->|Debug| Controllers
    Telescope -.->|Track| Models
    
    style WE fill:#ffeb3b
    style Events fill:#4caf50
    style Listeners fill:#4caf50
    style Horizon fill:#ff9800
    style Telescope fill:#ff9800
```

---

## 2. Application Layers

### Layer Interaction Flow

```mermaid
flowchart LR
    subgraph "Presentation Layer"
        R[Routes] --> MW[Middleware]
        MW --> C[Controllers]
    end
    
    subgraph "Application Layer"
        C --> RES[Resources]
        C --> SVC[Services]
    end
    
    subgraph "Domain Layer"
        SVC --> WE[WorkflowEngine]
        SVC --> BL[Business Logic]
    end
    
    subgraph "Data Layer"
        WE --> M[Models]
        BL --> M
        M --> DB[(Database)]
    end
    
    subgraph "Cross-Cutting"
        E[Events]
        L[Listeners]
        P[Policies]
    end
    
    SVC -.-> E
    E -.-> L
    C --> P
    
    style SVC fill:#2196f3
    style WE fill:#ffeb3b
    style E fill:#4caf50
```

### Layer Responsibilities

| Layer | Components | Responsibilities |
|-------|-----------|------------------|
| **Presentation** | Routes, Middleware, Controllers | HTTP handling, authentication, input validation |
| **Application** | Services, Resources | Business orchestration, data transformation |
| **Domain** | WorkflowEngine, Business Logic | Core business rules, workflow execution |
| **Data** | Models, Repositories | Data persistence, retrieval, relationships |
| **Cross-Cutting** | Events, Policies, Logging | Asynchronous operations, authorization, observability |

---

## 3. Request Processing Flow

### Complete Request Lifecycle

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant API as API Layer
    participant Auth as Authentication
    participant Controller
    participant Service
    participant Engine as WorkflowEngine
    participant Model
    participant DB as Database
    participant Event
    participant Queue as Job Queue
    participant Listener
    
    Client->>API: POST /api/requests
    API->>Auth: Verify Token
    Auth-->>API: User Authenticated
    
    API->>Controller: RequestController@store
    Controller->>Controller: Validate Input
    Controller->>Service: RequestService::createRequest()
    
    Service->>Engine: WorkflowEngine::initiateRequest()
    
    Engine->>Model: Create Request
    Model->>DB: INSERT INTO requests
    DB-->>Model: Request Created
    
    Engine->>Engine: Determine First Step
    Engine->>Engine: Find Approver (Manager/HR)
    
    Engine->>Model: Create RequestApproval
    Model->>DB: INSERT INTO request_approvals
    DB-->>Model: Approval Created
    
    Engine->>Event: Fire RequestCreated Event
    Event-->>Engine: Event Dispatched
    
    Engine-->>Service: Request Object
    Service-->>Controller: Request Object
    Controller-->>API: JSON Response
    API-->>Client: 201 Created
    
    Note over Event,Listener: Asynchronous Processing
    
    Event->>Queue: Dispatch to Redis Queue
    Queue->>Listener: SendRequestCreatedNotification
    Listener->>Listener: Build Email
    Listener->>Listener: Send to Approver
    
    alt Documents Attached
        Event->>Queue: Dispatch DocumentsUploaded
        Queue->>Listener: UploadDocumentsToS3
        Listener->>Listener: Stream to S3
        Listener->>DB: Update document records
    end
```

### Step-by-Step Breakdown

1. **Client Request** - HTTP request with payload
2. **Authentication** - Sanctum token validation
3. **Input Validation** - Request rules validation
4. **Service Invocation** - Business logic delegation
5. **Workflow Initiation** - WorkflowEngine orchestration
6. **Database Write** - Request and approval creation
7. **Event Dispatch** - Fire RequestCreated event
8. **Synchronous Response** - Immediate API response
9. **Async Processing** - Queue job execution
10. **Email Delivery** - Background notification
11. **S3 Upload** - Document storage (if applicable)

---

## 4. Event-Driven Architecture

### Event Bus Pattern

```mermaid
graph TB
    subgraph "Event Sources"
        WE[WorkflowEngine]
        RS[RequestService]
        AC[ApprovalController]
    end
    
    subgraph "Event Bus"
        RC[RequestCreated]
        RA[RequestApproved]
        RR[RequestRejected]
        DU[DocumentsUploaded]
    end
    
    subgraph "Listeners (Queued)"
        L1[SendRequestCreatedNotification]
        L2[SendRequestApprovedNotification]
        L3[SendRequestRejectedNotification]
        L4[UploadDocumentsToS3]
    end
    
    subgraph "Side Effects"
        Email[Email Service]
        S3[AWS S3]
        Log[Application Logs]
    end
    
    WE -->|fire| RC
    WE -->|fire| RA
    WE -->|fire| RR
    RS -->|fire| DU
    AC -->|fire| RA
    AC -->|fire| RR
    
    RC -.->|trigger| L1
    RA -.->|trigger| L2
    RR -.->|trigger| L3
    DU -.->|trigger| L4
    
    L1 --> Email
    L2 --> Email
    L3 --> Email
    L4 --> S3
    
    L1 --> Log
    L2 --> Log
    L3 --> Log
    L4 --> Log
    
    style RC fill:#4caf50
    style RA fill:#4caf50
    style RR fill:#4caf50
    style DU fill:#4caf50
    style L1 fill:#2196f3
    style L2 fill:#2196f3
    style L3 fill:#2196f3
    style L4 fill:#2196f3
```

### Event Flow Characteristics

| Aspect | Implementation |
|--------|---------------|
| **Pattern** | Observer (Pub/Sub) |
| **Coupling** | Loosely coupled |
| **Execution** | Asynchronous via queues |
| **Reliability** | Retry mechanism (Horizon) |
| **Scalability** | Horizontal scaling via workers |
| **Monitoring** | Horizon dashboard |

---

## 5. Background Job Processing

### Horizon Queue Architecture

```mermaid
graph LR
    subgraph "Application"
        App[Laravel App]
        Event[Events]
    end
    
    subgraph "Redis Queue"
        Queue[(Redis)]
        Default[default queue]
        High[high priority]
        Low[low priority]
    end
    
    subgraph "Horizon"
        Master[Master Process]
        Supervisor[Supervisor]
        Worker1[Worker 1]
        Worker2[Worker 2]
        Worker3[Worker 3]
    end
    
    subgraph "Listeners"
        L1[Email Listener]
        L2[S3 Upload Listener]
    end
    
    subgraph "External"
        SMTP[SMTP Server]
        S3Storage[AWS S3]
    end
    
    Event -->|dispatch| Queue
    Queue --> Default
    Queue --> High
    Queue --> Low
    
    Master --> Supervisor
    Supervisor --> Worker1
    Supervisor --> Worker2
    Supervisor --> Worker3
    
    Worker1 -->|pull| Default
    Worker2 -->|pull| Default
    Worker3 -->|pull| Default
    
    Worker1 --> L1
    Worker2 --> L2
    
    L1 --> SMTP
    L2 --> S3Storage
    
    style Queue fill:#f44336
    style Supervisor fill:#ff9800
    style Worker1 fill:#4caf50
    style Worker2 fill:#4caf50
    style Worker3 fill:#4caf50
```

### Job Processing Flow

```mermaid
flowchart TD
    Start[Job Dispatched] --> Queue[(Push to Redis)]
    Queue --> Horizon{Horizon Supervisor}
    Horizon --> Worker[Available Worker]
    
    Worker --> Job[Execute Job]
    Job --> Try{Execution}
    
    Try -->|Success| Success[Mark Complete]
    Try -->|Exception| Retry{Retry Available?}
    
    Retry -->|Yes| RetryJob[Requeue Job]
    Retry -->|No| Failed[Mark Failed]
    
    RetryJob --> Queue
    Success --> Metrics[Update Metrics]
    Failed --> Metrics
    Metrics --> Done[Job Done]
    
    style Success fill:#4caf50
    style Failed fill:#f44336
    style RetryJob fill:#ff9800
```

---

## 6. Database Transaction Flow

### Request Creation Transaction

```mermaid
sequenceDiagram
    autonumber
    participant WE as WorkflowEngine
    participant DB as PostgreSQL
    participant Trans as DB Transaction
    
    WE->>Trans: BEGIN TRANSACTION
    
    WE->>DB: INSERT INTO requests
    DB-->>WE: request_id
    
    WE->>DB: SELECT workflow WHERE request_type_id
    DB-->>WE: workflow
    
    WE->>DB: SELECT workflow_steps ORDER BY step_order LIMIT 1
    DB-->>WE: first_step
    
    WE->>DB: SELECT role FROM workflow_steps
    DB-->>WE: required_role
    
    alt Role is Manager
        WE->>DB: SELECT manager_id FROM users WHERE id
        DB-->>WE: manager_id
        
        alt Manager exists
            WE->>WE: approver = manager
        else No Manager
            WE->>DB: SELECT team.manager_id
            DB-->>WE: team_manager_id
            
            alt Team Manager exists
                WE->>WE: approver = team_manager
            else Fallback to HR
                WE->>DB: SELECT users JOIN roles WHERE role = 'hr'
                DB-->>WE: hr_user
                WE->>WE: approver = hr_user
            end
        end
    end
    
    WE->>DB: INSERT INTO request_approvals
    DB-->>WE: approval_id
    
    WE->>Trans: COMMIT
    Trans-->>WE: Transaction Complete
    
    WE->>WE: Fire Events (Outside Transaction)
```

### Approval Processing Transaction

```mermaid
flowchart TD
    Start[Process Approval] --> BeginTx[BEGIN TRANSACTION]
    BeginTx --> Load[SELECT * FROM request_approvals]
    
    Load --> Validate{Validate}
    Validate -->|Invalid| Rollback[ROLLBACK]
    Validate -->|Valid| Update[UPDATE request_approvals]
    
    Update --> Action{Action}
    Action -->|Approve| CheckNext{Next Step?}
    Action -->|Reject| RejectReq[UPDATE requests SET status='rejected']
    
    CheckNext -->|Yes| CreateNext[INSERT new request_approval]
    CheckNext -->|No| CompleteReq[UPDATE requests SET status='approved']
    
    CreateNext --> Commit[COMMIT]
    CompleteReq --> Commit
    RejectReq --> Commit
    Rollback --> End[Transaction End]
    
    Commit --> Event[Fire Events]
    Event --> End
    
    style Commit fill:#4caf50
    style Rollback fill:#f44336
    style Event fill:#ff9800
```

---

## Architecture Patterns

### Design Patterns Used

| Pattern | Implementation | Purpose |
|---------|---------------|---------|
| **Service Layer** | `WorkflowEngine`, `RequestService` | Business logic encapsulation |
| **Repository** | Eloquent Models | Data abstraction |
| **Observer** | Laravel Events/Listeners | Decoupled notifications |
| **Strategy** | Workflow Steps with Roles | Pluggable approval logic |
| **Factory** | Request creation with types | Dynamic object creation |
| **Chain of Responsibility** | Multi-step approval flow | Sequential processing |
| **Queue** | Redis with Horizon | Async job processing |

### Key Architectural Decisions

1. **Event-Driven for Side Effects**
   - Emails and uploads don't block main flow
   - Better scalability and resilience

2. **Service Layer Abstraction**
   - Controllers stay thin
   - Business logic testable and reusable

3. **WorkflowEngine Centralization**
   - Single source of truth for workflow logic
   - Easier maintenance and debugging

4. **Eloquent ORM**
   - Rapid development
   - Built-in relationships and caching

5. **Redis for Cache & Queue**
   - Fast in-memory operations
   - Pub/sub for real-time features

6. **S3 for Document Storage**
   - Scalable and durable
   - Offloads storage from app servers

---

## Performance Considerations

### Optimization Strategies

```mermaid
graph TD
    subgraph "Request Layer"
        Cache[Redis Cache]
        Eager[Eager Loading]
    end
    
    subgraph "Processing Layer"
        Queue[Job Queues]
        Async[Async Events]
    end
    
    subgraph "Storage Layer"
        Index[(Database Indexes)]
        CDN[S3 + CloudFront]
    end
    
    subgraph "Monitoring"
        Slow[Slow Query Log]
        APM[Telescope APM]
    end
    
    Request --> Cache
    Request --> Eager
    
    Business --> Queue
    Business --> Async
    
    Data --> Index
    Data --> CDN
    
    Monitor --> Slow
    Monitor --> APM
    
    style Cache fill:#4caf50
    style Queue fill:#4caf50
    style Index fill:#4caf50
```

| Strategy | Impact |
|----------|--------|
| **Eager Loading** | Reduces N+1 queries |
| **Redis Caching** | 10-100x faster reads |
| **Queue Jobs** | Non-blocking responses |
| **DB Indexes** | Faster lookups |
| **S3 Streaming** | Lower memory usage |
| **Connection Pooling** | Reduced overhead |

---

## Conclusion

The FlowManager architecture is designed for:

- ✅ **Scalability** - Horizontal scaling via queue workers
- ✅ **Reliability** - Transaction safety and job retries
- ✅ **Maintainability** - Clear separation of concerns
- ✅ **Performance** - Async processing and caching
- ✅ **Observability** - Comprehensive monitoring tools
