# Key Flows Summary - FlowManager

Quick reference guide with condensed flow summaries and decision trees for rapid understanding of FlowManager's core processes.

---

## Quick Navigation

| Flow Type | Key Question | Quick Answer |
|-----------|-------------|--------------|
| **User Journey** | How does each role interact? | [Role Flows](#role-quick-reference) |
| **Request Creation** | How are requests submitted? | [Request Flow](#request-lifecycle-summary) |
| **Approval Process** | How do approvals work? | [Approval Flow](#approval-process-summary) |
| **Document Upload** | How are files handled? | [Document Flow](#document-upload-summary) |
| **Authentication** | How do users log in? | [Auth Flow](#authentication-summary) |
| **Data Movement** | Where does data go? | [Data Flow](#data-flow-summary) |

---

## Role Quick Reference

### Admin Capabilities

```mermaid
mindmap
  root((Admin))
    Configuration
      Request Types
      Workflows
      Workflow Steps
    User Management
      Create Users
      Assign Roles
      Set Managers
    Team Management
      Create Teams
      Assign Managers
    Monitoring
      Horizon Dashboard
      Telescope Dashboard
```

### HR Capabilities

```mermaid
mindmap
  root((HR))
    Approvals
      HR-level Requests
      Fallback Approvals
    Team Oversight
      Manage Teams
      View All Requests
    Reporting
      Team Metrics
      Request Analytics
```

### Manager Capabilities

```mermaid
mindmap
  root((Manager))
    Approvals
      Team Requests
      Direct Reports
    Visibility
      Team Dashboard
      Request Status
    Requests
      Create own
      Track own
```

### Employee Capabilities

```mermaid
mindmap
  root((Employee))
    Requests
      Create New
      Track Status
    Notifications
      Email Updates
      Status Changes
    Documents
      Upload Files
      Download Files
```

---

## Request Lifecycle Summary

### Visual Timeline

```mermaid
gantt
    title Request Lifecycle
    dateFormat  s
    section Creation
    User Submits       :0, 1s
    Validation         :1, 1s
    DB Write           :2, 1s
    Event Dispatch     :3, 1s
    section Approval Step 1
    Manager Reviews    :4, 10s
    Manager Approves   :14, 1s
    Route to Next      :15, 1s
    section Approval Step 2
    HR Reviews         :16, 10s
    HR Approves        :26, 1s
    Route to Next      :27, 1s
    section Finance
    Finance Reviews    :28, 10s
    Finance Approves   :38, 1s
    section Complete
    Mark Approved      :39, 1s
    Notify Requester   :40, 1s
```

### Decision Tree

```mermaid
flowchart TD
    Start{Submit Request} -->|Valid| Create[Create in DB]
    Start -->|Invalid| Reject[Validation Error]
    
    Create --> Step1{Step 1}
    Step1 -->|Approved| Step2{Step 2?}
    Step1 -->|Rejected| End1[REJECTED]
    
    Step2 -->|Exists| Process2[Route to Step 2]
    Step2 -->|None| End2[APPROVED]
    
    Process2 --> Dec2{Decision}
    Dec2 -->|Approved| Step3{Step 3?}
    Dec2 -->|Rejected| End1
    
    Step3 -->|Exists| Process3[Route to Step 3]
    Step3 -->|None| End2
    
    Process3 --> Dec3{Decision}
    Dec3 -->|Approved| End2
    Dec3 -->|Rejected| End1
    
    style End2 fill:#4caf50
    style End1 fill:#f44336
```

---

## Approval Process Summary

### Approver Selection Logic

```mermaid
flowchart TD
    Start[Workflow Step] --> Role{Required Role}
    
    Role -->|Manager| Mgr{User Has Manager?}
    Role -->|HR| HR[Assign HR User]
    Role -->|Finance| Fin[Assign Finance User]
    
    Mgr -->|Yes| Direct[Use Direct Manager]
    Mgr -->|No| Team{Has Team Manager?}
    
    Team -->|Yes| TeamMgr[Use Team Manager]
    Team -->|No| Fallback[Fallback to HR]
    
    Direct --> Done[Approval Created]
    TeamMgr --> Done
    Fallback --> Done
    HR --> Done
    Fin --> Done
    
    style Done fill:#4caf50
```

### Approval States

| State | Meaning | Next Action |
|-------|---------|-------------|
| **pending** | Awaiting approver decision | Approver must review |
| **approved** | Approver accepted | Route to next step or complete |
| **rejected** | Approver denied | Workflow terminates |

---

## Document Upload Summary

### Two-Phase Process

```mermaid
graph LR
    A[Upload] -->|Phase 1: Sync| B[Local Storage]
    B -->|Instant Response| C[API 201]
    B -->|Phase 2: Async| D[Queue Job]
    D --> E[Stream to S3]
    E --> F[Update DB]
    F --> G[Delete Local]
    
    style B fill:#fff59d
    style C fill:#4caf50
    style E fill:#2196f3
```

### Why Two Phases?

1. **Speed** - Local writes are instant (~50ms)
2. **UX** - User gets immediate response
3. **Reliability** - S3 won't block request
4. **Scalability** - Workers handle S3 separately

---

## Authentication Summary

### Login Flow (Simplified)

```mermaid
sequenceDiagram
    User->>API: Email + Password
    API->>DB: Find User
    DB-->>API: User Record
    API->>API: Verify Password
    API->>API: Create Token
    API-->>User: Return Token
    User->>API: Future Requests (with token)
    API-->>User: Authorized
```

### Authorization Check

```
1. Request arrives with token
2. Sanctum verifies token
3. Load User + Roles + Permissions
4. Check Policy/Gate
5. Allow or Deny (403)
```

---

## Data Flow Summary

### Read Operations

```
Client → Routes → Middleware → Controller → Model → Database
                                    ↓
                                Resource (Transform)
                                    ↓
                                Client ← JSON Response
```

### Write Operations

```
Client → Routes → Middleware → Controller → Service → WorkflowEngine
                                                          ↓
                                                    BEGIN TRANSACTION
                                                          ↓
                                                    Model → Database
                                                          ↓
                                                    COMMIT TRANSACTION
                                                          ↓
                                                    Fire Events (Async)
```

---

## Event-Driven Architecture Summary

### Core Pattern

```mermaid
graph LR
    A[Action] --> B[Event]
    B -.->|Async| C[Queue]
    C --> D[Listener]
    D --> E[Side Effect]
    
    A --> F[Return to User]
    
    style B fill:#4caf50
    style C fill:#f44336
    style E fill:#2196f3
```

### Events → Listeners Mapping

| Event | Listener | Action |
|-------|----------|--------|
| **RequestCreated** | SendRequestCreatedNotification | Email approver |
| **RequestApproved** | SendRequestApprovedNotification | Email requester |
| **RequestRejected** | SendRequestRejectedNotification | Email requester |
| **DocumentsUploaded** | UploadDocumentsToS3 | Upload to S3 |

---

## Common Scenarios

### Scenario 1: Employee Submits Leave Request

```
1. Employee fills form (client-side)
2. POST /api/requests with JSON payload
3. Server validates and creates Request
4. WorkflowEngine finds manager
5. Creates pending RequestApproval for manager
6. Fires RequestCreated event
7. Returns 201 to employee (instant)
8. Queue worker emails manager
9. Manager receives email notification
```

### Scenario 2: Manager Approves Request

```
1. Manager clicks approval link
2. POST /api/requests/:id/action {"action": "approve"}
3. Server validates manager has authority
4. Updates RequestApproval to approved
5. Checks for next step (e.g., HR)
6. Creates new RequestApproval for HR
7. Fires RequestApproved event
8. Returns 200 to manager
9. Queue worker emails HR and requester
```

### Scenario 3: Multi-File Upload

```
1. Employee attaches 3 PDFs to request
2. Server stores files in storage/app/temp-uploads
3. Returns 201 immediately
4. DocumentsUploaded event fires
5. Queue worker picks up job
6. Streams file 1 to S3, creates DB record, deletes local
7. Streams file 2 to S3, creates DB record, deletes local
8. Streams file 3 to S3, creates DB record, deletes local
9. All documents available in S3
```

---

## Performance Targets

| Operation | Target Time |
|-----------|-------------|
| **Request Creation** | < 500ms |
| **Approval Processing** | < 300ms |
| **File Upload (local)** | < 100ms |
| **API Authentication** | < 50ms |
| **Email Delivery** | < 30s (async) |
| **S3 Upload** | < 60s per file (async) |

---

## Error Handling Patterns

### Validation Errors (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "payload.start_date": ["The start date field is required."]
  }
}
```

### Authorization Errors (403)

```json
{
  "message": "This action is unauthorized."
}
```

### Not Found (404)

```json
{
  "message": "Request not found."
}
```

---

## Monitoring Quick Reference

### Horizon Dashboard

- **URL**: `/horizon`
- **Purpose**: Queue monitoring
- **Key Metrics**:
  - Jobs per minute
  - Failed jobs
  - Queue wait times

### Telescope Dashboard

- **URL**: `/telescope`
- **Purpose**: Application debugging
- **Key Tabs**:
  - Requests (HTTP)
  - Queries (Database)
  - Jobs (Queue)
  - Mail (Emails)
  - Exceptions (Errors)

---

## Integration Points

### External Services

| Service | Purpose | Integration Method |
|---------|---------|-------------------|
| **AWS S3** | Document storage | Laravel Storage facade |
| **SMTP Server** | Email delivery | Laravel Mail facade |
| **Redis** | Cache & Queue | Laravel Cache/Queue |
| **PostgreSQL** | Primary database | Eloquent ORM |

---

## Key Takeaways

### Architecture Principles

1. **Event-Driven** - Side effects are asynchronous
2. **Service Layer** - Business logic isolated from controllers
3. **Policy-Based** - Authorization via policies and gates
4. **Queue-Based** - Background jobs for slow operations
5. **Transaction-Safe** - Critical operations use DB transactions

### Workflow Characteristics

- **Configurable** - Admin defines approval steps
- **Hierarchical** - Respects org structure
- **Fallback-Safe** - HR serves as backup approver
- **Auditable** - Complete approval history
- **Event-Driven** - Notifications and uploads deferred

### Security Layers

1. **Network** - HTTPS/TLS
2. **Application** - CSRF, Rate Limiting
3. **Authentication** - Laravel Sanctum tokens
4. **Authorization** - Roles, Permissions, Policies
5. **Data** - Encryption, Query Binding

---

## Decision Cheat Sheet

### "Which approver gets assigned?"

1. Check required role for step
2. If Manager: User's manager → Team manager → HR
3. If HR/Finance: Find user with that role
4. Create approval with approver_id

### "When does a request complete?"

1. All workflow steps approved? → Status = 'approved'
2. Any step rejected? → Status = 'rejected'
3. Otherwise → Status = 'pending'

### "How are documents uploaded?"

1. Store locally first
2. Return API response immediately
3. Queue job to stream to S3
4. Update database with S3 path
5. Delete local copy

### "Who can view a request?"

- **Owner** - Always
- **Admin** - Always
- **HR** - Always
- **Current Approver** - If pending at their step
- **Manager** - If requester is on their team
- **Others** - No

---

## Conclusion

FlowManager is designed with these core principles:

✅ **Async-First** - Non-blocking user experience  
✅ **Event-Driven** - Decoupled components  
✅ **Role-Based** - Granular access control  
✅ **Observable** - Comprehensive monitoring  
✅ **Scalable** - Horizontal scaling ready  
✅ **Secure** - Multi-layer protection

For detailed flows, see the individual documentation files linked at the top of this summary.
