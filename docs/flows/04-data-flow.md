# Data Flow - FlowManager

Comprehensive data flow diagrams showing how information moves through the FlowManager system.

---

## Table of Contents

1. [Request State Machine](#1-request-state-machine)
2. [Data Transformations](#2-data-transformations)
3. [Information Flow Across Layers](#3-information-flow-across-layers)
4. [Queue Data Flow](#4-queue-data-flow)
5. [Database Operations](#5-database-operations)

---

## 1. Request State Machine

### Request Status Transitions

```mermaid
stateDiagram-v2
    [*] --> pending: Request Created
    
    pending --> approved: All Steps Approved
    pending --> rejected: Any Step Rejected
    
    approved --> [*]: Workflow Complete
    rejected --> [*]: Workflow Terminated
    
    note right of pending
        active_step increments
        as approvals complete
    end note
    
    note right of approved
        Final status
        Cannot be changed
    end note
    
    note right of rejected
        Final status
        Cannot be changed
    end note
```

### Approval Status Transitions

```mermaid
stateDiagram-v2
    [*] --> pending: Approval Created
    
    pending --> approved: Approver Approves
    pending --> rejected: Approver Rejects
    
    approved --> [*]: Trigger Next Step
    rejected --> [*]: Terminate Workflow
    
    note right of pending
        approver_id = NULL
        approved_at = NULL
    end note
    
    note right of approved
        approver_id = user.id
        approved_at = NOW()
    end note
    
    note right of rejected
        approver_id = user.id
        comments = reason
    end note
```

---

## 2. Data Transformations

### Request Input to Database

```mermaid
flowchart LR
    subgraph "Client Input"
        A[HTTP Request]
        B["request_type_id: 1, payload: data, documents: files"]
    end
    
    subgraph "Validation Layer"
        C[Laravel Validator]
        D[FormRequest Rules]
    end
    
    subgraph "Service Layer"
        E[RequestService]
        F[WorkflowEngine]
    end
    
    subgraph "Database Records"
        G[(Request)]
        H[(RequestApproval)]
        I[(RequestDocument)]
    end
    
    A --> B
    B --> C
    C --> D
    D --> E
    E --> F
    F --> G
    F --> H
    E --> I
    
    style C fill:#ff9800
    style F fill:#ffeb3b
    style G fill:#4caf50
    style H fill:#4caf50
    style I fill:#4caf50
```

### Database to API Response

```mermaid
flowchart LR
    subgraph "Database"
        DB[(Tables)]
    end
    
    subgraph "Eloquent Models"
        M1[Request Model]
        M2[with requestType]
        M3[with approvals.step]
        M4[with documents]
    end
    
    subgraph "Resource Layer"
        R[RequestResource]
    end
    
    subgraph "JSON Response"
        J["id, status, request_type, approvals, documents"]
    end
    
    DB --> M1
    M1 --> M2
    M2 --> M3
    M3 --> M4
    M4 --> R
    R --> J
    
    style M1 fill:#2196f3
    style R fill:#ff9800
    style J fill:#4caf50
```

---

## 3. Information Flow Across Layers

### Complete Data Pipeline

```mermaid
graph TB
    subgraph "HTTP Layer"
        Request[HTTP Request]
        Response[HTTP Response]
    end
    
    subgraph "Middleware"
        Auth[Authentication]
        Validate[Validation]
    end
    
    subgraph "Controller"
        Store[store method]
        Show[show method]
    end
    
    subgraph "Service"
        RS[RequestService]
    end
    
    subgraph "Domain"
        WE[WorkflowEngine]
    end
    
    subgraph "Model"
        Req[Request Model]
        App[RequestApproval]
    end
    
    subgraph "Database"
        PG[(PostgreSQL)]
    end
    
    subgraph "Event Bus"
        Evt[Events]
    end
    
    subgraph "Queue"
        Redis[(Redis)]
        Worker[Queue Worker]
    end
    
    subgraph "External"
        S3[(AWS S3)]
        Email[SMTP]
    end
    
    Request --> Auth
    Auth --> Validate
    Validate --> Store
    Store --> RS
    RS --> WE
    WE --> Req
    WE --> App
    Req --> PG
    App --> PG
    
    WE -.->|fire| Evt
    Evt -.->|dispatch| Redis
    Redis --> Worker
    Worker --> S3
    Worker --> Email
    
    Store --> Show
    Show --> Req
    Req --> PG
    PG --> Req
    Req --> Response
    
    style WE fill:#ffeb3b
    style Evt fill:#4caf50
    style Worker fill:#2196f3
```

### Data Enrichment Flow

```mermaid
flowchart TD
    Start[Raw Request Data] --> Validate[Validation Rules]
    Validate --> Enrich1[Add User Context]
    Enrich1 --> Enrich2[Determine Workflow]
    Enrich2 --> Enrich3[Calculate First Step]
    Enrich3 --> Enrich4[Find Approver]
    Enrich4 --> Enrich5[Set Status & Timestamps]
    Enrich5 --> Store[(Store in Database)]
    
    Store --> Retrieve[Retrieve with Relations]
    Retrieve --> Transform[Apply Resource Transformation]
    Transform --> Return[Return to Client]
    
    style Enrich1 fill:#fff59d
    style Enrich2 fill:#fff59d
    style Enrich3 fill:#fff59d
    style Enrich4 fill:#fff59d
    style Enrich5 fill:#fff59d
    style Store fill:#4caf50
    style Transform fill:#2196f3
```

---

## 4. Queue Data Flow

### Event to Email Flow

```mermaid
sequenceDiagram
    autonumber
    participant App as Application
    participant Event as Event Class
    participant Dispatcher as Event Dispatcher
    participant Queue as Redis Queue
    participant Worker as Queue Worker
    participant Listener as Email Listener
    participant Mailable as Mail Class
    participant Driver as Mail Driver
    
    App->>Event: new RequestCreated(request, approval)
    App->>Dispatcher: event(new RequestCreated)
    
    Dispatcher->>Dispatcher: Find Listeners
    Dispatcher->>Queue: serialize(Listener, Event Data)
    
    Note over Queue: Event data stored as JSON
    
    Queue->>Worker: Pull job from queue
    Worker->>Worker: Unserialize job
    Worker->>Listener: handle(RequestCreated event)
    
    Listener->>Listener: Extract request & approval
    Listener->>Mailable: new RequestCreatedMail(request, approval)
    Mailable->>Mailable: Build email content
    Mailable->>Driver: Send email
    
    alt SMTP
        Driver->>Driver: Connect to SMTP
        Driver->>Driver: Send via socket
    else Log
        Driver->>Driver: Write to log file
    end
    
    Driver-->>Listener: Email sent
    Listener-->>Worker: Job complete
    Worker->>Queue: Mark job as processed
```

### Document Upload Flow

```mermaid
flowchart TD
    Start[Documents Uploaded Event] --> Queue[Push to Redis]
    Queue --> Worker[Queue Worker Pulls Job]
    Worker --> Listener[UploadDocumentsToS3 Listener]
    
    Listener --> Loop{For Each File}
    Loop -->|Next| ReadLocal[Read from storage/app/temp-uploads]
    ReadLocal --> Stream[Create Stream]
    Stream --> S3Write[Write Stream to S3]
    S3Write --> DBRecord[(INSERT request_documents)]
    DBRecord --> DeleteLocal[Delete Local File]
    DeleteLocal --> Loop
    
    Loop -->|Done| Complete[Job Complete]
    Complete --> Metrics[(Update Horizon Metrics)]
    
    style Queue fill:#f44336
    style Worker fill:#4caf50
    style S3Write fill:#2196f3
    style DBRecord fill:#4caf50
```

---

## 5. Database Operations

### Insert Operations

```mermaid
flowchart TD
    Start[New Request Submission] --> InsertReq[(INSERT INTO requests)]
    InsertReq --> GetID[Get request_id]
    GetID --> InsertApp[(INSERT INTO request_approvals)]
    
    InsertApp --> DocsCheck{Has Documents?}
    DocsCheck -->|No| Done[Commit Transaction]
    DocsCheck -->|Yes| QueueUpload[Queue Upload Job]
    QueueUpload --> Done
    
    Done --> AsyncDocs[Async: Process Documents]
    AsyncDocs --> LoopDocs{For Each Document}
    LoopDocs --> InsertDoc[(INSERT INTO request_documents)]
    InsertDoc --> LoopDocs
    LoopDocs -->|Done| Complete[All Data Persisted]
    
    style InsertReq fill:#4caf50
    style InsertApp fill:#4caf50
    style InsertDoc fill:#4caf50
```

### Update Operations

```mermaid
flowchart TD
    Start[Approval Decision] --> LoadRecords[(SELECT request, approval)]
    LoadRecords --> BeginTx[BEGIN TRANSACTION]
    BeginTx --> UpdateApp[(UPDATE request_approvals)]
    
    UpdateApp --> Decision{Approve or Reject?}
    Decision -->|Approve| HasNext{Next Step Exists?}
    Decision -->|Reject| UpdateReq1[(UPDATE requests status='rejected')]
    
    HasNext -->|Yes| CreateNext[(INSERT next approval)]
    HasNext -->|No| UpdateReq2[(UPDATE requests status='approved')]
    
    CreateNext --> UpdateStep[(UPDATE requests current_step_order)]
    UpdateStep --> Commit[COMMIT]
    UpdateReq1 --> Commit
    UpdateReq2 --> Commit
    
    Commit --> Events[Fire Events]
    
    style UpdateApp fill:#ff9800
    style CreateNext fill:#4caf50
    style UpdateReq1 fill:#f44336
    style UpdateReq2 fill:#4caf50
    style Commit fill:#81c784
```

### Select Operations with Eager Loading

```mermaid
flowchart LR
    Query[GET /api/requests/:id] --> Load1[(SELECT * FROM requests WHERE id)]
    Load1 --> Load2[(SELECT * FROM request_types WHERE id)]
    Load2 --> Load3[(SELECT * FROM request_approvals WHERE request_id)]
    Load3 --> Load4[(SELECT * FROM workflow_steps WHERE id IN)]
    Load4 --> Load5[(SELECT * FROM roles WHERE id IN)]
    Load5 --> Load6[(SELECT * FROM users WHERE id IN)]
    Load6 --> Load7[(SELECT * FROM request_documents WHERE request_id)]
    Load7 --> Assemble[Assemble Object Graph]
    Assemble --> Resource[Apply Resource Transform]
    Resource --> JSON[Return JSON]
    
    style Load1 fill:#2196f3
    style Assemble fill:#ff9800
    style Resource fill:#ffeb3b
```

---

## Data Flow Patterns

### Read Pattern

1. **Controller** receives request
2. **Policy** checks authorization
3. **Model** queries database with eager loading
4. **Resource** transforms for API
5. **Response** returned as JSON

### Write Pattern

1. **Controller** validates input
2. **Service** orchestrates business logic
3. **Transaction** begins
4. **Model** persists changes
5. **Transaction** commits
6. **Event** fires for side effects

### Async Pattern

1. **Event** dispatched to queue
2. **Queue** serializes job data
3. **Worker** pulls and processes
4. **Listener** executes logic
5. **Side effect** completes (email/S3)

---

## Data Integrity

### ACID Compliance

| Property | Implementation |
|----------|---------------|
| **Atomicity** | Database transactions |
| **Consistency** | Foreign key constraints |
| **Isolation** | Row-level locking |
| **Durability** | PostgreSQL WAL |

### Validation Layers

```mermaid
flowchart TD
    Input[User Input] --> Client[Client-Side Validation]
    Client -->|Pass| Server[Server-Side Validation]
    Server -->|Pass| Business[Business Rule Validation]
    Business -->|Pass| DB[Database Constraints]
    
    Client -->|Fail| Error1[Return Errors]
    Server -->|Fail| Error2[422 Response]
    Business -->|Fail| Error3[400 Response]
    DB -->|Fail| Error4[500 Response + Rollback]
    
    DB -->|Pass| Success[Data Persisted]
    
    style Success fill:#4caf50
    style Error1 fill:#f44336
    style Error2 fill:#f44336
    style Error3 fill:#f44336
    style Error4 fill:#f44336
```

---

## Data Retention

### Archival Strategy

- **Active Requests**: Full data retention
- **Completed Requests**: Archived after 2 years
- **Telescope Data**: 7 days retention
- **Horizon Metrics**: 30 days retention
- **Log Files**: Rotated weekly

### Soft Deletes

FlowManager uses soft deletes for:
- User accounts (for audit trail)
- Teams (preserve historical references)
- Request types (maintain data integrity)

Hard deletes:
- Test data only
- GDPR compliance requests

---

## Conclusion

FlowManager's data flow is optimized for:

- ✅ **Data Integrity** - Transactions and constraints
- ✅ **Performance** - Eager loading and caching
- ✅ **Scalability** - Async processing
- ✅ **Observability** - Comprehensive logging
- ✅ **Maintainability** - Clear transformation layers
